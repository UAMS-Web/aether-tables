<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use UAMSWeb\Aether\Tables\Services\TableCellSanitizer;
use UAMSWeb\Aether\Tables\Tests\Support\Doubles\RecordingPurifier;

/**
 * Pins the sanitizer's state, logging and cache-directory decisions, which the
 * output-level suites cannot see: a mutant there leaves every purified string
 * unchanged. The purifier is replaced by a double so each branch is reached on
 * purpose, and the private state is read back by reflection.
 *
 * Equivalent mutants that survive, with the reason on each annotation in
 * `TableCellSanitizer`: the `Cache.DefinitionImpl` early return (the same purifier is
 * built either way) and `clearstatcache()` (PHP never caches an absent path).
 *
 * @command  vendor/bin/pest tests/Integration/Tables/TableCellSanitizerMutationTest.php
 */

/**
 * A sanitizer holding the given purifier and cache latch.
 */
function sanitizerHolding(HTMLPurifier $purifier, bool $abandoned): TableCellSanitizer
{
    $sanitizer = new TableCellSanitizer;

    new ReflectionProperty($sanitizer, 'purifier')->setValue($sanitizer, $purifier);
    new ReflectionProperty($sanitizer, 'definitionCacheAbandoned')->setValue($sanitizer, $abandoned);

    return $sanitizer;
}

/**
 * Read a private property of the sanitizer.
 */
function sanitizerState(TableCellSanitizer $sanitizer, string $property): mixed
{
    return new ReflectionProperty($sanitizer, $property)->getValue($sanitizer);
}

/**
 * Resolve the cache directory the way `purifier()` does.
 */
function preparedCachePath(TableCellSanitizer $sanitizer): ?string
{
    /** @var ?string $path */
    $path = new ReflectionMethod($sanitizer, 'prepareCacheDirectory')->invoke($sanitizer);

    return $path;
}

/**
 * A scratch directory under the system temp directory, unique to this worker.
 */
function sanitizerScratch(bool $remove = false): string
{
    static $made = [];

    // Remove every directory made so far
    if ($remove) {
        foreach ($made as $made_dir) {
            (new Filesystem)->deleteDirectory($made_dir);
        }

        $made = [];

        return '';
    }

    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sanitizer-mutation-'.uniqid('', true);
    mkdir($dir, 0755, true);
    $made[] = $dir;

    return $dir;
}

afterEach(function (): void {
    sanitizerScratch(true);
});

it('does not purify empty or non-scalar input', function (mixed $value): void {
    $purifier = new RecordingPurifier;

    expect(sanitizerHolding($purifier, true)->sanitize($value))->toBe('')
        ->and($purifier->calls)->toBe(0);
})->with([
    'empty string' => [''],
    'null' => [null],
    'array' => [['a']],
    'object' => [new stdClass],
]);

it('casts a scalar to a string before falling back', function (): void {
    Log::spy();

    // The latch is already set, so the first failure goes straight to the escaped fallback
    $sanitizer = sanitizerHolding(new RecordingPurifier('boom'), true);

    expect($sanitizer->sanitize(42))->toBe('42');
});

it('abandons the definition cache once, logs why, and purifies on the retry', function (): void {
    $log = Log::spy();
    $sanitizer = sanitizerHolding(new RecordingPurifier('cache broke'), false);

    $out = $sanitizer->sanitize('<b>x</b>');

    expect($out)->toBe('<b>x</b>')
        ->and(sanitizerState($sanitizer, 'definitionCacheAbandoned'))->toBeTrue();

    $log->shouldHaveReceived('warning')
        ->with('The table-cell sanitizer abandoned its HTMLPurifier definition cache after a failure; recompiling in-process.', ['reason' => 'cache broke'])
        ->once();
});

