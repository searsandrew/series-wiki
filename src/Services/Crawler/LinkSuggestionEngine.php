<?php

namespace Searsandrew\SeriesWiki\Services\Crawler;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntrySnapshot;
use Searsandrew\SeriesWiki\Models\LinkSuggestion;
use Searsandrew\SeriesWiki\Models\Series;

class LinkSuggestionEngine
{
    /**
     * Crawl a whole series and generate link suggestions for published entries.
     *
     * @return array{entries_scanned:int, entries_skipped_unchanged:int, suggestions_created:int}
     */
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

        // Title dictionary: id => title, plus map title => id for quick use
        $titleById = $targets->pluck('title', 'id');
        $idByTitle = $targets->pluck('id', 'title'); // titles must be unique-ish; collisions handled by iterating IDs

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

                // Clear only "new" suggestions for this entry when content changes; keep accepted/dismissed history.
                LinkSuggestion::query()
                    ->where('entry_id', $entry->id)
                    ->where('status', 'new')
                    ->delete();
            }

            // Generate suggestions per block using the real block bodies (so we can point to block_key)
            $entry->loadMissing('blocks');

            foreach ($entry->blocks as $block) {
                $blockText = (string) ($block->body_full ?? '');

                $suggestions = $this->suggestFromText(
                    sourceEntry: $entry,
                    blockKey: $block->key,
                    text: $blockText,
                    targets: $targets
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

    /**
     * Extract text used for snapshot hashing and coarse suggestion context.
     * For now: concatenate all block full bodies (published content side).
     */
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
     * @return Collection<int, array{suggested_entry_id:string, anchor_text:string, occurrences:int, confidence:float, meta:array}>
     */
    public function suggestFromText(Entry $sourceEntry, ?string $blockKey, string $text, Collection $targets): Collection
    {
        $text = (string) $text;

        if (trim($text) === '') {
            return collect();
        }

        $out = collect();

        foreach ($targets as $target) {
            // Don't suggest self
            if ($target->id === $sourceEntry->id) {
                continue;
            }

            $title = trim((string) $target->title);

            // Skip very short titles (noise)
            if (mb_strlen($title) < 3) {
                continue;
            }

            // Already linked? If markdown link uses this exact anchor, skip.
            // Example: [Battle X](...)
            $alreadyLinkedPattern = '/\[' . preg_quote($title, '/') . '\]\(/i';
            if (preg_match($alreadyLinkedPattern, $text)) {
                continue;
            }

            // Find occurrences: word-boundary-ish match (handles punctuation reasonably)
            $pattern = '/(?<!\w)' . preg_quote($title, '/') . '(?!\w)/i';
            if (! preg_match_all($pattern, $text, $m)) {
                continue;
            }

            $occ = count($m[0]);
            if ($occ <= 0) {
                continue;
            }

            $confidence = $this->confidenceFromOccurrences($occ);

            $out->push([
                'suggested_entry_id' => $target->id,
                'anchor_text' => $title,
                'occurrences' => $occ,
                'confidence' => $confidence,
                'meta' => [
                    'match' => 'title',
                    'block_key' => $blockKey,
                ],
            ]);
        }

        // Rank by occurrences desc, then longer title (more specific), then alpha
        return $out->sort(function ($a, $b) {
            if ($a['occurrences'] !== $b['occurrences']) {
                return $b['occurrences'] <=> $a['occurrences'];
            }
            $la = mb_strlen($a['anchor_text']);
            $lb = mb_strlen($b['anchor_text']);
            if ($la !== $lb) {
                return $lb <=> $la;
            }
            return strcasecmp($a['anchor_text'], $b['anchor_text']);
        })->values();
    }

    protected function confidenceFromOccurrences(int $occ): float
    {
        // Simple bounded curve: 1->0.60, 2->0.70, 3->0.78, 4->0.84, 5->0.88, ...
        $base = 1 - exp(-0.35 * max(1, $occ));
        return round(min(0.99, 0.50 + ($base * 0.49)), 4);
    }

    protected function hashText(string $text): string
    {
        // stable hash
        return hash('sha256', $text);
    }
}