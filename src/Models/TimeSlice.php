<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeSlice extends Model
{
    use HasUlids;

    protected $table = 'sw_time_slices';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'series_id',
        'slug',
        'name',
        'kind',
        'start_year',
        'end_year',
        'sort',
        'meta',
    ];

    protected $casts = [
        'start_year' => 'int',
        'end_year' => 'int',
        'sort' => 'int',
        'meta' => 'array',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class, 'series_id');
    }
}