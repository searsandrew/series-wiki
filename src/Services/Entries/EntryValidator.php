<?php

namespace Searsandrew\SeriesWiki\Services\Entries;

use Illuminate\Validation\ValidationException;
use Searsandrew\SeriesWiki\Models\Entry;

class EntryValidator
{
    public function __construct(
        protected EntryTypeRegistry $registry
    ) {}

    /**
     * Validates Entry meta according to configured rules by entry type.
     * If no rules exist for the type, validation is a no-op (by default).
     */
    public function validate(Entry $entry): void
    {
        $type = (string) ($entry->type ?? 'page');

        $rules = $this->registry->rulesFor($type);

        if (! $rules) {
            // If unknown types not allowed AND there are some definitions, treat missing as error
            if (! config('series-wiki.entries.allow_unknown_types', true) && ! $this->registry->has($type)) {
                throw ValidationException::withMessages([
                    'type' => ["Unknown entry type: {$type}"],
                ]);
            }

            return; // no-op if no schema configured
        }

        // Validate meta as an array
        $meta = is_array($entry->meta ?? null) ? $entry->meta : [];

        // Prefix rules with meta.*
        $finalRules = [];
        foreach ($rules as $key => $rule) {
            $finalRules["meta.{$key}"] = $rule;
        }

        app('validator')->make(['meta' => $meta], $finalRules)->validate();
    }
}