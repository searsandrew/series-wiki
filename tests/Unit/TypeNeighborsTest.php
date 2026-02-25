<?php

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\TimeSlice;
use Searsandrew\SeriesWiki\Models\TypeNeighbor;
use Searsandrew\SeriesWiki\Services\ContemporaryService;

it('includes only same-type contemporaries when no neighbors are configured', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $slice = TimeSlice::create([
        'series_id' => $series->id,
        'slug' => 'era-a',
        'name' => 'Era A',
        'kind' => 'era',
        'start_year' => 4200,
        'end_year' => 4210,
        'sort' => 0,
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-a',
        'title' => 'Ship A',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $source->timeSlices()->attach($slice->id);

    $sameType = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-b',
        'title' => 'Ship B',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $sameType->timeSlices()->attach($slice->id);

    $neighborType = Entry::create([
        'series_id' => $series->id,
        'slug' => 'battle-x',
        'title' => 'Battle X',
        'type' => 'battle',
        'status' => 'published',
    ]);
    $neighborType->timeSlices()->attach($slice->id);

    $service = app(ContemporaryService::class);

    $out = $service->contemporaries($source);

    expect($out->pluck('id')->all())->toBe([$sameType->id]);
});

it('includes configured neighbor types when includeNeighbors is true', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    TypeNeighbor::create([
        'series_id' => $series->id,
        'type' => 'ship',
        'neighbor_type' => 'battle',
        'weight' => 100,
    ]);

    $slice = TimeSlice::create([
        'series_id' => $series->id,
        'slug' => 'era-a',
        'name' => 'Era A',
        'kind' => 'era',
        'start_year' => 4200,
        'end_year' => 4210,
        'sort' => 0,
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-a',
        'title' => 'Ship A',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $source->timeSlices()->attach($slice->id);

    $sameType = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-b',
        'title' => 'Ship B',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $sameType->timeSlices()->attach($slice->id);

    $battle = Entry::create([
        'series_id' => $series->id,
        'slug' => 'battle-x',
        'title' => 'Battle X',
        'type' => 'battle',
        'status' => 'published',
    ]);
    $battle->timeSlices()->attach($slice->id);

    $service = app(ContemporaryService::class);

    $out = $service->contemporaries($source, null, 10, includeNeighbors: true);

    // Same-type should still rank above neighbors due to the big type bonus
    expect($out->pluck('type')->all())->toBe(['ship', 'battle']);
    expect($out->pluck('id')->all())->toBe([$sameType->id, $battle->id]);
});

it('does not include unpublished entries', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    TypeNeighbor::create([
        'series_id' => $series->id,
        'type' => 'ship',
        'neighbor_type' => 'battle',
        'weight' => 100,
    ]);

    $slice = TimeSlice::create([
        'series_id' => $series->id,
        'slug' => 'era-a',
        'name' => 'Era A',
        'kind' => 'era',
        'start_year' => 4200,
        'end_year' => 4210,
        'sort' => 0,
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-a',
        'title' => 'Ship A',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $source->timeSlices()->attach($slice->id);

    $draftBattle = Entry::create([
        'series_id' => $series->id,
        'slug' => 'battle-x',
        'title' => 'Battle X',
        'type' => 'battle',
        'status' => 'draft',
    ]);
    $draftBattle->timeSlices()->attach($slice->id);

    $service = app(ContemporaryService::class);

    $out = $service->contemporaries($source);

    expect($out)->toHaveCount(0);
});