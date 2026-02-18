<?php

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryBlock;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\Template;
use Searsandrew\SeriesWiki\Models\TemplateSection;
use Searsandrew\SeriesWiki\Services\TemplateApplier;

it('creates missing blocks from a template without overwriting existing blocks', function () {
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

    TemplateSection::create([
        'template_id' => $template->id,
        'key' => 'biology',
        'label' => 'Biology',
        'format' => 'markdown',
        'body_safe' => 'Start biology…',
        'body_full' => 'Full biology…',
        'sort' => 10,
    ]);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ogris',
        'title' => 'Ogris',
        'type' => 'species',
        'status' => 'published',
    ]);

    // Pre-existing overview block (custom content)
    EntryBlock::create([
        'entry_id' => $entry->id,
        'key' => 'overview',
        'label' => 'Overview',
        'format' => 'markdown',
        'body_safe' => 'Custom safe',
        'body_full' => 'Custom full',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    $applier = app(TemplateApplier::class);

    $touched = $applier->apply($entry); // resolved by entry type default

    // Should create only missing (biology)
    expect($touched)->toHaveCount(1);
    expect($touched->first()->key)->toBe('biology');

    $overview = EntryBlock::query()->where('entry_id', $entry->id)->where('key', 'overview')->firstOrFail();
    expect($overview->body_full)->toBe('Custom full');

    $biology = EntryBlock::query()->where('entry_id', $entry->id)->where('key', 'biology')->firstOrFail();
    expect($biology->body_safe)->toBe('Start biology…');
});

it('overwrites existing blocks when overwrite is true', function () {
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
        'body_safe' => 'Template safe',
        'body_full' => 'Template full',
        'sort' => 0,
    ]);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ogris',
        'title' => 'Ogris',
        'type' => 'species',
        'status' => 'published',
    ]);

    EntryBlock::create([
        'entry_id' => $entry->id,
        'key' => 'overview',
        'label' => 'Overview',
        'format' => 'markdown',
        'body_safe' => 'Custom safe',
        'body_full' => 'Custom full',
        'locked_mode' => 'safe',
        'sort' => 999,
    ]);

    $applier = app(TemplateApplier::class);
    $touched = $applier->apply($entry, overwrite: true);

    expect($touched)->toHaveCount(1);

    $overview = EntryBlock::query()->where('entry_id', $entry->id)->where('key', 'overview')->firstOrFail();
    expect($overview->body_full)->toBe('Template full');
    expect($overview->sort)->toBe(0);
});