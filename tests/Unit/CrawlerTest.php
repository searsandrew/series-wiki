<?php

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryBlock;
use Searsandrew\SeriesWiki\Models\LinkSuggestion;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Services\Crawler\LinkSuggestionEngine;

it('creates link suggestions when another entry title appears in block text', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $target = Entry::create([
        'series_id' => $series->id,
        'slug' => 'battle-x',
        'title' => 'Battle X',
        'type' => 'event',
        'status' => 'published',
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-a',
        'title' => 'Ship A',
        'type' => 'ship',
        'status' => 'published',
    ]);

    EntryBlock::create([
        'entry_id' => $source->id,
        'key' => 'overview',
        'body_full' => 'During the War Era, Battle X was decisive. Battle X changed doctrine.',
        'body_safe' => null,
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    $engine = app(LinkSuggestionEngine::class);

    $stats = $engine->crawlSeries($series);

    expect($stats['suggestions_created'])->toBeGreaterThan(0);

    $s = LinkSuggestion::query()
        ->where('entry_id', $source->id)
        ->where('suggested_entry_id', $target->id)
        ->first();

    expect($s)->not->toBeNull();
    expect($s->anchor_text)->toBe('Battle X');
    expect($s->occurrences)->toBe(2);
});

it('does not suggest links to unpublished target entries', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $draftTarget = Entry::create([
        'series_id' => $series->id,
        'slug' => 'secret-thing',
        'title' => 'Secret Thing',
        'type' => 'lore',
        'status' => 'draft',
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-a',
        'title' => 'Ship A',
        'type' => 'ship',
        'status' => 'published',
    ]);

    EntryBlock::create([
        'entry_id' => $source->id,
        'key' => 'overview',
        'body_full' => 'Secret Thing is referenced here.',
        'body_safe' => null,
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    $engine = app(LinkSuggestionEngine::class);
    $engine->crawlSeries($series);

    expect(LinkSuggestion::query()->count())->toBe(0);
});

it('does not suggest links if the title is already a markdown link anchor', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $target = Entry::create([
        'series_id' => $series->id,
        'slug' => 'battle-x',
        'title' => 'Battle X',
        'type' => 'event',
        'status' => 'published',
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-a',
        'title' => 'Ship A',
        'type' => 'ship',
        'status' => 'published',
    ]);

    EntryBlock::create([
        'entry_id' => $source->id,
        'key' => 'overview',
        'body_full' => 'Already linked: [Battle X](/wiki/battle-x). Battle X again.',
        'body_safe' => null,
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    $engine = app(LinkSuggestionEngine::class);
    $engine->crawlSeries($series);

    // We skip entirely if we detect an existing markdown anchor for that title
    expect(LinkSuggestion::query()->count())->toBe(0);
});

it('skips processing an entry if its snapshot hash is unchanged', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $target = Entry::create([
        'series_id' => $series->id,
        'slug' => 'battle-x',
        'title' => 'Battle X',
        'type' => 'event',
        'status' => 'published',
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ship-a',
        'title' => 'Ship A',
        'type' => 'ship',
        'status' => 'published',
    ]);

    EntryBlock::create([
        'entry_id' => $source->id,
        'key' => 'overview',
        'body_full' => 'Battle X appears once.',
        'body_safe' => null,
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    $engine = app(LinkSuggestionEngine::class);

    $engine->crawlSeries($series);
    $firstCount = LinkSuggestion::query()->count();

    $stats2 = $engine->crawlSeries($series);

    expect($firstCount)->toBeGreaterThan(0);
    expect($stats2['entries_skipped_unchanged'])->toBe(2); // ship-a and battle-x both unchanged, both published
});