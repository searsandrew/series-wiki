<?php

namespace Searsandrew\SeriesWiki\Services\Crawler;

use Illuminate\Support\Collection;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryAlias;
use Searsandrew\SeriesWiki\Models\EntrySnapshot;
use Searsandrew\SeriesWiki\Models\LinkSuggestion;
use Searsandrew\SeriesWiki\Models\Series;

class LinkSuggestionEngine
{
    public function crawlSeries(Series $series, int $limit = 0, bool $dryRun = false): array
    {
        $entries = Entry::query()
            ->where('series_id', $series->id)
            ->where('status', 'published')
            ->orderBy('title')
            ->when($limit > 0, fn ($q) => $q->limit($limit))
            ->get();

        $targets = Entry::query()
            ->where('series_id', $series->id)
            ->where('status', 'published')
            ->get(['id', 'title'])
            ->filter(fn ($e) => trim((string) $e->title) !== '')
            ->values();

        // Aliases for published entries
        $aliases = EntryAlias::query()
            ->whereIn('entry_id', $targets->pluck('id'))
            ->get(['entry_id', 'alias']);

        // Build phrase => list of {entry_id, match}
        $phraseMap = $this->buildPhraseMap($targets, $aliases);

        $scanned = 0;
        $skipped = 0;
        $created = 0;

        foreach ($entries as $entry) {
            $scanned++;

            $text = $this->extractTextForEntry($entry);
            $hash = $this->hashText($text);

            $latestHash = EntrySnapshot::query()
                ->where('entry_id', $entry->id)
                ->orderByDesc('created_at')
                ->value('hash');

            if ($latestHash && hash_equals($latestHash, $hash)) {
                $skipped++;
                continue;
            }

            if (! $dryRun) {
                EntrySnapshot::create([
                    'entry_id' => $entry->id,
                    'hash' => $hash,
                    'text' => $text,
                ]);

                LinkSuggestion::query()
                    ->where('entry_id', $entry->id)
                    ->where('status', 'new')
                    ->delete();
            }

            $entry->loadMissing('blocks');

            foreach ($entry->blocks as $block) {
                $blockText = (string) ($block->body_full ?? '');
                $suggestions = $this->suggestFromTextUsingPhraseMap(
                    sourceEntry: $entry,
                    blockKey: $block->key,
                    text: $blockText,
                    phraseMap: $phraseMap
                );

                foreach ($suggestions as $s) {
                    if (! $dryRun) {
                        LinkSuggestion::updateOrCreate(
                            [
                                'entry_id' => $entry->id,
                                'block_key' => $block->key,
                                'suggested_entry_id' => $s['suggested_entry_id'],
                                'anchor_text' => $s['anchor_text'],
                            ],
                            [
                                'occurrences' => $s['occurrences'],
                                'confidence' => $s['confidence'],
                                'status' => 'new',
                                'snapshot_hash' => $hash,
                                'meta' => $s['meta'],
                            ]
                        );
                    }
                    $created++;
                }
            }
        }

        return [
            'entries_scanned' => $scanned,
            'entries_skipped_unchanged' => $skipped,
            'suggestions_created' => $created,
        ];
    }

    public function extractTextForEntry(Entry $entry): string
    {
        $entry->loadMissing('blocks');

        $parts = [];

        foreach ($entry->blocks as $block) {
            $body = (string) ($block->body_full ?? '');
            if ($body !== '') {
                $parts[] = $block->key . ":\n" . $body;
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Build phrase map from titles + aliases.
     *
     * @param Collection<int, Entry> $targets
     * @param Collection<int, EntryAlias> $aliases
     * @return array<string, array<int, array{entry_id:string, match:string, title:string}>>
     */
    protected function buildPhraseMap(Collection $targets, Collection $aliases): array
    {
        $map = [];

        foreach ($targets as $t) {
            $title = trim((string) $t->title);
            if ($this->isNoisyPhrase($title)) {
                continue;
            }
            $map[$title][] = ['entry_id' => $t->id, 'match' => 'title', 'title' => $title];
        }

        foreach ($aliases as $a) {
            $phrase = trim((string) $a->alias);
            if ($this->isNoisyPhrase($phrase)) {
                continue;
            }

            // Find entry title for display purposes
            $t = $targets->firstWhere('id', $a->entry_id);
            if (! $t) {
                continue;
            }

            $map[$phrase][] = ['entry_id' => $a->entry_id, 'match' => 'alias', 'title' => (string) $t->title];
        }

        // Sort keys by length desc so longer phrases match first
        uksort($map, function ($a, $b) {
            $la = mb_strlen($a);
            $lb = mb_strlen($b);
            if ($la !== $lb) return $lb <=> $la;
            return strcasecmp($a, $b);
        });

        return $map;
    }

    /**
     * Suggest using phrase map.
     *
     * @return Collection<int, array{suggested_entry_id:string, anchor_text:string, occurrences:int, confidence:float, meta:array}>
     */
    protected function suggestFromTextUsingPhraseMap(
        Entry $sourceEntry,
        ?string $blockKey,
        string $text,
        array $phraseMap
    ): Collection {
        $text = (string) $text;

        if (trim($text) === '') {
            return collect();
        }

        $out = collect();

        foreach ($phraseMap as $phrase => $hits) {
            // Skip if already linked with this anchor as markdown
            $alreadyLinkedPattern = '/\[' . preg_quote($phrase, '/') . '\]\(/i';
            if (preg_match($alreadyLinkedPattern, $text)) {
                continue;
            }

            // Find occurrences in text
            $pattern = '/(?<!\w)' . preg_quote($phrase, '/') . '(?!\w)/i';
            if (! preg_match_all($pattern, $text, $m)) {
                continue;
            }

            $occ = count($m[0]);
            if ($occ <= 0) {
                continue;
            }

            foreach ($hits as $hit) {
                // no self suggestions
                if ($hit['entry_id'] === $sourceEntry->id) {
                    continue;
                }

                $confidence = $this->confidenceFromOccurrences($occ);

                $out->push([
                    'suggested_entry_id' => $hit['entry_id'],
                    // anchor_text should be the canonical title for the target entry
                    'anchor_text' => $hit['title'],
                    'occurrences' => $occ,
                    'confidence' => $confidence,
                    'meta' => [
                        'match' => $hit['match'],     // title|alias
                        'phrase' => $phrase,          // what we matched in text
                        'block_key' => $blockKey,
                    ],
                ]);
            }
        }

        // Rank: occurrences desc, then longer phrase, then alpha
        return $out->sort(function ($a, $b) {
            if ($a['occurrences'] !== $b['occurrences']) {
                return $b['occurrences'] <=> $a['occurrences'];
            }
            $la = mb_strlen($a['meta']['phrase'] ?? $a['anchor_text']);
            $lb = mb_strlen($b['meta']['phrase'] ?? $b['anchor_text']);
            if ($la !== $lb) {
                return $lb <=> $la;
            }
            return strcasecmp($a['anchor_text'], $b['anchor_text']);
        })->values();
    }

    protected function isNoisyPhrase(string $phrase): bool
    {
        $p = trim($phrase);

        // too short = noise
        if (mb_strlen($p) < 3) return true;

        // pure numbers or very short tokens are usually bad
        if (preg_match('/^\d+$/', $p)) return true;

        return false;
    }

    protected function confidenceFromOccurrences(int $occ): float
    {
        $base = 1 - exp(-0.35 * max(1, $occ));
        return round(min(0.99, 0.50 + ($base * 0.49)), 4);
    }

    protected function hashText(string $text): string
    {
        return hash('sha256', $text);
    }
}