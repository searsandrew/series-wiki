<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryAlias extends Model
{
    use HasUlids;

    protected $table = 'sw_entry_aliases';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'entry_id',
        'alias',
        'is_primary',
        'sort',
    ];

    protected $casts = [
        'is_primary' => 'bool',
        'sort' => 'int',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }
}