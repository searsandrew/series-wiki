<?php

namespace Searsandrew\SeriesWiki\Services;

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\Template;

class TemplateResolver
{
    public function resolveForEntry(Entry $entry): ?Template
    {
        if ($entry->template_id) {
            return Template::query()->find($entry->template_id);
        }

        // default for entry type
        $typeDefault = Template::query()
            ->where('series_id', $entry->series_id)
            ->where('entry_type', $entry->type)
            ->where('is_default', true)
            ->first();

        if ($typeDefault) {
            return $typeDefault;
        }

        // default generic
        return Template::query()
            ->where('series_id', $entry->series_id)
            ->whereNull('entry_type')
            ->where('is_default', true)
            ->first();
    }
}