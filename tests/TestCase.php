<?php

namespace Searsandrew\SeriesWiki\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Searsandrew\SeriesWiki\SeriesWikiServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SeriesWikiServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->app['db']->connection()->getSchemaBuilder()->create('sw_series', function ($t) {
            $t->id();
            $t->ulid('ulid')->unique();
            $t->string('slug')->unique();
            $t->string('name');
            $t->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('sw_entries', function ($t) {
            $t->id();
            $t->foreignId('series_id')->constrained('sw_series')->cascadeOnDelete();
            $t->string('slug');
            $t->string('title');
            $t->string('type')->default('page');
            $t->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('sw_entry_blocks', function ($t) {
            $t->id();
            $t->foreignId('entry_id')->constrained('sw_entries')->cascadeOnDelete();
            $t->string('key')->default('overview');
            $t->string('format')->default('markdown');
            $t->longText('body')->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });
    }
}