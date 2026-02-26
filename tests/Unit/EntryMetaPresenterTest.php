<?php

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Services\Entries\EntryMetaPresenter;

it('merges entry meta with configured defaults for entry type', function () {
    config()->set('series-wiki.entries.types', [
        'planet' => [
            'defaults' => [
                'gravity_g' => 1.0,
                'atmosphere' => [
                    'breathable' => false,
                ],
            ],
            'fields' => [
                ['key' => 'gravity_g', 'label' => 'Gravity (g)'],
            ],
        ],
    ]);

    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'entele',
        'title' => 'Entele',
        'type' => 'planet',
        'status' => 'published',
        'meta' => [
            'gravity_g' => 0.92,
            'atmosphere' => [
                'breathable' => true,
            ],
        ],
    ]);

    $presenter = app(EntryMetaPresenter::class);

    $out = $presenter->present($entry);

    expect($out['gravity_g'])->toBe(0.92);
    expect($out['atmosphere']['breathable'])->toBeTrue();

    $fields = $presenter->fieldsForType('planet');
    expect($fields)->toHaveCount(1);
    expect($fields[0]['key'])->toBe('gravity_g');
});