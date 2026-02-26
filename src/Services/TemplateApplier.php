<?php

namespace Searsandrew\SeriesWiki\Services;

use Illuminate\Support\Collection;
use Searsandrew\SeriesWiki\Models\Block;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\Template;

class TemplateApplier
{
    public function __construct(
        protected TemplateResolver $resolver
    ) {}

    /**
     * Apply a template to an entry.
     *
     * - By default: creates only missing blocks (does not overwrite).
     * - If $overwrite is true: updates existing blocks to match the template defaults.
     *
     * @return Collection<int, Block> blocks that were created/updated
     */
    public function apply(Entry $entry, ?Template $template = null, bool $overwrite = false): Collection
    {
        $template ??= $this->resolver->resolveForEntry($entry);

        if (! $template) {
            return collect();
        }

        $template->loadMissing('sections');

        $existing = $entry->blocks()->get()->keyBy(fn (Block $b) => $b->key);

        $touched = collect();

        foreach ($template->sections as $section) {
            /** @var Block|null $block */
            $block = $existing->get($section->key);

            if (! $block) {
                $block = Block::query()->create([
                    'owner_type' => 'entry',
                    'owner_id' =>  $entry->id,
                    'key' => $section->key,
                    'label' => $section->label,
                    'format' => $section->format,
                    'body_safe' => $section->body_safe,
                    'body_full' => $section->body_full,
                    'locked_mode' => $section->locked_mode,
                    'required_gate_id' => $section->required_gate_id,
                    'sort' => $section->sort,
                ]);

                $touched->push($block);
                continue;
            }

            if (! $overwrite) {
                continue;
            }

            $block->fill([
                'label' => $section->label,
                'format' => $section->format,
                'body_safe' => $section->body_safe,
                'body_full' => $section->body_full,
                'locked_mode' => $section->locked_mode,
                'required_gate_id' => $section->required_gate_id,
                'sort' => $section->sort,
            ])->save();

            $touched->push($block);
        }

        return $touched;
    }
}