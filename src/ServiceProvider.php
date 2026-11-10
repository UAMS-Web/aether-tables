<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Tables;

use Illuminate\Support\Facades\Event;
use Statamic\Modifiers\Modifier;
use Statamic\Providers\AddonServiceProvider;
use UAMSWeb\Aether\Tables\Listeners\HandlePasteTableDataTrigger;
use UAMSWeb\Aether\Tables\Listeners\HandleTableDataImporterTrigger;
use UAMSWeb\Aether\Tables\Modifiers\SanitizeTableCell;
use UAMSWeb\Aether\Tables\Services\TableCellSanitizer;

/**
 * Service provider for the add-on.
 *
 * The host application discovers it through `extra.laravel.providers` in
 * `composer.json`. It registers the `sanitize_table_cell` modifier, shares one
 * table-cell sanitizer, and listens for the host's trigger event to handle the
 * table paste and spreadsheet-import triggers.
 */
class ServiceProvider extends AddonServiceProvider
{
    /**
     * Antlers modifiers this add-on provides.
     *
     * @var list<class-string<Modifier>>
     */
    // The coverage driver attributes no executable line to a property default, so a mutant
    // here is reported uncovered whatever the tests assert; ServiceProviderTest pins the
    // registered modifier.
    // @pest-mutate-ignore: RemoveArrayItem
    protected $modifiers = [
        SanitizeTableCell::class,
    ];

    /**
     * The add-on merges and publishes its config itself, under the `tables` key
     * the sanitizer has always read, rather than under the package handle.
     *
     * @var bool
     */
    // A property default carries no executable line for the coverage driver; ServiceProviderTest
    // pins the unchanged `tables` key and the publish tag this setting stands in for.
    // @pest-mutate-ignore: FalseToTrue
    protected $config = false;

    /**
     * The Vite build of the Control Panel assets. `input` and `publicDirectory`
     * must match `vite.config.js`; the built files are committed under
     * `resources/dist`.
     *
     * @var array{input: list<string>, publicDirectory: string}
     */
    // A property default carries no executable line for the coverage driver; ViteBuildTest pins
    // these inputs and the output directory against vite.config.js and the committed manifest.
    // @pest-mutate-ignore: RemoveArrayItem
    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    /**
     * Register the config defaults and share the sanitizer, so its compiled
     * HTML definition is built once and reused across every cell in a request.
     */
    public function register(): void
    {
        // Merge the defaults under the `tables` key; the host's own keys win
        $this->mergeConfigFrom(__DIR__.'/../config/tables.php', 'tables');

        // Share one sanitizer
        $this->app->singleton(TableCellSanitizer::class);
    }

    /**
     * Boot the add-on once Statamic has booted.
     */
    public function bootAddon(): void
    {
        // Offer the config file for publishing to the host's config directory
        $this->publishes([
            __DIR__.'/../config/tables.php' => config_path('tables.php'),
        ], 'aether-tables-config');

        // Allow the modifier on user content. Statamic runs only an allowlisted
        // modifier on content data and otherwise skips it, silently returning the
        // raw value; its default allowlist includes modifiers under `App\Modifiers`
        // but not an add-on's. Table cells are content data, so without this the
        // sanitizer would not run and a cell would render unsanitized.
        $this->allowModifierOnContent('sanitize_table_cell');

        // Listen for the host's trigger event, when the host names one
        $event = config('tables.trigger_event');

        if (is_string($event) && $event !== '') {
            Event::listen($event, HandleTableDataImporterTrigger::class);
            Event::listen($event, HandlePasteTableDataTrigger::class);
        }
    }

    /**
     * Add a modifier handle to Statamic's allowlist for modifiers run on content.
     *
     * Leaves a host's own list intact: an unset list becomes the defaults plus the
     * handle, and a configured list gains the handle if it lacks it.
     *
     * @param  string  $handle  The modifier handle to allow.
     */
    private function allowModifierOnContent(string $handle): void
    {
        // Read the host's list; unset means Statamic's defaults
        $configured = config('statamic.antlers.allowedContentModifiers') ?? ['@default'];

        // Keep a list that already allows the handle
        if (! is_array($configured) || in_array($handle, $configured, true)) {
            return;
        }

        // Append the handle to the list
        config(['statamic.antlers.allowedContentModifiers' => [...$configured, $handle]]);
    }
}
