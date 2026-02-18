<?php

namespace Searsandrew\SeriesWiki\Services\Timeline;

use Searsandrew\SeriesWiki\Models\TimeSlice;

class TimeSliceMatcher
{
    public function intersects(TimeSlice $slice, YearRange $range): bool
    {
        return !($slice->end_year < $range->startYear || $slice->start_year > $range->endYear);
    }
}