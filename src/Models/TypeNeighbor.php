<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypeNeighbor extends Model
{
    protected $table = 'sw_type_neighbors';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'series_id',
        'type',
        'neighbor_type',
        'weight',
    ];

    protected $casts = [
        'weight' => 'int',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class, 'series_id');
    }
}