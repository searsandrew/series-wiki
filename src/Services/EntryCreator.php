<?php

namespace Searsandrew\SeriesWiki\Services;

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\Template;

class EntryCreator
{
    public function __construct(
        protected TemplateResolver $resolver,
        protected TemplateApplier $applier,
    ) {}

    /**
     * @param array{
     *   series_id: string,
     *   slug: string,
     *   title: string,
     *   type?: string,
     *   status?: string,
     *   summary?: string|null,
     *   template_id?: string|null
     * } $attributes
     */
    public function create(array $attributes, ?Template $template = null): Entry
    {
        $entry = Entry::query()->create($attributes);

        // Resolve template: explicit param > template_id on entry > defaults
        $template ??= $entry->template_id ? Template::query()->find($entry->template_id) : null;
        $template ??= $this->resolver->resolveForEntry($entry);

        if ($template && ! $entry->template_id) {
            $entry->template_id = $template->id;
            $entry->save();
        }

        if ($template) {
            $this->applier->apply($entry, $template, overwrite: false);
        }

        return $entry->fresh(['blocks', 'template']);
    }
}