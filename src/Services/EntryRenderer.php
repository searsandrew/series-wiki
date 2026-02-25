<?php

namespace Searsandrew\SeriesWiki\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Services\Timeline\TimeSliceMatcher;
use Searsandrew\SeriesWiki\Services\Timeline\YearRange;
use Searsandrew\SeriesWiki\Services\Variants\VariantComposer;
use Searsandrew\SeriesWiki\Services\Variants\VariantResolver;

class EntryRenderer
{
    public function __construct(
        protected GateAccess $gateAccess,
        protected VariantResolver $variantResolver,
        protected VariantComposer $variantComposer,
        protected TimeSliceMatcher $timeSliceMatcher,
    ) {}

    /**
     * Backwards-compatible render: no timeline filter, no variant.
     *
     * @return Collection<int, array{type:string, key:string, model:mixed, body:?string, is_locked:bool, block_type:string, locked_mode:string, has_payload:bool}>
     */
    public function render(Entry $entry, ?Authenticatable $user = null): Collection
    {
        return $this->renderWithContext($entry, $user, null, null);
    }

    /**
     * Render with optional timeline range and variant.
     *
     * Rules:
     * - If a block has no timeSlices => always relevant
     * - If it has timeSlices => show only if intersects range
     * - If block.type !== 'text': body will be null (UI renders via type + data)
     *
     * @return Collection<int, array{type:string, key:string, model:mixed, body:?string, is_locked:bool, block_type:string, locked_mode:string, has_payload:bool}>
     */
    public function renderWithContext(
        Entry $entry,
        ?Authenticatable $user = null,
        ?YearRange $range = null,
        ?string $variantKey = null,
    ): Collection {
        $variant = $this->variantResolver->resolve($entry, $variantKey);

        $composed = $this->variantComposer->compose($entry, $variant);

        return $composed
            ->filter(function (array $item) use ($range) {
                if ($range === null) {
                    return true;
                }

                $model = $item['model'];

                $slices = $model->relationLoaded('timeSlices')
                    ? $model->timeSlices
                    : collect();

                // Untagged block => always relevant
                if ($slices->count() === 0) {
                    return true;
                }

                // Tagged block => must intersect at least one slice
                foreach ($slices as $slice) {
                    if ($this->timeSliceMatcher->intersects($slice, $range)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(function (array $item) use ($user) {
                $model = $item['model'];

                $blockType = (string) ($model->type ?? 'text');
                $lockedMode = (string) ($model->locked_mode ?: config('series-wiki.spoilers.default_locked_mode', 'safe'));
                $hasPayload = !empty($model->data);

                $requiredGate = $model->requiredGate ?? null;

                $canView = $this->gateAccess->canView($user, $requiredGate);

                // LOCKED
                if (! $canView) {
                    if ($lockedMode === 'stub') {
                        return [
                            'type' => $item['type'],
                            'key' => $item['key'],
                            'model' => $model,
                            'body' => (string) config('series-wiki.spoilers.stub_text'),
                            'is_locked' => true,
                            'block_type' => $blockType,
                            'locked_mode' => $lockedMode,
                            'has_payload' => $hasPayload,
                        ];
                    }

                    // safe mode: return safe body (or null)
                    return [
                        'type' => $item['type'],
                        'key' => $item['key'],
                        'model' => $model,
                        'body' => $model->body_safe,
                        'is_locked' => true,
                        'block_type' => $blockType,
                        'locked_mode' => $lockedMode,
                        'has_payload' => $hasPayload,
                    ];
                }

                // UNLOCKED
                // If non-text, body is null. UI uses $model->data to render.
                $body = $blockType === 'text'
                    ? $model->body_full
                    : null;

                return [
                    'type' => $item['type'],
                    'key' => $item['key'],
                    'model' => $model,
                    'body' => $body,
                    'is_locked' => false,
                    'block_type' => $blockType,
                    'locked_mode' => $lockedMode,
                    'has_payload' => $hasPayload,
                ];
            })
            ->values();
    }
}