<?php

namespace Searsandrew\SeriesWiki\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\UserWorkProgress;
use Searsandrew\SeriesWiki\Models\Work;

class ProgressService
{
    /**
     * Set a user's progress for a work to a specific gate position.
     *
     * - Default behavior: only moves progress forward (never backward).
     * - Set $allowRollback = true if you want to support lowering progress.
     */
    public function setProgress(
        Authenticatable|string $user,
        Work $work,
        int $position,
        bool $allowRollback = false
    ): UserWorkProgress {
        $userId = $this->userId($user);

        $record = UserWorkProgress::query()->firstOrNew([
            'user_id' => $userId,
            'work_id' => $work->id,
        ]);

        $current = (int) ($record->max_gate_position ?? 0);

        if ($allowRollback) {
            $record->max_gate_position = max(0, $position);
        } else {
            $record->max_gate_position = max($current, $position);
        }

        $record->save();

        return $record;
    }

    public function setProgressToGate(
        Authenticatable|string $user,
        Gate $gate,
        bool $allowRollback = false
    ): UserWorkProgress {
        $gate->loadMissing('work');

        return $this->setProgress($user, $gate->work, (int) $gate->position, $allowRollback);
    }

    public function getProgress(Authenticatable|string $user, Work $work): int
    {
        $userId = $this->userId($user);

        return (int) UserWorkProgress::query()
            ->where('user_id', $userId)
            ->where('work_id', $work->id)
            ->value('max_gate_position');
    }

    protected function userId(Authenticatable|string $user): string
    {
        if (is_string($user)) {
            return $user;
        }

        return (string) $user->getAuthIdentifier();
    }
}