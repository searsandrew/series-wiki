<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryBlock extends Model
{
    protected $table = 'sw_entry_blocks';

    protected $fillable = [
        'entry_id',
        'key',
        'format',
        'body_safe',
        'body_full',
        'locked_mode',
        'required_gate_id',
        'sort',
    ];

    public function requiredGate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'required_gate_id');
    }
}