it('escapes the text and logs the reason when purification still fails', function (): void {
    $log = Log::spy();
    $sanitizer = sanitizerHolding(new RecordingPurifier('still broken'), true);

    // The tags are stripped before the text is escaped, so no markup can reach the page
    expect($sanitizer->sanitize('<script>x</script>hi & <b>'))->toBe('xhi &amp; ');

    $log->shouldHaveReceived('error')
        ->with('The table-cell sanitizer could not purify a cell; falling back to escaped text.', ['reason' => 'still broken'])
        ->once();
});

it('builds the purifier once and reuses it', function (): void {
    $sanitizer = new TableCellSanitizer;

    $sanitizer->sanitize('<b>a</b>');

    $first = sanitizerState($sanitizer, 'purifier');
    $sanitizer->sanitize('<b>b</b>');

    expect($first)->toBeInstanceOf(HTMLPurifier::class)
        ->and(sanitizerState($sanitizer, 'purifier'))->toBe($first)
        ->and(sanitizerState($sanitizer, 'definitionCacheAbandoned'))->toBeFalse();
});

it('compiles in-process and logs the path when the cache directory cannot be made', function (): void {
    $log = Log::spy();
    $scratch = sanitizerScratch();
    $blocker = $scratch.DIRECTORY_SEPARATOR.'blocker';
    file_put_contents($blocker, 'not a directory');
    $path = $blocker.DIRECTORY_SEPARATOR.'cache';
    config(['tables.cell_sanitizer.cache_path' => $path]);

    $sanitizer = new TableCellSanitizer;

    expect($sanitizer->sanitize('<b>x</b>'))->toBe('<b>x</b>')
        ->and(sanitizerState($sanitizer, 'definitionCacheAbandoned'))->toBeTrue();

    $purifier = sanitizerState($sanitizer, 'purifier');

    if (! $purifier instanceof HTMLPurifier || ! $purifier->config instanceof HTMLPurifier_Config) {
        throw new LogicException('The sanitizer built no purifier configuration.');
    }

    expect($purifier->config->get('Cache.DefinitionImpl'))->toBeNull();

    $log->shouldHaveReceived('warning')
        ->with('Could not create the table-cell sanitizer cache directory; HTMLPurifier will recompile its definition once per process.', ['path' => $path])
        ->once();
});

it('uses an existing cache directory without trying to create it', function (): void {
    $dir = sanitizerScratch();
    config(['tables.cell_sanitizer.cache_path' => $dir]);
    $files = File::partialMock();
    $files->shouldReceive('makeDirectory')->never();

    expect(preparedCachePath(new TableCellSanitizer))->toBe($dir);
});

it('creates a missing cache directory recursively with the expected mode', function (): void {
    // The directory exists on disk, so only the mocked answers decide the branch
    $dir = sanitizerScratch();
    config(['tables.cell_sanitizer.cache_path' => $dir]);
    $files = File::partialMock();
    $files->shouldReceive('isDirectory')->with($dir)->andReturn(false);
    $files->shouldReceive('makeDirectory')->once()->with($dir, 0755, true, true)->andReturn(true);

    expect(preparedCachePath(new TableCellSanitizer))->toBe($dir);
});

it('accepts a directory a sibling process created between the check and the create', function (): void {
    $dir = sanitizerScratch();
    config(['tables.cell_sanitizer.cache_path' => $dir]);
    $files = File::partialMock();
    $files->shouldReceive('isDirectory')->with($dir)->andReturn(false);
    $files->shouldReceive('makeDirectory')->once()->andReturn(false);

    expect(preparedCachePath(new TableCellSanitizer))->toBe($dir);
});

it('keeps the permitted link schemes and drops the rest', function (string $href, bool $kept): void {
    $out = (new TableCellSanitizer)->sanitize('<a href="'.$href.'">x</a>');

    expect(str_contains($out, 'href="'.$href.'"'))->toBe($kept);
})->with([
    'http' => ['http://example.com/a', true],
    'https' => ['https://example.com/a', true],
    'mailto' => ['mailto:a@example.com', true],
    'tel' => ['tel:+15015550100', true],
    'javascript' => ['javascript:alert(1)', false],
    'data' => ['data:text/html,x', false],
]);
