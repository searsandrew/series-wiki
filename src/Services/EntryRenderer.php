<?php

namespace Searsandrew\SeriesWiki\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryBlock;

class EntryRenderer
{
    public function __construct(
        protected GateAccess $gateAccess,
    ) {}

    /**
     * @return Collection<int, array{block: EntryBlock, body: ?string, is_locked: bool, required_gate: ?Gate}>
     */
    public function render(Entry $entry, ?Authenticatable $user = null): Collection
    {
        return $entry->blocks()
            ->with('requiredGate')
            ->orderBy('sort')
            ->get()
            ->map(function (EntryBlock $block) use ($user) {
                $requiredGate = $block->requiredGate;

                $canView = $this->gateAccess->canView($user, $requiredGate);

                if ($canView) {
                    return [
                        'block' => $block,
                        'body' => $block->body_full,
                        'is_locked' => false,
                        'required_gate' => $requiredGate,
                    ];
                }

                $mode = $block->locked_mode ?: config('series-wiki.spoilers.default_locked_mode', 'safe');

                if ($mode === 'stub') {
                    return [
                        'block' => $block,
                        'body' => config('series-wiki.spoilers.stub_text'),
                        'is_locked' => true,
                        'required_gate' => $requiredGate,
                    ];
                }

                // safe mode
                return [
                    'block' => $block,
                    'body' => $block->body_safe,
                    'is_locked' => true,
                    'required_gate' => $requiredGate,
                ];
            })
            ->values();
    }
}