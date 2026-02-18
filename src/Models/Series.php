<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Series extends Model
{
    use HasUlids;

    protected $table = 'sw_series';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'series_id');
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class, 'series_id');
    }
}