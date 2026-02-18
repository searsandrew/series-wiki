<?php

namespace Searsandrew\SeriesWiki\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\UserWorkProgress;

class GateAccess
{
    /** @var array<string, int> cache key => max_gate_position */
    protected array $progressCache = [];

    public function canView(?Authenticatable $user, ?Gate $requiredGate): bool
    {
        if ($requiredGate === null) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        $userId = (string) $user->getAuthIdentifier();

        $max = $this->maxPositionForUserAndWork($userId, (string) $requiredGate->work_id);

        return $max >= (int) $requiredGate->position;
    }

    public function maxPositionForUserAndWork(string $userId, string $workId): int
    {
        $cacheKey = $userId . ':' . $workId;

        if (array_key_exists($cacheKey, $this->progressCache)) {
            return $this->progressCache[$cacheKey];
        }

        $max = (int) UserWorkProgress::query()
            ->where('user_id', $userId)
            ->where('work_id', $workId)
            ->value('max_gate_position');

        return $this->progressCache[$cacheKey] = $max;
    }
}