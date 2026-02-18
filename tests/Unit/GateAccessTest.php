<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Support\Facades\DB;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\UserWorkProgress;
use Searsandrew\SeriesWiki\Models\Work;
use Searsandrew\SeriesWiki\Services\GateAccess;

class FakeUser extends AuthenticatableUser
{
    protected $table = 'users';
    protected $guarded = [];
}

beforeEach(function () {
    // minimal users table for auth identifier
    $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($t) {
        $t->id();
        $t->string('name')->nullable();
        $t->timestamps();
    });
});

it('denies gated content for guests', function () {
    $seriesId = DB::table('sw_series')->insertGetId([
        'ulid' => (string) \Illuminate\Support\Str::ulid(),
        'slug' => 'stellar-empire',
        'name' => 'Stellar Empire',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $work = Work::create([
        'series_id' => $seriesId,
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
    $user = FakeUser::query()->create(['name' => 'Andrew']);

    $seriesId = DB::table('sw_series')->insertGetId([
        'ulid' => (string) \Illuminate\Support\Str::ulid(),
        'slug' => 'stellar-empire',
        'name' => 'Stellar Empire',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $work = Work::create([
        'series_id' => $seriesId,
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
        'user_id' => $user->id,
        'work_id' => $work->id,
        'max_gate_position' => 1,
    ]);

    $access = app(GateAccess::class);

    expect($access->canView($user, $gate))->toBeTrue();
});

it('denies gated content when progress is behind', function () {
    $user = FakeUser::query()->create(['name' => 'Andrew']);

    $seriesId = DB::table('sw_series')->insertGetId([
        'ulid' => (string) \Illuminate\Support\Str::ulid(),
        'slug' => 'stellar-empire',
        'name' => 'Stellar Empire',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $work = Work::create([
        'series_id' => $seriesId,
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
        'user_id' => $user->id,
        'work_id' => $work->id,
        'max_gate_position' => 2,
    ]);

    $access = app(GateAccess::class);

    expect($access->canView($user, $gate))->toBeFalse();
});