<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Work extends Model
{
    protected $table = 'sw_works';

    protected $fillable = [
        'ulid',
        'series_id',
        'slug',
        'title',
        'kind',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->ulid ??= (string) Str::ulid();
        });
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class, 'series_id');
    }

    public function gates(): HasMany
    {
        return $this->hasMany(Gate::class, 'work_id')->orderBy('position');
    }
}