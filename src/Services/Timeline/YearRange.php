<?php

namespace Searsandrew\SeriesWiki\Services\Timeline;

class YearRange
{
    public function __construct(
        public readonly int $startYear,
        public readonly int $endYear,
    ) {
        if ($endYear < $startYear) {
            throw new \InvalidArgumentException('endYear must be >= startYear');
        }
    }
}