<?php

namespace Searsandrew\SeriesWiki\Services\Blocks;

use Illuminate\Validation\ValidationException;
use Searsandrew\SeriesWiki\Models\Block;

class BlockValidator
{
    public function __construct(
        protected BlockTypeRegistry $registry
    ) {}

    /**
     * Validates a block. Throws ValidationException on failure.
     */
    public function validate(Block $block): void
    {
        $type = (string) ($block->type ?? 'text');

        if (! $this->registry->has($type)) {
            if (! config('series-wiki.blocks.allow_unknown_types', false)) {
                throw ValidationException::withMessages([
                    'type' => ["Unknown block type: {$type}"],
                ]);
            }

            // Unknown types allowed: only ensure data is array-like if present
            $this->validateUnknown($block);
            return;
        }

        $rules = $this->registry->rulesFor($type) ?? [];

        $payloadRules = (array) ($rules['data'] ?? []);
        $bodyFullRule = $rules['body_full'] ?? null;
        $bodySafeRule = $rules['body_safe'] ?? null;

        $input = [
            'data' => $block->data ?? [],
            'body_full' => $block->body_full ?? null,
            'body_safe' => $block->body_safe ?? null,
        ];

        // Prefix payload rules with "data."
        $finalRules = [];
        foreach ($payloadRules as $key => $rule) {
            $finalRules["data.{$key}"] = $rule;
        }
        if ($bodyFullRule) $finalRules['body_full'] = $bodyFullRule;
        if ($bodySafeRule) $finalRules['body_safe'] = $bodySafeRule;

        // Extra invariant: image blocks must have either asset_id or src if configured
        if ($type === 'image') {
            $finalRules['data.asset_id'] = ($finalRules['data.asset_id'] ?? 'sometimes|string|max:120');
            $finalRules['data.src'] = ($finalRules['data.src'] ?? 'sometimes|string|max:2000');
        }

        $validator = app('validator')->make($input, $finalRules);

        // For image: require one of asset_id/src
        if ($type === 'image') {
            $validator->after(function ($v) use ($input) {
                $asset = $input['data']['asset_id'] ?? null;
                $src = $input['data']['src'] ?? null;
                if (! $asset && ! $src) {
                    $v->errors()->add('data.asset_id', 'Image blocks require asset_id or src.');
                }
            });
        }

        $validator->validate();
    }

    protected function validateUnknown(Block $block): void
    {
        $input = ['data' => $block->data ?? null];
        $rules = ['data' => 'nullable|array'];
        app('validator')->make($input, $rules)->validate();
    }
}