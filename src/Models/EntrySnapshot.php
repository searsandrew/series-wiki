<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrySnapshot extends Model
{
    use HasUlids;

    protected $table = 'sw_entry_snapshots';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'entry_id',
        'mode',
        'hash',
        'text',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }
}