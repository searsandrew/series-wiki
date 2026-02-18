<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Gate extends Model
{
    protected $table = 'sw_gates';

    protected $fillable = [
        'ulid',
        'work_id',
        'key',
        'position',
        'label',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->ulid ??= (string) Str::ulid();
        });
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'work_id');
    }
}