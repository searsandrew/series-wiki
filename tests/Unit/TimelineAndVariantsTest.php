<?php

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryBlock;
use Searsandrew\SeriesWiki\Models\EntryVariant;
use Searsandrew\SeriesWiki\Models\Faction;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\TimeSlice;
use Searsandrew\SeriesWiki\Models\UserWorkProgress;
use Searsandrew\SeriesWiki\Models\VariantBlock;
use Searsandrew\SeriesWiki\Models\Work;
use Searsandrew\SeriesWiki\Services\EntryRenderer;
use Searsandrew\SeriesWiki\Services\Timeline\YearRange;

class TVUser extends AuthenticatableUser
{
    use HasUlids;

    protected $table = 'users';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
}

it('treats blocks with no time tags as always relevant', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

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
        'body_safe' => 'safe',
        'body_full' => 'full',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    $renderer = app(EntryRenderer::class);

    $out = $renderer->renderWithContext($entry, null, new YearRange(4200, 4200), null);

    expect($out)->toHaveCount(1);
    expect($out[0]['key'])->toBe('overview');
});

it('filters tagged blocks by year range intersection', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $slice = TimeSlice::create([
        'series_id' => $series->id,
        'slug' => 'stellar-era',
        'name' => 'Stellar Era',
        'kind' => 'era',
        'start_year' => 4200,
        'end_year' => 4300,
        'sort' => 0,
    ]);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'ancient-being',
        'title' => 'Ancient Being',
        'type' => 'lore',
        'status' => 'published',
    ]);

    $always = EntryBlock::create([
        'entry_id' => $entry->id,
        'key' => 'myth',
        'body_safe' => 'myth safe',
        'body_full' => 'myth full',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    $tagged = EntryBlock::create([
        'entry_id' => $entry->id,
        'key' => 'truth',
        'body_safe' => 'truth safe',
        'body_full' => 'truth full',
        'locked_mode' => 'safe',
        'sort' => 10,
    ]);

    // Tag only the "truth" block
    $tagged->timeSlices()->attach($slice->id);

    $renderer = app(EntryRenderer::class);

    // Outside range => only untagged block visible
    $out1 = $renderer->renderWithContext($entry, null, new YearRange(4100, 4199), null);
    expect(collect($out1)->pluck('key')->all())->toBe(['myth']);

    // Intersecting range => both visible
    $out2 = $renderer->renderWithContext($entry, null, new YearRange(4201, 4201), null);
    expect(collect($out2)->pluck('key')->all())->toBe(['myth', 'truth']);
});

it('applies variant overrides by key and still respects timeline filtering', function () {
    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $slice = TimeSlice::create([
        'series_id' => $series->id,
        'slug' => 'war-era',
        'name' => 'War Era',
        'kind' => 'era',
        'start_year' => 4250,
        'end_year' => 4260,
        'sort' => 0,
    ]);

    $faction = Faction::create([
        'series_id' => $series->id,
        'slug' => 'republic',
        'name' => 'Republic',
        'sort' => 0,
    ]);

    $entry = Entry::create([
        'series_id' => $series->id,
        'slug' => 'type-88',
        'title' => 'Type-88 Destroyer',
        'type' => 'ship',
        'status' => 'published',
    ]);

    // Base block
    EntryBlock::create([
        'entry_id' => $entry->id,
        'key' => 'overview',
        'body_safe' => 'base safe',
        'body_full' => 'base full',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    // Base tagged wartime block
    $baseWar = EntryBlock::create([
        'entry_id' => $entry->id,
        'key' => 'wartime_use',
        'body_safe' => 'base war safe',
        'body_full' => 'base war full',
        'locked_mode' => 'safe',
        'sort' => 10,
    ]);
    $baseWar->timeSlices()->attach($slice->id);

    $variant = EntryVariant::create([
        'entry_id' => $entry->id,
        'faction_id' => $faction->id,
        'variant_key' => 'republic',
        'label' => 'Republic View',
        'is_default' => false,
        'sort' => 0,
    ]);

    // Variant overrides overview
    VariantBlock::create([
        'variant_id' => $variant->id,
        'key' => 'overview',
        'body_safe' => 'variant safe',
        'body_full' => 'variant full',
        'locked_mode' => 'safe',
        'sort' => 0,
    ]);

    // Variant-only wartime block (also tagged)
    $variantWar = VariantBlock::create([
        'variant_id' => $variant->id,
        'key' => 'wartime_use',
        'body_safe' => 'variant war safe',
        'body_full' => 'variant war full',
        'locked_mode' => 'safe',
        'sort' => 10,
    ]);
    $variantWar->timeSlices()->attach($slice->id);

    $renderer = app(EntryRenderer::class);

    // Outside war era => wartime_use filtered out; overview should come from variant
    $out1 = $renderer->renderWithContext($entry, null, new YearRange(4200, 4201), 'republic');
    expect(collect($out1)->pluck('key')->all())->toBe(['overview']);
    expect($out1[0]['body'])->toBe('variant full');

    // Inside war era => wartime_use visible, and should be variant override
    $out2 = $renderer->renderWithContext($entry, null, new YearRange(4255, 4255), 'republic');
    expect(collect($out2)->pluck('key')->all())->toBe(['overview', 'wartime_use']);
    expect($out2[1]['body'])->toBe('variant war full');
});

it('still honors spoiler gates after variant + timeline composition', function () {
    $user = TVUser::query()->create(['name' => 'Reader']);

    $series = Series::create(['slug' => 'stellar-empire', 'name' => 'Stellar Empire']);

    $work = Work::create([
        'series_id' => $series->id,
        'slug' => 'bones-that-remember',
        'title' => 'The Bones That Remember',
        'kind' => 'novella',
    ]);

    $gate = Gate::create([
        'work_id' => $work->id,
        'key' => '1',
        'position' => 1,
        'label' => 'Chapter 1',
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
        'key' => 'truth',
        'body_safe' => 'safe truth',
        'body_full' => 'full truth',
        'locked_mode' => 'safe',
        'required_gate_id' => $gate->id,
        'sort' => 0,
    ]);

    $renderer = app(EntryRenderer::class);

    // No progress: locked => safe shown
    $out1 = $renderer->renderWithContext($entry, $user, new YearRange(4200, 4200), null);
    expect($out1[0]['is_locked'])->toBeTrue();
    expect($out1[0]['body'])->toBe('safe truth');

    UserWorkProgress::create([
        'user_id' => (string) $user->getAuthIdentifier(),
        'work_id' => $work->id,
        'max_gate_position' => 1,
    ]);

    // With progress: unlocked => full shown
    $out2 = $renderer->renderWithContext($entry, $user, new YearRange(4200, 4200), null);
    expect($out2[0]['is_locked'])->toBeFalse();
    expect($out2[0]['body'])->toBe('full truth');
});