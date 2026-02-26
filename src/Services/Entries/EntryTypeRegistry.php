<?php

namespace Searsandrew\SeriesWiki\Services\Entries;

class EntryTypeRegistry
{
    public function definitions(): array
    {
        return (array) config('series-wiki.entries.types', []);
    }

    public function has(string $type): bool
    {
        return array_key_exists($type, $this->definitions());
    }

    /**
     * Returns the structured definition for a type:
     * ['rules'=>[], 'defaults'=>[], 'fields'=>[]]
     */
    public function definitionFor(string $type): ?array
    {
        $def = $this->definitions()[$type] ?? null;

        if (! is_array($def)) {
            return null;
        }

        return [
            'rules' => isset($def['rules']) && is_array($def['rules']) ? $def['rules'] : [],
            'defaults' => isset($def['defaults']) && is_array($def['defaults']) ? $def['defaults'] : [],
            'fields' => isset($def['fields']) && is_array($def['fields']) ? $def['fields'] : [],
        ];
    }

    public function rulesFor(string $type): array
    {
        return $this->definitionFor($type)['rules'] ?? [];
    }

    public function defaultsFor(string $type): array
    {
        return $this->definitionFor($type)['defaults'] ?? [];
    }

    public function fieldsFor(string $type): array
    {
        return $this->definitionFor($type)['fields'] ?? [];
    }
}