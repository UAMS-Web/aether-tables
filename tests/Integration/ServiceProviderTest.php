<?php

declare(strict_types=1);

use Statamic\Facades\Addon;
use UAMSWeb\Aether\Tables\ServiceProvider;
use UAMSWeb\Aether\Tables\Services\TableCellSanitizer;
use UAMSWeb\Aether\Tables\Tests\Support\Doubles\TriggerActivated;

/**
 * The add-on boots inside a minimal Statamic.
 *
 * @command  vendor/bin/pest tests/Integration/ServiceProviderTest.php
 */
it('is discovered by Statamic as an add-on', function (): void {
    // Expect the add-on among those Statamic booted
    expect(Addon::all()->map->package()->all())->toContain('uams-web/aether-tables');
});

it('publishes its config file under the tag the README names', function (): void {
    // Read the paths published under the config tag
    $paths = ServiceProvider::pathsToPublish(null, 'aether-tables-config');

    // Expect the package config to publish to the app's config directory
    expect(array_map(realpath(...), array_keys($paths)))->toBe([realpath(__DIR__.'/../../config/tables.php')])
        ->and(array_values($paths))->toBe([config_path('tables.php')]);
});

it('registers the sanitize_table_cell modifier and shares one sanitizer', function (): void {
    // Expect the modifier under its unchanged handle
    expect(app('statamic.modifiers')->has('sanitize_table_cell'))->toBeTrue()
        // Expect one shared sanitizer instance
        ->and(app(TableCellSanitizer::class))->toBe(app(TableCellSanitizer::class));
});

it('merges its defaults under the unchanged tables config key', function (): void {
    expect(config('tables.cell_sanitizer.cache_path'))->toBeString();
});

it('handles the paste and importer triggers when the configured trigger event is dispatched', function (): void {
    // Dispatch the configured event for the paste trigger and collect the listeners' responses
    $responses = event(new TriggerActivated(null, 'table_data_paste_trigger', ['table_data_paste' => 'a,b
1,2']));

    // Expect the paste listener's parsed table among them
    expect(array_filter($responses ?? []))->toContain(['table_data' => [['cells' => ['a', 'b']], ['cells' => ['1', '2']]]]);
});

/**
 * Boot a fresh copy of the add-on provider against the current config.
 *
 * `bootAddon()` is public, so the trigger and allowlist branches can be driven by
 * config without rebuilding the application.
 */
function bootProviderWith(mixed $triggerEvent, mixed $allowedContentModifiers): void
{
    config([
        'tables.trigger_event' => $triggerEvent,
        'statamic.antlers.allowedContentModifiers' => $allowedContentModifiers,
    ]);

    new ServiceProvider(app())->bootAddon();
}

it('listens with both triggers on the configured event', function (): void {
    // Boot against a distinct event name so the provider booted by the test case does not count
    bootProviderWith('aether-tables.test.event', null);

    // Expect the importer and the paste listener, one each
    expect(app('events')->getListeners('aether-tables.test.event'))->toHaveCount(2);
});

it('registers no listener when the trigger event is empty or unset', function (mixed $event): void {
    bootProviderWith($event, null);

    // Expect nothing under the empty name a mis-guarded string check would listen on
    expect(app('events')->hasListeners(''))->toBeFalse();
})->with([
    'unset' => [null],
    'empty string' => [''],
]);

it('allows the modifier on content beside the defaults when the host sets no list', function (): void {
    bootProviderWith(null, null);

    expect(config('statamic.antlers.allowedContentModifiers'))->toBe(['@default', 'sanitize_table_cell']);
});

it('appends the modifier to the host list and keeps the host entries', function (): void {
    bootProviderWith(null, ['strip_tags']);

    expect(config('statamic.antlers.allowedContentModifiers'))->toBe(['strip_tags', 'sanitize_table_cell']);
});

it('leaves a host list that already allows the modifier unchanged', function (): void {
    bootProviderWith(null, ['sanitize_table_cell', 'strip_tags']);

    expect(config('statamic.antlers.allowedContentModifiers'))->toBe(['sanitize_table_cell', 'strip_tags']);
});

it('leaves a host allowlist that is not a list unchanged', function (): void {
    bootProviderWith(null, '*');

    expect(config('statamic.antlers.allowedContentModifiers'))->toBe('*');
});
