<?php

namespace Searsandrew\SeriesWiki\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\TypeNeighbor;
use Searsandrew\SeriesWiki\Services\Timeline\YearRange;

class ContemporaryService
{
    /**
     * Find contemporaries for an entry based on time slice overlap.
     *
     * - Always includes same type
     * - Optionally includes configured neighbor types (series-scoped) with weighted ranking
     * - Only returns published entries
     *
     * @return Collection<int, Entry>
     */
    public function contemporaries(
        Entry $entry,
        ?YearRange $range = null,
        int $limit = 12,
        bool $includeNeighbors = true
    ): Collection {
        $sliceIds = $this->timeSliceIdsForEntry($entry);

        // If we can't determine any time slices, don't guess.
        if ($sliceIds->isEmpty()) {
            return collect();
        }

        $types = collect([$entry->type]);

        $neighborWeights = collect(); // neighbor_type => weight

        if ($includeNeighbors) {
            $neighbors = TypeNeighbor::query()
                ->where('series_id', $entry->series_id)
                ->where('type', $entry->type)
                ->get(['neighbor_type', 'weight']);

            foreach ($neighbors as $n) {
                $types->push($n->neighbor_type);
                $neighborWeights->put($n->neighbor_type, (int) $n->weight);
            }
        }

        $types = $types->unique()->values();

        // Base query: same series, allowed types, published only, not itself
        $q = Entry::query()
            ->where('series_id', $entry->series_id)
            ->whereIn('type', $types->all())
            ->where('status', 'published')
            ->where('id', '!=', $entry->id);

        // Must share at least one slice id (candidate must have entry-level time slices)
        $q->whereHas('timeSlices', function (Builder $sub) use ($sliceIds) {
            $sub->whereIn('sw_time_slices.id', $sliceIds->all());
        });

        // If viewer range provided, ensure candidate intersects range
        if ($range) {
            $q->whereHas('timeSlices', function (Builder $sub) use ($range) {
                $sub->where('end_year', '>=', $range->startYear)
                    ->where('start_year', '<=', $range->endYear);
            });
        }

        $entries = $q->with('timeSlices')->get();

        // Score by overlap count + type bonus/weight
        $scored = $entries->map(function (Entry $candidate) use ($sliceIds, $entry, $neighborWeights) {
            $overlap = $candidate->timeSlices
                ->pluck('id')
                ->intersect($sliceIds)
                ->count();

            // Same type gets a big, stable bonus so it ranks above neighbors.
            $typeBonus = $candidate->type === $entry->type ? 10_000 : 0;

            // Neighbor weight bonus (if configured)
            $neighborBonus = $candidate->type !== $entry->type
                ? (int) ($neighborWeights->get($candidate->type, 0))
                : 0;

            return [
                'entry' => $candidate,
                'score' => ($overlap * 100) + $typeBonus + $neighborBonus,
                'overlap' => $overlap,
            ];
        });

        return $scored
            ->sort(function (array $a, array $b) {
                // score desc
                if ($a['score'] !== $b['score']) {
                    return $b['score'] <=> $a['score'];
                }

                // title asc (case-insensitive)
                return strcasecmp($a['entry']->title, $b['entry']->title);
            })
            ->pluck('entry')
            ->take($limit)
            ->values();
    }

    /**
     * Determine which time slices define this entry's "contemporary set".
     *
     * Priority:
     * 1) entry-level time slices
     * 2) time slices used on entry blocks
     * 3) time slices used on variant blocks (any variant)
     *
     * @return Collection<int, string> time_slice_ids
     */
    public function timeSliceIdsForEntry(Entry $entry): Collection
    {
        $entry->loadMissing(['timeSlices']);

        if ($entry->timeSlices->isNotEmpty()) {
            return $entry->timeSlices->pluck('id')->values();
        }

        // Infer from tagged entry blocks
        $entry->loadMissing(['blocks.timeSlices']);

        $blockSliceIds = $entry->blocks
            ->flatMap(fn ($b) => $b->timeSlices->pluck('id'))
            ->unique()
            ->values();

        if ($blockSliceIds->isNotEmpty()) {
            return $blockSliceIds;
        }

        // Infer from tagged variant blocks (across variants)
        $entry->loadMissing(['variants.blocks.timeSlices']);

        return $entry->variants
            ->flatMap(fn ($v) => $v->blocks->flatMap(fn ($b) => $b->timeSlices->pluck('id')))
            ->unique()
            ->values();
    }
}