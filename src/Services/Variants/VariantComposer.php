<?php

namespace Searsandrew\SeriesWiki\Services\Variants;

use Illuminate\Support\Collection;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryBlock;
use Searsandrew\SeriesWiki\Models\EntryVariant;
use Searsandrew\SeriesWiki\Models\VariantBlock;

class VariantComposer
{
    /**
     * Returns a unified list of blocks:
     * - base EntryBlocks
     * - overridden by VariantBlocks when keys match
     *
     * Each item includes:
     * - type: 'base'|'variant'
     * - model: EntryBlock|VariantBlock
     */
    public function compose(Entry $entry, ?EntryVariant $variant): Collection
    {
        $base = $entry->blocks()
            ->with(['requiredGate', 'timeSlices'])
            ->get()
            ->map(fn (EntryBlock $b) => [
                'type' => 'base',
                'key' => $b->key,
                'sort' => (int) $b->sort,
                'model' => $b,
            ]);

        if (! $variant) {
            return $base->sortBy(['sort', 'key'])->values();
        }

        $variantBlocks = $variant->blocks()
            ->with(['requiredGate', 'timeSlices'])
            ->get()
            ->map(fn (VariantBlock $b) => [
                'type' => 'variant',
                'key' => $b->key,
                'sort' => (int) $b->sort,
                'model' => $b,
            ]);

        // Override by key: variant wins
        $byKey = $base->keyBy('key');

        foreach ($variantBlocks as $vb) {
            $byKey->put($vb['key'], $vb);
        }

        return $byKey->values()->sortBy(['sort', 'key'])->values();
    }
}