<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Statamic\Assets\Asset as StatamicAsset;
use Statamic\Assets\AssetContainer as StatamicAssetContainer;
use Statamic\Entries\Entry as StatamicEntry;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;
use UAMSWeb\Aether\Tables\Listeners\HandleTableDataImporterTrigger;
use UAMSWeb\Aether\Tables\Tests\Support\Doubles\TriggerActivated;

/**
 * Runs the importer against a real asset container, asset and entry, with nothing mocked.
 *
 * `HandleTableDataImporterTriggerTest` supplies the asset and the entry through doubles, so
 * a change in how Statamic resolves an asset id, reports its extension and path, or stores
 * a saved entry would pass that suite. These tests keep every Statamic store in a temporary
 * directory that belongs to the running worker, so parallel workers never share one.
 *
 * @covers \UAMSWeb\Aether\Tables\Listeners\HandleTableDataImporterTrigger
 *
 * @command  vendor/bin/pest tests/Integration/Tables/HandleTableDataImporterTriggerRealStatamicTest.php
 */

/**
 * The temporary directory for this worker, made on first use and removed with `$remove`.
 */
function importerScratch(bool $remove = false): string
{
    static $dir = null;

    // Remove the directory made so far
    if ($remove) {
        if ($dir !== null) {
            (new Filesystem)->deleteDirectory($dir);
        }

        $dir = null;

        return '';
    }

    // Make a directory unique to this worker and test
    if ($dir === null) {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'importer-real-'.uniqid('', true);
        mkdir($dir, 0755, true);
    }

    return $dir;
}

/**
 * Move every Statamic store into the scratch directory and make the real container.
 *
 * The container's `table_spreadsheets` disk is a local directory holding the CSV fixture
 * the tests read.
 */
function bootImporterStores(): void
{
    $scratch = importerScratch();

    // Keep what Statamic saves out of the repository
    Stache::stores()->each(function ($store) use ($scratch): void {
        $store->directory($scratch.'/stache/'.$store->key());
    });

    // Give the container a disk that holds the fixture
    $disk = $scratch.'/disk';
    mkdir($disk, 0755, true);
    copy(__DIR__.'/../../Fixtures/spreadsheets/table.csv', $disk.'/table.csv');
    file_put_contents($disk.'/notes.txt', 'not a spreadsheet');
    config(['filesystems.disks.importer_real_disk' => ['driver' => 'local', 'root' => $disk]]);

    // Save the container the importer is pinned to
    $container = AssetContainer::make('table_spreadsheets');
    if (! $container instanceof StatamicAssetContainer) {
        throw new LogicException('Statamic made no asset container.');
    }

    $container->disk('importer_real_disk');
    $container->save();
}

afterEach(function (): void {
    importerScratch(true);
});

/**
 * The trigger event for one form value, unique per call so the importer's duplicate guard never fires.
 *
 * @param  ?StatamicEntry  $entry  The entry, or null
 */
function importerEvent(?StatamicEntry $entry, string $assetId): TriggerActivated
{
    return new TriggerActivated($entry, 'table_data_importer_trigger', ['table_data_importer' => $assetId, 'nonce' => uniqid('', true)]);
}

/**
 * Save a real entry in a real collection and return it.
 *
 * @param  array<string, mixed>  $data  The entry's data
 */
function importerSavedEntry(string $slug, array $data): StatamicEntry
{
    Collection::make('pages')->save();

    $entry = Entry::make();
    if (! $entry instanceof StatamicEntry) {
        throw new LogicException('Statamic made no entry.');
    }

    $entry->collection('pages');
    $entry->slug($slug);
    $entry->data($data);
    $entry->save();

    return $entry;
}

/**
 * A field of a saved entry, read from its file on disk.
 *
 * The Stache hands back the object it already holds, so only the file shows what was saved.
 */
function importerSavedField(StatamicEntry $entry, string $field): mixed
{
    $path = $entry->path();
    if (! is_string($path)) {
        throw new LogicException('The saved entry has no file path.');
    }

    $stored = YAML::file($path)->parse();

    return $stored[$field] ?? null;
}

/**
 * The rows the fixture holds, as the importer stores them: every cell a string.
 *
 * @return list<array{cells: list<string>}>
 */
function importerExpectedTableData(): array
{
    return [
        ['cells' => ['Name', 'Count', 'Ratio', 'Code', 'Note']],
        ['cells' => ['Alpha', '3', '1.5', '007', '']],
        ['cells' => ['Beta', '', '0.25', 'x1', 'with, comma']],
    ];
}

