<?php

namespace Searsandrew\SeriesWiki\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\TimeSlice;
use Searsandrew\SeriesWiki\Services\Timeline\TimeSliceMatcher;
use Searsandrew\SeriesWiki\Services\Timeline\YearRange;

class ContemporaryService
{
    public function __construct(
        protected TimeSliceMatcher $matcher,
    ) {}

    /**
     * Find contemporaries for an entry (same series + same type) based on time slice overlap.
     *
     * @return Collection<int, Entry>
     */
    public function contemporaries(Entry $entry, ?YearRange $range = null, int $limit = 12): Collection
    {
        $sliceIds = $this->timeSliceIdsForEntry($entry);

        // If we can't determine any time slices, don't guess.
        if ($sliceIds->isEmpty()) {
            return collect();
        }

        // Base query: same series, same type, not itself
        $q = Entry::query()
            ->where('series_id', $entry->series_id)
            ->where('type', $entry->type)
            ->where('status', 'published')
            ->where('id', '!=', $entry->id);

        // Must share at least one slice id
        $q->whereHas('timeSlices', function (Builder $sub) use ($sliceIds) {
            $sub->whereIn('sw_time_slices.id', $sliceIds->all());
        });

        // If range provided, ensure the entry has at least one slice that intersects the range
        if ($range) {
            $q->whereHas('timeSlices', function (Builder $sub) use ($range) {
                $sub->where(function (Builder $w) use ($range) {
                    // inclusive overlap: NOT (end < start || start > end)
                    $w->where('end_year', '>=', $range->startYear)
                        ->where('start_year', '<=', $range->endYear);
                });
            });
        }

        // Pull entries + their slices (so we can score overlap)
        $entries = $q->with('timeSlices')->get();

        // Score by overlap count with the source entry sliceIds
        $scored = $entries->map(function (Entry $candidate) use ($sliceIds) {
            $overlap = $candidate->timeSlices
                ->pluck('id')
                ->intersect($sliceIds)
                ->count();

            return [
                'entry' => $candidate,
                'overlap' => $overlap,
            ];
        });

        return $scored
            ->sortByDesc('overlap')
            ->sortBy(fn ($row) => $row['entry']->title)
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

        $variantSliceIds = $entry->variants
            ->flatMap(fn ($v) => $v->blocks->flatMap(fn ($b) => $b->timeSlices->pluck('id')))
            ->unique()
            ->values();

        return $variantSliceIds;
    }
}