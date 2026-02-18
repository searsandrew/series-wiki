<?php

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\Work;
use Searsandrew\SeriesWiki\Services\ProgressService;

class ProgressUser extends AuthenticatableUser
{
    use HasUlids;

    protected $table = 'users';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
}

it('creates a progress record when setting progress for the first time', function () {
    $user = ProgressUser::query()->create(['name' => 'Reader']);

    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);
    $work = Work::create([
        'series_id' => $series->id,
        'slug' => 'book-a',
        'title' => 'Book A',
        'kind' => 'book',
    ]);

    $service = app(ProgressService::class);

    $record = $service->setProgress($user, $work, 3);

    expect($record->user_id)->toBe((string) $user->getAuthIdentifier());
    expect($record->work_id)->toBe($work->id);
    expect($record->max_gate_position)->toBe(3);
});

it('does not roll back progress by default', function () {
    $user = ProgressUser::query()->create(['name' => 'Reader']);

    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);
    $work = Work::create([
        'series_id' => $series->id,
        'slug' => 'book-a',
        'title' => 'Book A',
        'kind' => 'book',
    ]);

    $service = app(ProgressService::class);

    $service->setProgress($user, $work, 5);
    $service->setProgress($user, $work, 2);

    expect($service->getProgress($user, $work))->toBe(5);
});

it('can roll back progress when allowRollback is true', function () {
    $user = ProgressUser::query()->create(['name' => 'Reader']);

    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);
    $work = Work::create([
        'series_id' => $series->id,
        'slug' => 'book-a',
        'title' => 'Book A',
        'kind' => 'book',
    ]);

    $service = app(ProgressService::class);

    $service->setProgress($user, $work, 5);
    $service->setProgress($user, $work, 2, allowRollback: true);

    expect($service->getProgress($user, $work))->toBe(2);
});