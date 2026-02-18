<?php

namespace Searsandrew\SeriesWiki\Services;

use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\Work;

class GateSeederService
{
    /**
     * Create a numbered set of gates for a work (e.g. Chapter 1..N).
     * Idempotent: won't duplicate existing keys.
     */
    public function createChapterGates(Work $work, int $count, string $labelPrefix = 'Chapter'): void
    {
        $count = max(0, $count);

        // Existing keys so we don't duplicate.
        $existing = Gate::query()
            ->where('work_id', $work->id)
            ->pluck('key')
            ->all();

        $existing = array_flip($existing);

        for ($i = 1; $i <= $count; $i++) {
            $key = (string) $i;

            if (isset($existing[$key])) {
                continue;
            }

            Gate::query()->create([
                'work_id' => $work->id,
                'key' => $key,
                'position' => $i,
                'label' => "{$labelPrefix} {$i}",
            ]);
        }
    }
}