<?php

namespace Searsandrew\SeriesWiki\Services\Variants;

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryVariant;

class VariantResolver
{
    public function resolve(Entry $entry, ?string $variantKey): ?EntryVariant
    {
        if ($variantKey) {
            return EntryVariant::query()
                ->where('entry_id', $entry->id)
                ->where('variant_key', $variantKey)
                ->first();
        }

        return EntryVariant::query()
            ->where('entry_id', $entry->id)
            ->where('is_default', true)
            ->orderBy('sort')
            ->first()
            ?? EntryVariant::query()->where('entry_id', $entry->id)->orderBy('sort')->first();
    }
}