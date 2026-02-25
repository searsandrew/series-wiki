<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entry extends Model
{
    use HasUlids;

    protected $table = 'sw_entries';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'series_id',
        'template_id',
        'slug',
        'title',
        'type',
        'status',
        'summary',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class, 'series_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(\Searsandrew\SeriesWiki\Models\EntryAlias::class, 'entry_id')->orderBy('sort');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(EntryBlock::class, 'entry_id')->orderBy('sort');
    }

    public function timeSlices(): BelongsToMany
    {
        return $this->belongsToMany(
            TimeSlice::class,
            'sw_entry_time_slices',
            'entry_id',
            'time_slice_id'
        );
    }

    public function variants(): HasMany
    {
        return $this->hasMany(EntryVariant::class, 'entry_id')->orderBy('sort');
    }
}