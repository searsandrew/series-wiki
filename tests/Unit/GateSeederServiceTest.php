<?php

use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\Work;
use Searsandrew\SeriesWiki\Services\GateSeederService;

it('creates chapter gates 1..N for a work', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);
    $work = Work::create([
        'series_id' => $series->id,
        'slug' => 'book-a',
        'title' => 'Book A',
        'kind' => 'book',
    ]);

    $service = app(GateSeederService::class);

    $service->createChapterGates($work, 3);

    expect(Gate::query()->where('work_id', $work->id)->count())->toBe(3);

    $keys = Gate::query()->where('work_id', $work->id)->orderBy('position')->pluck('key')->all();
    expect($keys)->toBe(['1', '2', '3']);
});

it('is idempotent and does not create duplicates', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);
    $work = Work::create([
        'series_id' => $series->id,
        'slug' => 'book-a',
        'title' => 'Book A',
        'kind' => 'book',
    ]);

    $service = app(GateSeederService::class);

    $service->createChapterGates($work, 3);
    $service->createChapterGates($work, 3);

    expect(Gate::query()->where('work_id', $work->id)->count())->toBe(3);
});