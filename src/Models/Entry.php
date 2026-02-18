<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entry extends Model
{
    use HasUlids;

    protected $table = 'sw_entries';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'series_id',
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

    public function blocks(): HasMany
    {
        return $this->hasMany(EntryBlock::class, 'entry_id')->orderBy('sort');
    }
}