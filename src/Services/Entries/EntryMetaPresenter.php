<?php

namespace Searsandrew\SeriesWiki\Services\Entries;

use Searsandrew\SeriesWiki\Models\Entry;

class EntryMetaPresenter
{
    public function __construct(
        protected EntryTypeRegistry $registry
    ) {}

    public function present(Entry $entry): array
    {
        $meta = is_array($entry->meta ?? null) ? $entry->meta : [];
        $defaults = $this->registry->defaultsFor((string) $entry->type);

        return array_replace_recursive($defaults, $meta);
    }

    public function fieldsForType(string $type): array
    {
        return $this->registry->fieldsFor($type);
    }
}