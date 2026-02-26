<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Block extends Model
{
    use HasUlids;

    protected $table = 'sw_blocks';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'owner_type',
        'owner_id',
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
        'data' => 'array',
        'sort' => 'int',
    ];

    public function requiredGate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'required_gate_id');
    }

    public function timeSlices(): BelongsToMany
    {
        return $this->belongsToMany(
            TimeSlice::class,
            'sw_block_time_slices',
            'block_id',
            'time_slice_id'
        );
    }
}