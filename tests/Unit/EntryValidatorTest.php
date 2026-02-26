<?php

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Services\Entries\EntryValidator;

it('does nothing when no meta rules are configured for the entry type', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'type-88',
        'title' => 'Type-88 Destroyer',
        'type' => 'ship',
        'status' => 'published',
        'meta' => ['length_m' => 'not a number'],
    ]);

    // No rules configured => no exception
    app(EntryValidator::class)->validate($entry);

    expect(true)->toBeTrue();
});

it('validates meta according to configured rules for the entry type', function () {
    config()->set('series-wiki.entries.types', [
        'ship' => [
            'rules' => [
                'length_m' => 'required|numeric|min:0',
                'crew' => 'sometimes|integer|min:0',
            ],
            'defaults' => [],
            'fields' => [],
        ],
    ]);

    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'type-88',
        'title' => 'Type-88 Destroyer',
        'type' => 'ship',
        'status' => 'published',
        'meta' => ['length_m' => 120.5, 'crew' => 900],
    ]);

    app(EntryValidator::class)->validate($entry);

    expect(true)->toBeTrue();
});

it('throws a ValidationException when meta violates rules', function () {
    config()->set('series-wiki.entries.types', [
        'ship' => [
            'rules' => [
                'length_m' => 'required|numeric|min:0',
                'crew' => 'sometimes|integer|min:0',
            ],
            'defaults' => [],
            'fields' => [],
        ],
    ]);

    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'type-88',
        'title' => 'Type-88 Destroyer',
        'type' => 'ship',
        'status' => 'published',
        'meta' => ['length_m' => 'nope'],
    ]);

    $thrown = false;

    try {
        app(EntryValidator::class)->validate($entry);
    } catch (\Illuminate\Validation\ValidationException $e) {
        $thrown = true;
        expect($e->errors())->toHaveKey('meta.length_m');
    }

    expect($thrown)->toBeTrue();
});