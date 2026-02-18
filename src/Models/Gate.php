<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gate extends Model
{
    use HasUlids;

    protected $table = 'sw_gates';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'work_id',
        'key',
        'position',
        'label',
    ];

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'work_id');
    }
}