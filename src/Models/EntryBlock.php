<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryBlock extends Model
{
    use HasUlids;

    protected $table = 'sw_entry_blocks';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'entry_id',
        'key',
        'label',
        'format',
        'body_safe',
        'body_full',
        'locked_mode',
        'required_gate_id',
        'sort',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }

    public function requiredGate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'required_gate_id');
    }
}