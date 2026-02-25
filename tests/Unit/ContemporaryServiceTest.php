<?php

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryBlock;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\TimeSlice;
use Searsandrew\SeriesWiki\Services\ContemporaryService;
use Searsandrew\SeriesWiki\Services\Timeline\YearRange;

it('returns contemporaries by shared entry-level time slices (same type)', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $sliceA = TimeSlice::create([
        'series_id' => $series->id,
        'slug' => 'era-a',
        'name' => 'Era A',
        'kind' => 'era',
        'start_year' => 4200,
        'end_year' => 4210,
        'sort' => 0,
    ]);

    $sliceB = TimeSlice::create([
        'series_id' => $series->id,
        'slug' => 'era-b',
        'name' => 'Era B',
        'kind' => 'era',
        'start_year' => 4300,
        'end_year' => 4310,
        'sort' => 0,
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-a',
        'title' => 'Ship A',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $source->timeSlices()->attach($sliceA->id);

    $sameTypeSameSlice = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-b',
        'title' => 'Ship B',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $sameTypeSameSlice->timeSlices()->attach($sliceA->id);

    $sameTypeOtherSlice = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-c',
        'title' => 'Ship C',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $sameTypeOtherSlice->timeSlices()->attach($sliceB->id);

    $diffTypeSameSlice = Entry::create([
        'series_id' => $series->id,
        'slug' => 'species-x',
        'title' => 'Species X',
        'type' => 'species',
        'status' => 'published',
    ]);
    $diffTypeSameSlice->timeSlices()->attach($sliceA->id);

    $service = app(ContemporaryService::class);

    $out = $service->contemporaries($source);

    expect($out->pluck('id')->all())->toBe([$sameTypeSameSlice->id]);
});

it('infers slices from tagged entry blocks when entry has no entry-level time slices', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $slice = TimeSlice::create([
        'series_id' => $series->id,
        'slug' => 'war-era',
        'name' => 'War Era',
        'kind' => 'era',
        'start_year' => 4250,
        'end_year' => 4260,
        'sort' => 0,
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'event-a',
        'title' => 'Event A',
        'type' => 'event',
        'status' => 'published',
    ]);

    $block = EntryBlock::create([
        'entry_id' => $source->id,
        'key' => 'history',
        'body_safe' => 'safe',
        'body_full' => 'full',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    $block->timeSlices()->attach($slice->id);

    $candidate = Entry::create([
        'series_id' => $series->id,
        'slug' => 'event-b',
        'title' => 'Event B',
        'type' => 'event',
        'status' => 'published',
    ]);
    $candidate->timeSlices()->attach($slice->id);

    $service = app(ContemporaryService::class);

    $out = $service->contemporaries($source);

    expect($out)->toHaveCount(1);
    expect($out->first()->id)->toBe($candidate->id);
});

it('returns empty if it cannot determine any slices (no entry slices and no tagged blocks)', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'mystery',
        'title' => 'Mystery',
        'type' => 'event',
        'status' => 'published',
    ]);

    $candidate = Entry::create([
        'series_id' => $series->id,
        'slug' => 'mystery-2',
        'title' => 'Mystery 2',
        'type' => 'event',
        'status' => 'published',
    ]);

    $service = app(ContemporaryService::class);

    $out = $service->contemporaries($source);

    expect($out)->toHaveCount(0);
});

it('supports filtering contemporaries by a viewer year range', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $sliceA = TimeSlice::create([
        'series_id' => $series->id,
        'slug' => 'era-a',
        'name' => 'Era A',
        'kind' => 'era',
        'start_year' => 4200,
        'end_year' => 4210,
        'sort' => 0,
    ]);

    $sliceB = TimeSlice::create([
        'series_id' => $series->id,
        'slug' => 'era-b',
        'name' => 'Era B',
        'kind' => 'era',
        'start_year' => 4300,
        'end_year' => 4310,
        'sort' => 0,
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-a',
        'title' => 'Ship A',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $source->timeSlices()->attach($sliceA->id);

    $inRange = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-b',
        'title' => 'Ship B',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $inRange->timeSlices()->attach($sliceA->id);

    $outOfRange = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-c',
        'title' => 'Ship C',
        'type' => 'ship',
        'status' => 'published',
    ]);
    $outOfRange->timeSlices()->attach($sliceB->id);

    $service = app(ContemporaryService::class);

    $out = $service->contemporaries($source, new YearRange(4205, 4205));

    expect($out->pluck('id')->all())->toBe([$inRange->id]);
});