<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entry extends Model
{
    protected $table = 'sw_entries';

    public function blocks(): HasMany
    {
        return $this->hasMany(EntryBlock::class, 'entry_id');
    }
}