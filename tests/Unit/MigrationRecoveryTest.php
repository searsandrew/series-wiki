<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Searsandrew\SeriesWiki\Models\Gate;
use Searsandrew\SeriesWiki\Models\Series;
use Searsandrew\SeriesWiki\Models\Template;
use Searsandrew\SeriesWiki\Models\TemplateSection;
use Searsandrew\SeriesWiki\Models\Work;

function withSeriesWikiMigrationDatabase(Closure $test): void
{
    $database = getenv('SERIES_WIKI_MYSQL_DATABASE');
    if (! is_string($database) || ! str_starts_with($database, 'series_wiki_test_')) {
        throw new RuntimeException('Migration tests require a dedicated series_wiki_test_ database.');
    }
    $originalConnection = DB::getDefaultConnection();
    $originalSchema = Schema::getFacadeRoot();
    config(['database.connections.migration_mysql' => [
        'driver' => 'mysql',
        'host' => getenv('SERIES_WIKI_MYSQL_HOST') ?: '127.0.0.1',
        'port' => getenv('SERIES_WIKI_MYSQL_PORT') ?: '3306',
        'database' => $database,
        'username' => getenv('SERIES_WIKI_MYSQL_USERNAME') ?: 'root',
        'password' => getenv('SERIES_WIKI_MYSQL_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ]]);
    DB::setDefaultConnection('migration_mysql');
    Schema::swap(DB::connection()->getSchemaBuilder());
    try {
        Schema::dropAllTables();
        $paths = glob(__DIR__.'/../../database/migrations/*.php');
        sort($paths);
        $test($paths);
    } finally {
        Schema::dropAllTables();
        Schema::swap($originalSchema);
        DB::setDefaultConnection($originalConnection);
        DB::purge('migration_mysql');
    }
}

it('migrates and rolls back a clean MySQL database with an enforced template gate foreign key', function () {
    withSeriesWikiMigrationDatabase(function (array $paths): void {
        foreach ($paths as $path) {
            (require $path)->up();
        }
        $series = Series::create(['slug' => 'mysql-test', 'name' => 'MySQL test']);
        $template = Template::create(['series_id' => $series->id, 'slug' => 'default', 'name' => 'Default']);
        $work = Work::create(['series_id' => $series->id, 'slug' => 'book', 'title' => 'Book']);
        $gate = Gate::create(['work_id' => $work->id, 'key' => '1', 'position' => 1, 'label' => 'Chapter 1']);
        $section = TemplateSection::create(['template_id' => $template->id, 'key' => 'history', 'required_gate_id' => $gate->id]);
        expect(fn () => TemplateSection::create(['template_id' => $template->id, 'key' => 'invalid', 'required_gate_id' => (string) Str::ulid()]))
            ->toThrow(QueryException::class);
        $gate->delete();
        expect($section->fresh()->required_gate_id)->toBeNull();
        foreach (array_reverse($paths) as $path) {
            (require $path)->down();
        }
        expect(Schema::hasTable('sw_template_sections'))->toBeFalse()
            ->and(Schema::hasTable('sw_gates'))->toBeFalse();
    });
})->skip(fn (): bool => ! getenv('SERIES_WIKI_MYSQL_DATABASE'), 'Requires the isolated MySQL migration test database.');

it('resumes a partially failed MySQL migration without dropping existing template sections', function () {
    withSeriesWikiMigrationDatabase(function (array $paths): void {
        foreach (array_slice($paths, 0, 3) as $path) {
            (require $path)->up();
        }
        Schema::table('sw_template_sections', function (Blueprint $table): void {
            $table->index('template_id');
            $table->dropIndex('sw_template_sections_sort_index');
            $table->dropIndex('sw_template_sections_template_id_key_sort_index');
        });
        expect(fn () => Schema::table('sw_template_sections', function (Blueprint $table): void {
            $table->foreign('required_gate_id')->references('id')->on('sw_gates')->nullOnDelete();
        }))->toThrow(QueryException::class);
        expect(Schema::hasTable('sw_template_sections'))->toBeTrue();
        $series = Series::create(['slug' => 'recovery-test', 'name' => 'Recovery test']);
        $template = Template::create(['series_id' => $series->id, 'slug' => 'default', 'name' => 'Default']);
        $section = TemplateSection::create(['template_id' => $template->id, 'key' => 'history', 'body_full' => 'Preserve existing content']);
        foreach (array_slice($paths, 2) as $path) {
            (require $path)->up();
        }
        expect($section->fresh()->body_full)->toBe('Preserve existing content');
        $indexes = array_column(Schema::getIndexes('sw_template_sections'), 'name');
        expect($indexes)->toContain('sw_template_sections_sort_index', 'sw_template_sections_template_id_key_sort_index');
        $gateKeys = collect(Schema::getForeignKeys('sw_template_sections'))->where('foreign_table', 'sw_gates');
        expect($gateKeys)->toHaveCount(1);
        (require $paths[2])->up();
        (require end($paths))->up();
        expect(collect(Schema::getForeignKeys('sw_template_sections'))->where('foreign_table', 'sw_gates'))->toHaveCount(1);
    });
})->skip(fn (): bool => ! getenv('SERIES_WIKI_MYSQL_DATABASE'), 'Requires the isolated MySQL migration test database.');

it('keeps an existing template gate constraint when the repair migration runs again', function () {
    $migration = require __DIR__.'/../../database/migrations/2026_11_11_224012_add_template_section_gate_foreign_key.php';
    $before = Schema::getForeignKeys('sw_template_sections');
    $migration->up();
    expect(Schema::getForeignKeys('sw_template_sections'))->toBe($before);
});
