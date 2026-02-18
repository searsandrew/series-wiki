<?php

namespace Searsandrew\SeriesWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserWorkProgress extends Model
{
    protected $table = 'sw_user_work_progress';

    protected $fillable = [
        'ulid',
        'user_id',
        'work_id',
        'max_gate_position',
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