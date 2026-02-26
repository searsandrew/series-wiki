<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntryVariant extends Model
{
    use HasUlids;

    protected $table = 'sw_entry_variants';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'entry_id',
        'faction_id',
        'variant_key',
        'label',
        'is_default',
        'sort',
    ];

    protected $casts = [
        'is_default' => 'bool',
        'sort' => 'int',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }

    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class, 'faction_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class, 'owner_id')
            ->where('owner_type', 'variant')
            ->orderBy('sort');
    }
}