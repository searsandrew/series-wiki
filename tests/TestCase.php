<?php

namespace Searsandrew\SeriesWiki\Tests;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
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

    /**
     * Testbench 10+ uses this hook reliably.
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.env', 'testing');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Provide package config defaults so config('series-wiki.*') always works.
        $app['config']->set('series-wiki', [
            'spoilers' => [
                'default_locked_mode' => 'safe',
                'stub_text' => 'Spoiler content hidden. Continue reading to unlock this section.',
            ],
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        /**
         * CRITICAL: ensure Laravel helpers + facades resolve against THIS app instance.
         * This is what makes config(), app(), DB, etc. work in a package test context.
         */
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);

        /**
         * CRITICAL: ensure Eloquent has a connection resolver and event dispatcher.
         * Without these, Model::create() will explode with "connection() on null".
         */
        EloquentModel::setConnectionResolver($this->app['db']);
        EloquentModel::setEventDispatcher($this->app['events']);

        // Run package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Minimal users table for the FakeUser models in tests
        if (! Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->ulid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }
    }
}