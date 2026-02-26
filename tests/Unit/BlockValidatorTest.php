<?php

use Searsandrew\SeriesWiki\Models\Block;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Services\Blocks\BlockValidator;

it('validates a map block payload', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'atlas',
        'title' => 'Atlas',
        'type' => 'page',
        'status' => 'published',
    ]);

    $block = Block::create([
        'owner_type' => 'entry',
        'owner_id' => $entry->id,
        'key' => 'map',
        'type' => 'map',
        'format' => 'json',
        'data' => [
            'asset_id' => 'asset-123',
            'pins' => [
                ['x' => 0.25, 'y' => 0.4, 'label' => 'North Shore'],
            ],
        ],
        'sort' => 0,
    ]);

    $validator = app(BlockValidator::class);

    $validator->validate($block); // should not throw
    expect(true)->toBeTrue();
});

it('rejects an image block without asset_id or src', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'pic',
        'title' => 'Pic',
        'type' => 'page',
        'status' => 'published',
    ]);

    $block = Block::create([
        'owner_type' => 'entry',
        'owner_id' => $entry->id,
        'key' => 'image',
        'type' => 'image',
        'format' => 'json',
        'data' => [
            'caption' => 'hello',
        ],
        'sort' => 0,
    ]);

    $validator = app(BlockValidator::class);

    $thrown = false;
    try {
        $validator->validate($block);
    } catch (\Illuminate\Validation\ValidationException $e) {
        $thrown = true;
        expect($e->errors())->toHaveKey('data.asset_id');
    }

    expect($thrown)->toBeTrue();
});

it('allows custom block types via config', function () {
    config()->set('series-wiki.blocks.types', [
        'video' => [
            'data' => [
                'url' => 'required|string|max:2000',
                'caption' => 'sometimes|string|max:500',
            ],
        ],
    ]);

    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'vid',
        'title' => 'Vid',
        'type' => 'page',
        'status' => 'published',
    ]);

    $block = Block::create([
        'owner_type' => 'entry',
        'owner_id' => $entry->id,
        'key' => 'clip',
        'type' => 'video',
        'format' => 'json',
        'data' => [
            'url' => 'https://example.com/video.mp4',
        ],
        'sort' => 0,
    ]);

    $validator = app(\Searsandrew\SeriesWiki\Services\Blocks\BlockValidator::class);
    $validator->validate($block);

    expect(true)->toBeTrue();
});