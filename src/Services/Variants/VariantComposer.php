<?php

namespace Searsandrew\SeriesWiki\Services\Variants;

use Illuminate\Support\Collection;
use Searsandrew\SeriesWiki\Models\Block;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryVariant;

class VariantComposer
{
    public function compose(Entry $entry, ?EntryVariant $variant): Collection
    {
        $base = $entry->blocks()
            ->with(['requiredGate', 'timeSlices'])
            ->get()
            ->map(fn (Block $b) => [
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
            ->map(fn (Block $b) => [
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