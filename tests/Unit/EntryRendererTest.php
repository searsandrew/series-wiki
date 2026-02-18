<?php

use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryBlock;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\UserWorkProgress;
use Searsandrew\SeriesWiki\Models\Work;
use Searsandrew\SeriesWiki\Services\EntryRenderer;

class FakeUser2 extends AuthenticatableUser
{
    protected $table = 'users';
    protected $guarded = [];
}

beforeEach(function () {
    if (!Schema::hasTable('users')) {
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
    }
});

it('renders safe body when locked (safe mode)', function () {
    $user = FakeUser2::query()->create(['name' => 'Reader']);

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

    $entryId = DB::table('sw_entries')->insertGetId([
        'series_id' => $seriesId,
        'slug' => 'ogris',
        'title' => 'Ogris',
        'type' => 'species',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $entry = Entry::query()->findOrFail($entryId);

    EntryBlock::query()->create([
        'entry_id' => $entry->id,
        'key' => 'history',
        'format' => 'markdown',
        'body_safe' => 'What the galaxy believes…',
        'body_full' => 'What really happened…',
        'locked_mode' => 'safe',
        'required_gate_id' => $gate->id,
        'sort' => 0,
    ]);

    // No progress → locked
    $renderer = app(EntryRenderer::class);
    $rendered = $renderer->render($entry, $user);

    expect($rendered)->toHaveCount(1);
    expect($rendered[0]['is_locked'])->toBeTrue();
    expect($rendered[0]['body'])->toBe('What the galaxy believes…');
});

it('renders full body when unlocked', function () {
    $user = FakeUser2::query()->create(['name' => 'Reader']);

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

    $entryId = DB::table('sw_entries')->insertGetId([
        'series_id' => $seriesId,
        'slug' => 'ogris',
        'title' => 'Ogris',
        'type' => 'species',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $entry = Entry::query()->findOrFail($entryId);

    EntryBlock::query()->create([
        'entry_id' => $entry->id,
        'key' => 'history',
        'format' => 'markdown',
        'body_safe' => 'What the galaxy believes…',
        'body_full' => 'What really happened…',
        'locked_mode' => 'safe',
        'required_gate_id' => $gate->id,
        'sort' => 0,
    ]);

    $renderer = app(EntryRenderer::class);
    $rendered = $renderer->render($entry, $user);

    expect($rendered[0]['is_locked'])->toBeFalse();
    expect($rendered[0]['body'])->toBe('What really happened…');
});

it('renders stub when locked_mode is stub', function () {
    config()->set('series-wiki.spoilers.stub_text', 'SPOILER STUB');

    $user = FakeUser2::query()->create(['name' => 'Reader']);

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

    $entryId = DB::table('sw_entries')->insertGetId([
        'series_id' => $seriesId,
        'slug' => 'ogris',
        'title' => 'Ogris',
        'type' => 'species',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $entry = Entry::query()->findOrFail($entryId);

    EntryBlock::query()->create([
        'entry_id' => $entry->id,
        'key' => 'history',
        'format' => 'markdown',
        'body_safe' => null,
        'body_full' => 'Hidden truth…',
        'locked_mode' => 'stub',
        'required_gate_id' => $gate->id,
        'sort' => 0,
    ]);

    $renderer = app(EntryRenderer::class);
    $rendered = $renderer->render($entry, $user);

    expect($rendered[0]['is_locked'])->toBeTrue();
    expect($rendered[0]['body'])->toBe('SPOILER STUB');
});