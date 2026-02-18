<?php

namespace Searsandrew\SeriesWiki\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\UserWorkProgress;

class GateAccess
{
    /** @var array<int, int> work_id => max_gate_position */
    protected array $progressCache = [];

    public function canView(?Authenticatable $user, ?Gate $requiredGate): bool
    {
        if ($requiredGate === null) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        $max = $this->maxPositionForUserAndWork((int) $user->getAuthIdentifier(), $requiredGate->work_id);

        return $max >= (int) $requiredGate->position;
    }

    public function maxPositionForUserAndWork(int $userId, int $workId): int
    {
        if (array_key_exists($workId, $this->progressCache)) {
            return $this->progressCache[$workId];
        }

        $max = (int) UserWorkProgress::query()
            ->where('user_id', $userId)
            ->where('work_id', $workId)
            ->value('max_gate_position');

        return $this->progressCache[$workId] = $max;
    }
}