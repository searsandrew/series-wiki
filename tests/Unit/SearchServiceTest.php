<?php

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\Work;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\Block;
use Searsandrew\SeriesWiki\Services\Crawler\LinkSuggestionEngine;
use Searsandrew\SeriesWiki\Services\Search\SearchService;

it('returns results from safe snapshots and excludes gated full-only terms', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $work = Work::create([
        'series_id' => $series->id,
        'slug' => 'book-a',
        'title' => 'Book A',
        'kind' => 'book',
    ]);

    $gate = Gate::create([
        'work_id' => $work->id,
        'key' => '1',
        'position' => 1,
        'label' => 'Chapter 1',
    ]);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ancient-being',
        'title' => 'Ancient Being',
        'type' => 'lore',
        'status' => 'published',
    ]);

    // Block is gated: safe text doesn't include "planned by uplifted rhyno"
    Block::create([
        'owner_type' => 'entry',
        'owner_id' => $entry->id,
        'key' => 'truth',
        'type' => 'text',
        'body_safe' => 'The galaxy believes it is folklore.',
        'body_full' => 'It was planned by uplifted Rhyno.',
        'required_gate_id' => $gate->id,
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    // Generate snapshots (safe + full)
    app(LinkSuggestionEngine::class)->crawlSeries($series);

    $search = app(SearchService::class);

    $safe = $search->search($series, 'uplifted Rhyno', ['mode' => 'safe']);
    expect($safe)->toHaveCount(0);

    $safe2 = $search->search($series, 'folklore', ['mode' => 'safe']);
    expect($safe2)->toHaveCount(1);
    expect($safe2[0]['entry']->id)->toBe($entry->id);
});

it('can search full snapshots', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'type-88',
        'title' => 'Type-88 Destroyer',
        'type' => 'ship',
        'status' => 'published',
    ]);

    Block::create([
        'owner_type' => 'entry',
        'owner_id' => $entry->id,
        'key' => 'overview',
        'type' => 'text',
        'body_full' => 'The Type 88 was refit for deep patrol.',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    app(LinkSuggestionEngine::class)->crawlSeries($series);

    $search = app(SearchService::class);

    $full = $search->search($series, 'deep patrol', ['mode' => 'full']);
    expect($full)->toHaveCount(1);
    expect($full[0]['entry']->slug)->toBe('type-88');
});

it('supports type filtering', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $ship = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-a',
        'title' => 'Ship A',
        'type' => 'ship',
        'status' => 'published',
    ]);

    $event = Entry::create([
        'series_id' => $series->id,
        'slug' => 'battle-x',
        'title' => 'Battle X',
        'type' => 'event',
        'status' => 'published',
    ]);

    Block::create([
        'owner_type' => 'entry',
        'owner_id' => $ship->id,
        'key' => 'overview',
        'type' => 'text',
        'body_full' => 'Battle X mentioned here.',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    Block::create([
        'owner_type' => 'entry',
        'owner_id' => $event->id,
        'key' => 'overview',
        'type' => 'text',
        'body_full' => 'Battle X was decisive.',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    app(LinkSuggestionEngine::class)->crawlSeries($series);

    $search = app(SearchService::class);

    $shipOnly = $search->search($series, 'Battle X', ['mode' => 'full', 'type' => 'ship']);
    expect($shipOnly)->toHaveCount(1);
    expect($shipOnly[0]['entry']->id)->toBe($ship->id);
});