<?php

namespace Searsandrew\SeriesWiki\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\UserWorkProgress;

class GateAccess
{
    public function canView(?Authenticatable $user, ?Gate $gate): bool
    {
        if (! $gate) {
            return true;
        }

        if (! $user) {
            return false;
        }

        $userId = (string) $user->getAuthIdentifier();

        $max = (int) UserWorkProgress::query()
            ->where('user_id', $userId)
            ->where('work_id', $gate->work_id)
            ->value('max_gate_position');

        return $max >= (int) $gate->position;
    }
}