<?php

use Searsandrew\SeriesWiki\Models\Block;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\Template;
use Searsandrew\SeriesWiki\Models\TemplateSection;
use Searsandrew\SeriesWiki\Services\EntryCreator;

it('creates an entry and applies the default template for its type', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $template = Template::create([
        'series_id' => $series->id,
        'slug' => 'species-default',
        'name' => 'Species (Default)',
        'entry_type' => 'species',
        'is_default' => true,
    ]);

    TemplateSection::create([
        'template_id' => $template->id,
        'key' => 'overview',
        'label' => 'Overview',
        'format' => 'markdown',
        'body_safe' => 'Start overview…',
        'body_full' => 'Full overview…',
        'sort' => 0,
    ]);

    $creator = app(EntryCreator::class);

    $entry = $creator->create([
        'series_id' => $series->id,
        'slug' => 'ogris',
        'title' => 'Ogris',
        'type' => 'species',
        'status' => 'published',
    ]);

    expect($entry->template_id)->toBe($template->id);
    expect(Block::query()->where('owner_type', 'entry')->where('owner_id', $entry->id)->count())->toBe(1);
    expect($entry->blocks->first()->key)->toBe('overview');
});

it('respects an explicitly provided template_id', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $templateA = Template::create([
        'series_id' => $series->id,
        'slug' => 'species-default',
        'name' => 'Species (Default)',
        'entry_type' => 'species',
        'is_default' => true,
    ]);

    $templateB = Template::create([
        'series_id' => $series->id,
        'slug' => 'species-alt',
        'name' => 'Species (Alt)',
        'entry_type' => 'species',
        'is_default' => false,
    ]);

    TemplateSection::create([
        'template_id' => $templateB->id,
        'key' => 'biology',
        'label' => 'Biology',
        'format' => 'markdown',
        'body_safe' => 'Bio safe…',
        'body_full' => 'Bio full…',
        'sort' => 0,
    ]);

    $creator = app(EntryCreator::class);

    $entry = $creator->create([
        'series_id' => $series->id,
        'template_id' => $templateB->id,
        'slug' => 'ogris',
        'title' => 'Ogris',
        'type' => 'species',
        'status' => 'published',
    ]);

    expect($entry->template_id)->toBe($templateB->id);
    expect($entry->blocks->first()->key)->toBe('biology');
});