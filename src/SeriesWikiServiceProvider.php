<?php

namespace Searsandrew\SeriesWiki;

use Illuminate\Support\ServiceProvider;
use Searsandrew\SeriesWiki\Console\CrawlSeriesWikiCommand;
use Searsandrew\SeriesWiki\Services\ContemporaryService;
use Searsandrew\SeriesWiki\Services\Crawler\LinkSuggestionEngine;
use Searsandrew\SeriesWiki\Services\Crawler\LinkSuggestionWorkflow;
use Searsandrew\SeriesWiki\Services\EntryCreator;
use Searsandrew\SeriesWiki\Services\EntryRenderer;
use Searsandrew\SeriesWiki\Services\GateAccess;
use Searsandrew\SeriesWiki\Services\GateSeederService;
use Searsandrew\SeriesWiki\Services\ProgressService;
use Searsandrew\SeriesWiki\Services\TemplateApplier;
use Searsandrew\SeriesWiki\Services\TemplateResolver;
use Searsandrew\SeriesWiki\Services\Timeline\TimeSliceMatcher;
use Searsandrew\SeriesWiki\Services\Variants\VariantComposer;
use Searsandrew\SeriesWiki\Services\Variants\VariantResolver;

class SeriesWikiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/series-wiki.php', 'series-wiki');

        $this->app->singleton(GateAccess::class);
        $this->app->singleton(EntryRenderer::class);

        $this->app->singleton(ProgressService::class);
        $this->app->singleton(GateSeederService::class);

        $this->app->singleton(TemplateResolver::class);
        $this->app->singleton(TemplateApplier::class);

        $this->app->singleton(EntryCreator::class);

        $this->app->singleton(TimeSliceMatcher::class);
        $this->app->singleton(VariantResolver::class);
        $this->app->singleton(VariantComposer::class);

        $this->app->singleton(ContemporaryService::class);

        $this->app->singleton(LinkSuggestionEngine::class);
        $this->app->singleton(LinkSuggestionWorkflow::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/series-wiki.php' => $this->app->configPath('series-wiki.php'),
        ], 'series-wiki-config');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'series-wiki-migrations');
            $this->commands([
                CrawlSeriesWikiCommand::class,
            ]);
        }
    }
}