<?php

namespace Searsandrew\SeriesWiki\Services\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntrySnapshot;
use Searsandrew\SeriesWiki\Models\Series;

class SearchService
{
    /**
     * Search entries by snapshot text.
     *
     * Options:
     * - mode: 'safe'|'full' (default 'safe')
     * - type: filter entry type (optional)
     * - limit: max results (default 20)
     *
     * @return Collection<int, array{entry:Entry, score:int, snippet:string, mode:string}>
     */
    public function search(Series $series, string $query, array $options = []): Collection
    {
        $q = trim($query);
        if ($q === '') {
            return collect();
        }

        $mode = $options['mode'] ?? 'safe';
        $type = $options['type'] ?? null;
        $limit = (int) ($options['limit'] ?? 20);

        // Pull recent snapshots matching query (we'll dedupe by entry_id in PHP)
        $snapshots = EntrySnapshot::query()
            ->where('mode', $mode)
            ->whereHas('entry', function (Builder $sub) use ($series, $type) {
                $sub->where('series_id', $series->id)
                    ->where('status', 'published');

                if ($type) {
                    $sub->where('type', $type);
                }
            })
            ->where('text', 'like', '%' . $this->escapeLike($q) . '%')
            ->orderByDesc('created_at')
            ->limit(200) // widen to allow dedupe
            ->with('entry')
            ->get();

        $seen = [];
        $results = [];

        foreach ($snapshots as $snap) {
            $entry = $snap->entry;
            if (! $entry) continue;

            if (isset($seen[$entry->id])) {
                continue;
            }
            $seen[$entry->id] = true;

            $text = (string) $snap->text;

            $score = $this->countOccurrences($text, $q);
            $snippet = $this->makeSnippet($text, $q);

            $results[] = [
                'entry' => $entry,
                'score' => $score,
                'snippet' => $snippet,
                'mode' => $mode,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        // Sort by score desc then title
        return collect($results)->sort(function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            return strcasecmp($a['entry']->title, $b['entry']->title);
        })->values();
    }

    protected function countOccurrences(string $text, string $needle): int
    {
        $pattern = '/' . preg_quote($needle, '/') . '/i';
        if (!preg_match_all($pattern, $text, $m)) return 0;
        return count($m[0]);
    }

    protected function makeSnippet(string $text, string $needle, int $radius = 80): string
    {
        $lower = mb_strtolower($text);
        $n = mb_strtolower($needle);

        $pos = mb_strpos($lower, $n);
        if ($pos === false) {
            return mb_substr($text, 0, min(160, mb_strlen($text)));
        }

        $start = max(0, $pos - $radius);
        $end = min(mb_strlen($text), $pos + mb_strlen($needle) + $radius);

        $snippet = mb_substr($text, $start, $end - $start);
        $snippet = trim(preg_replace('/\s+/', ' ', $snippet) ?? $snippet);

        if ($start > 0) $snippet = '…' . $snippet;
        if ($end < mb_strlen($text)) $snippet .= '…';

        return $snippet;
    }

    protected function escapeLike(string $value): string
    {
        // SQLite/MySQL LIKE escape for % and _
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}