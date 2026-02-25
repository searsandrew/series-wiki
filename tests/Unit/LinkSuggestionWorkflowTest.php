<?php

use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryBlock;
use Searsandrew\SeriesWiki\Models\EntrySnapshot;
use Searsandrew\SeriesWiki\Models\LinkSuggestion;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Services\Crawler\LinkSuggestionWorkflow;

it('accepts and dismisses suggestions', function () {
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

    $s = LinkSuggestion::create([
        'entry_id' => $source->id,
        'block_key' => 'overview',
        'suggested_entry_id' => $target->id,
        'anchor_text' => 'Battle X',
        'occurrences' => 1,
        'confidence' => 0.7,
        'status' => 'new',
        'meta' => ['phrase' => 'Battle X'],
    ]);

    $wf = app(LinkSuggestionWorkflow::class);

    $wf->dismiss($s->fresh());
    expect($s->fresh()->status)->toBe('dismissed');

    $wf->accept($s->fresh());
    expect($s->fresh()->status)->toBe('accepted');
});

it('applies a suggestion by inserting a markdown link and marks it accepted', function () {
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

    $block = EntryBlock::create([
        'entry_id' => $source->id,
        'key' => 'overview',
        'body_full' => 'Battle X was decisive.',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    // snapshot + hash for staleness check
    $hash = hash('sha256', "overview:\nBattle X was decisive.");
    EntrySnapshot::create([
        'entry_id' => $source->id,
        'hash' => $hash,
        'text' => "overview:\nBattle X was decisive.",
    ]);

    $s = LinkSuggestion::create([
        'entry_id' => $source->id,
        'block_key' => 'overview',
        'suggested_entry_id' => $target->id,
        'anchor_text' => 'Battle X',
        'occurrences' => 1,
        'confidence' => 0.7,
        'status' => 'new',
        'snapshot_hash' => $hash,
        'meta' => ['phrase' => 'Battle X'],
    ]);

    $wf = app(LinkSuggestionWorkflow::class);

    $result = $wf->applyToBlock($s);

    expect($result['applied'])->toBeTrue();

    $block->refresh();
    expect($block->body_full)->toBe('[Battle X](/wiki/battle-x) was decisive.');

    $s->refresh();
    expect($s->status)->toBe('accepted');
});

it('refuses to apply a stale suggestion when snapshot hash differs', function () {
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
        'body_full' => 'Battle X was decisive.',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    // Latest snapshot hash doesn't match suggestion hash
    EntrySnapshot::create([
        'entry_id' => $source->id,
        'hash' => hash('sha256', 'changed'),
        'text' => 'changed',
    ]);

    $s = LinkSuggestion::create([
        'entry_id' => $source->id,
        'block_key' => 'overview',
        'suggested_entry_id' => $target->id,
        'anchor_text' => 'Battle X',
        'occurrences' => 1,
        'confidence' => 0.7,
        'status' => 'new',
        'snapshot_hash' => hash('sha256', 'old'),
        'meta' => ['phrase' => 'Battle X'],
    ]);

    $wf = app(LinkSuggestionWorkflow::class);

    $result = $wf->applyToBlock($s);

    expect($result['applied'])->toBeFalse();
    expect($result['reason'])->toBe('Suggestion is stale (entry changed since crawl)');
});