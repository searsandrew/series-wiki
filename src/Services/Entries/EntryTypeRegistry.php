<?php

namespace Searsandrew\SeriesWiki\Services\Entries;

class EntryTypeRegistry
{
    /**
     * Returns the entry meta validation rules by type.
     */
    public function definitions(): array
    {
        return (array) config('series-wiki.entries.types', []);
    }

    public function has(string $type): bool
    {
        return array_key_exists($type, $this->definitions());
    }

    public function rulesFor(string $type): ?array
    {
        return $this->definitions()[$type] ?? null;
    }
}