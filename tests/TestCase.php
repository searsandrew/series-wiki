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

        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($t) {
            $t->ulid('id')->primary();
            $t->string('name')->nullable();
            $t->timestamps();
        });
    }
}