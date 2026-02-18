<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use HasUlids;

    protected $table = 'sw_templates';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'series_id',
        'slug',
        'name',
        'entry_type',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'is_default' => 'bool',
        'settings' => 'array',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class, 'series_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TemplateSection::class, 'template_id')->orderBy('sort');
    }
}