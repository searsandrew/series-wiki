<?php

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\UserWorkProgress;
use Searsandrew\SeriesWiki\Models\Work;
use Searsandrew\SeriesWiki\Services\GateAccess;

class FakeUser extends AuthenticatableUser
{
    use HasUlids;

    protected $table = 'users';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
}

it('denies gated content for guests', function () {
    $series = Series::create([
        'slug' => 'stellar-empire',
        'name' => 'Stellar Empire',
    ]);

    $work = Work::create([
        'series_id' => $series->id,
        'slug' => 'bones-that-remember',
        'title' => 'The Bones That Remember',
        'kind' => 'novella',
    ]);

    $gate = Gate::create([
        'work_id' => $work->id,
        'key' => '1',
        'position' => 1,
        'label' => 'Chapter 1',
    ]);

    $access = app(GateAccess::class);

    expect($access->canView(null, $gate))->toBeFalse();
});

it('allows gated content when user progress meets gate position', function () {
    $user = FakeUser::query()->create(['name' => 'Reader']);

    $series = Series::create([
        'slug' => 'stellar-empire',
        'name' => 'Stellar Empire',
    ]);

    $work = Work::create([
        'series_id' => $series->id,
        'slug' => 'bones-that-remember',
        'title' => 'The Bones That Remember',
        'kind' => 'novella',
    ]);

    $gate = Gate::create([
        'work_id' => $work->id,
        'key' => '1',
        'position' => 1,
        'label' => 'Chapter 1',
    ]);

    UserWorkProgress::create([
        'user_id' => (string) $user->getAuthIdentifier(),
        'work_id' => $work->id,
        'max_gate_position' => 1,
    ]);

    $access = app(GateAccess::class);

    expect($access->canView($user, $gate))->toBeTrue();
});

it('denies gated content when progress is behind', function () {
    $user = FakeUser::query()->create(['name' => 'Reader']);

    $series = Series::create([
        'slug' => 'stellar-empire',
        'name' => 'Stellar Empire',
    ]);

    $work = Work::create([
        'series_id' => $series->id,
        'slug' => 'bones-that-remember',
        'title' => 'The Bones That Remember',
        'kind' => 'novella',
    ]);

    $gate = Gate::create([
        'work_id' => $work->id,
        'key' => '3',
        'position' => 3,
        'label' => 'Chapter 3',
    ]);

    UserWorkProgress::create([
        'user_id' => (string) $user->getAuthIdentifier(),
        'work_id' => $work->id,
        'max_gate_position' => 2,
    ]);

    $access = app(GateAccess::class);

    expect($access->canView($user, $gate))->toBeFalse();
});