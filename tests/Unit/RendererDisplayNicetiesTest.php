<?php

use Searsandrew\SeriesWiki\Models\Block;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Services\EntryRenderer;
use Searsandrew\SeriesWiki\Services\Timeline\YearRange;

it('returns normalized display fields for non-text blocks', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'atlas',
        'title' => 'Atlas',
        'type' => 'page',
        'status' => 'published',
    ]);

    Block::create([
        'owner_type' => 'entry',
        'owner_id' => $entry->id,
        'key' => 'map',
        'label' => 'Atlas Map',
        'format' => 'json',
        'type' => 'map',
        'data' => [
            'title' => 'Main Star Map',
            'caption' => 'Tap a pin to open the entry.',
            'asset_id' => 'asset-123',
            'pins' => [
                ['x' => 0.25, 'y' => 0.4, 'label' => 'North Shore', 'entry_slug' => 'north-shore'],
            ],
        ],
        'body_safe' => null,
        'body_full' => null,
        'locked_mode' => 'safe',
        'required_gate_id' => null,
        'sort' => 0,
    ]);

    $renderer = app(EntryRenderer::class);

    $out = $renderer->renderWithContext($entry, null, new YearRange(4200, 4200), null);

    expect($out)->toHaveCount(1);

    $item = $out[0];

    expect($item['block_type'])->toBe('map');
    expect($item['is_locked'])->toBeFalse();

    // Non-text blocks should not emit text body by default
    expect($item['body'])->toBeNull();
    expect($item['display']['text'])->toBeNull();

    // Niceties pulled from data
    expect($item['display']['title'])->toBe('Main Star Map');
    expect($item['display']['caption'])->toBe('Tap a pin to open the entry.');
    expect($item['display']['payload'])->not->toBeNull();

    // Payload should be the data array for non-text blocks
    expect($item['display']['payload']['asset_id'])->toBe('asset-123');
});

it('does not leak payload for non-text blocks when locked_mode is stub', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'secret-map',
        'title' => 'Secret Map',
        'type' => 'page',
        'status' => 'published',
    ]);

    // Create a work+gate so the block can be locked
    $work = \Searsandrew\SeriesWiki\Models\Work::create([
        'series_id' => $series->id,
        'slug' => 'book-a',
        'title' => 'Book A',
        'kind' => 'book',
    ]);

    $gate = \Searsandrew\SeriesWiki\Models\Gate::create([
        'work_id' => $work->id,
        'key' => '1',
        'position' => 1,
        'label' => 'Chapter 1',
    ]);

    Block::create([
        'owner_type' => 'entry',
        'owner_id' => $entry->id,
        'key' => 'map',
        'label' => 'Secret Map',
        'format' => 'json',
        'type' => 'map',
        'data' => [
            'title' => 'Hidden Star Map',
            'caption' => 'This should not be visible when stubbed.',
            'asset_id' => 'asset-secret',
            'pins' => [
                ['x' => 0.1, 'y' => 0.2, 'label' => 'Hidden Site', 'entry_slug' => 'hidden-site'],
            ],
        ],
        'locked_mode' => 'stub',
        'required_gate_id' => $gate->id,
        'sort' => 0,
    ]);

    config()->set('series-wiki.spoilers.stub_text', 'SPOILER STUB');

    $renderer = app(\Searsandrew\SeriesWiki\Services\EntryRenderer::class);

    // No user => can't view gated content => locked
    $out = $renderer->renderWithContext($entry, null, new YearRange(4200, 4200), null);

    expect($out)->toHaveCount(1);

    $item = $out[0];

    expect($item['is_locked'])->toBeTrue();
    expect($item['locked_mode'])->toBe('stub');

    // Stub shows text, but must not include payload
    expect($item['display']['text'])->toBe('SPOILER STUB');
    expect($item['display']['payload'])->toBeNull();
});

it('uses data.safe as a safe payload for non-text blocks when locked', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'map-with-safe',
        'title' => 'Map With Safe',
        'type' => 'page',
        'status' => 'published',
    ]);

    $work = \Searsandrew\SeriesWiki\Models\Work::create([
        'series_id' => $series->id,
        'slug' => 'book-a',
        'title' => 'Book A',
        'kind' => 'book',
    ]);

    $gate = \Searsandrew\SeriesWiki\Models\Gate::create([
        'work_id' => $work->id,
        'key' => '1',
        'position' => 1,
        'label' => 'Chapter 1',
    ]);

    Block::create([
        'owner_type' => 'entry',
        'owner_id' => $entry->id,
        'key' => 'map',
        'label' => 'Map',
        'format' => 'json',
        'type' => 'map',
        'data' => [
            'title' => 'Spoiler Map',
            'asset_id' => 'asset-full',
            'pins' => [
                ['x' => 0.7, 'y' => 0.8, 'label' => 'Secret Base', 'entry_slug' => 'secret-base'],
            ],
            // Safe payload override (what locked readers can see)
            'safe' => [
                'title' => 'Public Map',
                'asset_id' => 'asset-safe',
                'pins' => [], // no secret pins
            ],
        ],
        'locked_mode' => 'safe',
        'required_gate_id' => $gate->id,
        'body_safe' => 'A public overview of this map.',
        'sort' => 0,
    ]);

    $renderer = app(\Searsandrew\SeriesWiki\Services\EntryRenderer::class);

    // No user => locked
    $out = $renderer->renderWithContext($entry, null, new YearRange(4200, 4200), null);

    expect($out)->toHaveCount(1);

    $item = $out[0];

    expect($item['is_locked'])->toBeTrue();
    expect($item['locked_mode'])->toBe('safe');

    // Safe mode should show safe text + safe payload (not full)
    expect($item['display']['text'])->toBe('A public overview of this map.');
    expect($item['display']['payload'])->not->toBeNull();
    expect($item['display']['payload']['asset_id'])->toBe('asset-safe');
    expect($item['display']['payload']['pins'])->toBe([]);
});