it('reads a real asset from a real container and returns the rows', function (string $assetId): void {
    bootImporterStores();

    $result = (new HandleTableDataImporterTrigger)->handle(importerEvent(null, $assetId));

    // The bare id and the prefixed id both resolve in table_spreadsheets
    expect($result)->toBe(['table_data' => importerExpectedTableData()]);
})->with([
    'bare id' => ['table.csv'],
    'prefixed id' => ['table_spreadsheets::table.csv'],
]);

it('resolves the real asset the importer reads', function (): void {
    bootImporterStores();

    $asset = Asset::find('table_spreadsheets::table.csv');
    if (! $asset instanceof StatamicAsset) {
        throw new LogicException('The container resolved no asset.');
    }

    // The two accessors the importer calls on the asset
    expect($asset->extension())->toBe('csv')
        ->and(str_replace('\\', '/', $asset->resolvedPath()))->toBe(str_replace('\\', '/', importerScratch()).'/disk/table.csv');
});

it('logs and returns nothing for an asset that is not a spreadsheet', function (): void {
    bootImporterStores();
    $log = Log::spy();

    $result = (new HandleTableDataImporterTrigger)->handle(importerEvent(null, 'notes.txt'));

    expect($result)->toBe([]);
    $log->shouldHaveReceived('error')
        ->with('HandleTableDataImporterTrigger::handle: Asset not found or not a supported format', ['asset_id' => 'table_spreadsheets::notes.txt'])
        ->once();
});

it('logs and returns nothing for an asset that does not exist', function (): void {
    bootImporterStores();
    $log = Log::spy();

    $result = (new HandleTableDataImporterTrigger)->handle(importerEvent(null, 'missing.csv'));

    expect($result)->toBe([]);
    $log->shouldHaveReceived('error')
        ->with('HandleTableDataImporterTrigger::handle: Asset not found or not a supported format', ['asset_id' => 'table_spreadsheets::missing.csv'])
        ->once();
});

it('logs and returns nothing for a traversal id', function (string $assetId): void {
    bootImporterStores();
    $log = Log::spy();

    $result = (new HandleTableDataImporterTrigger)->handle(importerEvent(null, $assetId));

    // Nothing is returned, and the resolution failure is caught and logged rather than raised
    expect($result)->toBe([]);
    $log->shouldHaveReceived('error')
        ->withArgs(fn (string $message): bool => str_starts_with($message, 'HandleTableDataImporterTrigger::handle: Error replacing table data: '))
        ->once();
})->with([
    'in-container traversal' => ['table_spreadsheets::../x.csv'],
    'bare traversal' => ['../x.csv'],
    'deep traversal' => ['table_spreadsheets::../../../etc/passwd.csv'],
]);

it('refuses a foreign container before touching the real container', function (): void {
    bootImporterStores();
    $log = Log::spy();

    $result = (new HandleTableDataImporterTrigger)->handle(importerEvent(null, 'other_container::table.csv'));

    expect($result)->toBe([]);
    $log->shouldHaveReceived('warning')
        ->with('HandleTableDataImporterTrigger::handle: Refusing asset id outside the intended container', ['intended_container' => 'table_spreadsheets', 'requested_container' => 'other_container'])
        ->once();
});

it('saves the processed rows to a real entry', function (): void {
    bootImporterStores();
    $entry = importerSavedEntry('importer', ['title' => 'Importer', 'table_data' => [['cells' => ['stale']]]]);

    $result = (new HandleTableDataImporterTrigger)->handle(importerEvent($entry, 'table.csv'));

    // The returned rows, the entry's rows and the saved file's rows are the same, and the old rows are replaced
    expect($result)->toBe(['table_data' => importerExpectedTableData()])
        ->and($entry->get('table_data'))->toBe(importerExpectedTableData())
        ->and(importerSavedField($entry, 'table_data'))->toBe(importerExpectedTableData())
        ->and(importerSavedField($entry, 'title'))->toBe('Importer');
});

it('leaves a real entry untouched when the asset is refused', function (): void {
    bootImporterStores();
    $entry = importerSavedEntry('untouched', ['title' => 'Untouched', 'table_data' => [['cells' => ['kept']]]]);

    $result = (new HandleTableDataImporterTrigger)->handle(importerEvent($entry, 'table_spreadsheets::../x.csv'));

    expect($result)->toBe([])
        ->and(importerSavedField($entry, 'table_data'))->toBe([['cells' => ['kept']]]);
});
