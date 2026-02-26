<?php

use Searsandrew\SeriesWiki\Models\Block;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryAlias;
use Searsandrew\SeriesWiki\Models\LinkSuggestion;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Services\Crawler\LinkSuggestionEngine;

it('suggests a link when an alias appears in text', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $target = Entry::create([
        'series_id' => $series->id,
        'slug' => 'type-88-destroyer',
        'title' => 'Type-88 Destroyer',
        'type' => 'ship',
        'status' => 'published',
    ]);

    EntryAlias::create([
        'entry_id' => $target->id,
        'alias' => 'Type 88',
        'is_primary' => false,
        'sort' => 0,
    ]);

    $source = Entry::create([
        'series_id' => $series->id,
        'slug' => 'battle-x',
        'title' => 'Battle X',
        'type' => 'event',
        'status' => 'published',
    ]);

    Block::create([
        'owner_type' => 'entry',
        'owner_id' => $source->id,
        'key' => 'overview',
        'body_full' => 'The Type 88 arrived late but turned the tide.',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    $engine = app(LinkSuggestionEngine::class);
    $engine->crawlSeries($series);

    $s = LinkSuggestion::query()
        ->where('entry_id', $source->id)
        ->where('suggested_entry_id', $target->id)
        ->first();

    expect($s)->not->toBeNull();
    expect($s->anchor_text)->toBe('Type-88 Destroyer');
    expect($s->meta['match'])->toBe('alias');
    expect($s->meta['phrase'])->toBe('Type 88');
});

it('does not suggest a self link even if its title appears', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'battle-x',
        'title' => 'Battle X',
        'type' => 'event',
        'status' => 'published',
    ]);

    Block::create([
        'owner_type' => 'entry',
        'owner_id' => $entry->id,
        'key' => 'overview',
        'body_full' => 'Battle X is Battle X.',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    $engine = app(LinkSuggestionEngine::class);
    $engine->crawlSeries($series);

    expect(LinkSuggestion::query()->count())->toBe(0);
});