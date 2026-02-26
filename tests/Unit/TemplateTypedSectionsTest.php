<?php

use Searsandrew\SeriesWiki\Models\Block;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\Template;
use Searsandrew\SeriesWiki\Models\TemplateSection;
use Searsandrew\SeriesWiki\Services\TemplateApplier;

it('creates typed blocks from template sections', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $template = Template::create([
        'series_id' => $series->id,
        'slug' => 'planet-default',
        'name' => 'Planet (Default)',
        'entry_type' => 'planet',
        'is_default' => true,
    ]);

    TemplateSection::create([
        'template_id' => $template->id,
        'key' => 'overview',
        'label' => 'Overview',
        'format' => 'markdown',
        'type' => 'text',
        'body_full' => 'Write an overview…',
        'sort' => 0,
    ]);

    TemplateSection::create([
        'template_id' => $template->id,
        'key' => 'map',
        'label' => 'Map',
        'format' => 'json',
        'type' => 'map',
        'data' => [
            'asset_id' => 'asset-planet-map',
            'caption' => 'Planetary map',
        ],
        'sort' => 10,
    ]);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'entele',
        'title' => 'Entele',
        'type' => 'planet',
        'status' => 'published',
    ]);

    $applier = app(TemplateApplier::class);
    $applier->apply($entry, $template);

    $blocks = Block::query()
        ->where('owner_type', 'entry')
        ->where('owner_id', $entry->id)
        ->orderBy('sort')
        ->get();

    expect($blocks)->toHaveCount(2);
    expect($blocks[0]->type)->toBe('text');
    expect($blocks[1]->type)->toBe('map');
    expect($blocks[1]->data['asset_id'])->toBe('asset-planet-map');
});