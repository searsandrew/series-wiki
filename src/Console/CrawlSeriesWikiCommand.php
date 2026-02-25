<?php

namespace Searsandrew\SeriesWiki\Console;

use Illuminate\Console\Command;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Services\Crawler\LinkSuggestionEngine;

class CrawlSeriesWikiCommand extends Command
{
    protected $signature = 'series-wiki:crawl
        {--series= : Series slug to crawl}
        {--limit=0 : Limit number of entries scanned (0 = all)}
        {--dry-run : Do not write snapshots/suggestions}';

    protected $description = 'Crawl a series wiki and generate internal link suggestions.';

    public function handle(LinkSuggestionEngine $engine): int
    {
        $slug = (string) ($this->option('series') ?? '');

        if ($slug === '') {
            $this->error('Missing required option: --series=<series-slug>');
            return self::FAILURE;
        }

        $series = Series::query()->where('slug', $slug)->first();

        if (! $series) {
            $this->error("Series not found for slug: {$slug}");
            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $dry = (bool) $this->option('dry-run');

        $stats = $engine->crawlSeries($series, $limit, $dry);

        $this->line("Entries scanned: {$stats['entries_scanned']}");
        $this->line("Entries skipped (unchanged): {$stats['entries_skipped_unchanged']}");
        $this->line("Suggestions created: {$stats['suggestions_created']}");
        $this->line($dry ? 'Dry run: no writes' : 'Wrote snapshots + suggestions');

        return self::SUCCESS;
    }
}