<?php

namespace Searsandrew\SeriesWiki\Services\Blocks;

class BlockTypeRegistry
{
    /**
     * Built-in block types and their validation rules.
     * Rules are applied to:
     * - data.* (payload)
     * - body_full/body_safe (optional)
     */
    public function definitions(): array
    {
        $builtIns = [
            'text' => [
                'data' => [
                    'title' => 'sometimes|string|max:200',
                    'caption' => 'sometimes|string|max:500',
                ],
                // don’t require body_full; some blocks may intentionally be empty drafts
                'body_full' => 'sometimes|nullable|string',
                'body_safe' => 'sometimes|nullable|string',
            ],

            'image' => [
                'data' => [
                    // allow either asset_id (internal) or src (external)
                    'asset_id' => 'sometimes|string|max:120',
                    'src' => 'sometimes|string|max:2000',
                    'alt' => 'sometimes|string|max:500',
                    'title' => 'sometimes|string|max:200',
                    'caption' => 'sometimes|string|max:1000',
                    'credit' => 'sometimes|string|max:500',
                ],
            ],

            'gallery' => [
                'data' => [
                    'title' => 'sometimes|string|max:200',
                    'caption' => 'sometimes|string|max:1000',
                    'items' => 'required|array|min:1',
                    'items.*.asset_id' => 'sometimes|string|max:120',
                    'items.*.src' => 'sometimes|string|max:2000',
                    'items.*.alt' => 'sometimes|string|max:500',
                    'items.*.caption' => 'sometimes|string|max:1000',
                ],
            ],

            'chart' => [
                'data' => [
                    'title' => 'sometimes|string|max:200',
                    'caption' => 'sometimes|string|max:1000',
                    'dataset_id' => 'required|string|max:120',
                    'chart_type' => 'required|string|max:50',
                    'options' => 'sometimes|array',
                ],
            ],

            'map' => [
                'data' => [
                    'title' => 'sometimes|string|max:200',
                    'caption' => 'sometimes|string|max:1000',
                    'asset_id' => 'required|string|max:120',
                    'pins' => 'sometimes|array',
                    'pins.*.x' => 'required_with:pins|numeric|min:0|max:1',
                    'pins.*.y' => 'required_with:pins|numeric|min:0|max:1',
                    'pins.*.label' => 'sometimes|string|max:200',
                    'pins.*.entry_slug' => 'sometimes|string|max:200',
                ],
            ],

            'callout' => [
                'data' => [
                    'title' => 'sometimes|string|max:200',
                    'caption' => 'sometimes|string|max:500',
                    'style' => 'sometimes|string|max:50', // info|warning|quote etc (UI decides)
                    'icon' => 'sometimes|string|max:100',
                ],
                'body_full' => 'sometimes|nullable|string',
                'body_safe' => 'sometimes|nullable|string',
            ],
        ];

        // Merge in host overrides/additions
        $custom = (array) config('series-wiki.blocks.types', []);
        return array_replace_recursive($builtIns, $custom);
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