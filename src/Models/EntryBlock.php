<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'type',
        'data',
        'body_safe',
        'body_full',
        'locked_mode',
        'required_gate_id',
        'sort',
    ];

    protected $casts = [
        'sort' => 'int',
        'data' => 'array',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }

    public function requiredGate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'required_gate_id');
    }

    public function timeSlices(): BelongsToMany
    {
        return $this->belongsToMany(
            TimeSlice::class,
            'sw_entry_block_time_slices',
            'entry_block_id',
            'time_slice_id'
        );
    }
}