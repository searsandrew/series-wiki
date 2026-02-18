<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Work extends Model
{
    use HasUlids;

    protected $table = 'sw_works';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'series_id',
        'slug',
        'title',
        'kind',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class, 'series_id');
    }

    public function gates(): HasMany
    {
        return $this->hasMany(Gate::class, 'work_id')->orderBy('position');
    }
}