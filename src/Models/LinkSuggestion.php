<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkSuggestion extends Model
{
    use HasUlids;

    protected $table = 'sw_link_suggestions';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'entry_id',
        'block_key',
        'suggested_entry_id',
        'anchor_text',
        'occurrences',
        'confidence',
        'status',
        'meta',
    ];

    protected $casts = [
        'occurrences' => 'int',
        'confidence' => 'float',
        'meta' => 'array',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }

    public function suggestedEntry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'suggested_entry_id');
    }
}