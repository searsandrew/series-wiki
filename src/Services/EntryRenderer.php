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

    public function render(Entry $entry, ?Authenticatable $user = null): Collection
    {
        return $this->renderWithContext($entry, $user, null, null);
    }

    /**
     * @return Collection<int, array{
     *   type:string,
     *   key:string,
     *   model:mixed,
     *   body:?string,
     *   is_locked:bool,
     *   block_type:string,
     *   locked_mode:string,
     *   has_payload:bool,
     *   display:array{
     *     title:?string,
     *     caption:?string,
     *     alt:?string,
     *     text:?string,
     *     payload:mixed
     *   }
     * }>
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

                if ($slices->count() === 0) {
                    return true; // untagged => always relevant
                }

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
                $data = is_array($model->data ?? null) ? $model->data : null;
                $hasPayload = !empty($data);

                $requiredGate = $model->requiredGate ?? null;
                $canView = $this->gateAccess->canView($user, $requiredGate);

                // Helpers for niceties
                $title = $data['title'] ?? $model->label ?? null;
                $caption = $data['caption'] ?? null;
                $alt = $data['alt'] ?? null;

                // Default payload = data (non-text blocks). For text blocks, payload is null.
                $payload = $blockType === 'text' ? null : $data;

                // LOCKED
                if (! $canView) {
                    if ($lockedMode === 'stub') {
                        $stub = (string) config('series-wiki.spoilers.stub_text');

                        return [
                            'type' => $item['type'],
                            'key' => $item['key'],
                            'model' => $model,
                            'body' => $stub,
                            'is_locked' => true,
                            'block_type' => $blockType,
                            'locked_mode' => $lockedMode,
                            'has_payload' => $hasPayload,
                            'display' => [
                                'title' => $title,
                                'caption' => $caption,
                                'alt' => $alt,
                                'text' => $stub,
                                'payload' => null, // locked stub => do not render payload
                            ],
                        ];
                    }

                    // safe mode: allow safe text; payload stays null unless you later add data.safe
                    $safeText = $model->body_safe;

                    // Optional future nicety: allow data.safe to override payload when locked
                    $safePayload = null;
                    if ($payload !== null && is_array($payload) && isset($payload['safe']) && is_array($payload['safe'])) {
                        $safePayload = $payload['safe'];
                    }

                    return [
                        'type' => $item['type'],
                        'key' => $item['key'],
                        'model' => $model,
                        'body' => $safeText,
                        'is_locked' => true,
                        'block_type' => $blockType,
                        'locked_mode' => $lockedMode,
                        'has_payload' => $hasPayload,
                        'display' => [
                            'title' => $title,
                            'caption' => $caption,
                            'alt' => $alt,
                            'text' => $safeText,
                            'payload' => $safePayload, // usually null; can be data.safe
                        ],
                    ];
                }

                // UNLOCKED
                $fullText = $blockType === 'text' ? $model->body_full : null;

                return [
                    'type' => $item['type'],
                    'key' => $item['key'],
                    'model' => $model,
                    'body' => $fullText,
                    'is_locked' => false,
                    'block_type' => $blockType,
                    'locked_mode' => $lockedMode,
                    'has_payload' => $hasPayload,
                    'display' => [
                        'title' => $title,
                        'caption' => $caption,
                        'alt' => $alt,
                        'text' => $fullText,
                        'payload' => $payload,
                    ],
                ];
            })
            ->values();
    }
}