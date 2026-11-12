# Aether Tables

Table sanitizing and pasted-table handling for Statamic's editors, by UAMS Web Services. The package is `uams-web/aether-tables`, in the `UAMSWeb\Aether\Tables\` namespace.

## What it does

- The `sanitize_table_cell` Antlers modifier, which renders a table cell through HTMLPurifier against a small inline allowlist (links and basic emphasis; everything else is stripped).
- Pasted-table parsing: the `table_data_paste_trigger` trigger turns pasted HTML, TSV or CSV into the `table_data` value.
- Spreadsheet import: the `table_data_importer_trigger` trigger reads an uploaded spreadsheet asset into `table_data`.

The Table set's template, script and styles belong to the site's theme and stay in the site.

## Requirements

- PHP 8.4 or later
- Statamic 6.35 or later

## Installation

```bash
composer require uams-web/aether-tables
```

Publish the configuration file to the host application's `config/` directory with:

```bash
php artisan vendor:publish --tag=aether-tables-config
```

It merges into the `tables` config key. The settings are:

- `tables.cell_sanitizer.cache_path`: where HTMLPurifier stores its compiled definitions (default `storage/app/htmlpurifier`).
- `tables.trigger_event`: the class name of the event the host dispatches when a trigger field is activated in the Control Panel. The paste and import listeners listen for it. The event carries `entry`, `triggerHandle` and `formValues` properties. The default is `App\Events\TriggerActivated`.

Statamic runs only allowlisted modifiers on content data. The add-on adds `sanitize_table_cell` to `statamic.antlers.allowedContentModifiers` itself, so the host does not have to.

## Development and release

- [`docs/DEVELOPMENT.md`](docs/DEVELOPMENT.md): developing against a host application, and the commands.
- [`docs/SUBMISSION.md`](docs/SUBMISSION.md): the Packagist and Marketplace checklist.
- [`docs/DECISIONS.md`](docs/DECISIONS.md): the decision log.

## License

MIT. See [`LICENSE`](LICENSE).
