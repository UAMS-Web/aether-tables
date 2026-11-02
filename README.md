# Aether Tables

Table sanitizing and pasted-table handling for Statamic's editors, by UAMS Web Services. The package is `uams-web/aether-tables`, in the `UAMSWeb\Aether\Tables\` namespace.

> This repository was generated from [`UAMS-Web/statamic-addon-template`](https://github.com/UAMS-Web/statamic-addon-template). The table code moves in from `UAMS-Web/uams-statamic` in a separate change, so the add-on registers nothing yet.

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

The file has no settings yet.

## Development and release

- [`docs/DEVELOPMENT.md`](docs/DEVELOPMENT.md): developing against a host application, and the commands.
- [`docs/SUBMISSION.md`](docs/SUBMISSION.md): the Packagist and Marketplace checklist.
- [`docs/DECISIONS.md`](docs/DECISIONS.md): the decision log.

## License

No license is declared yet. It waits on the general decision from UAMS legal in [`UAMS-Web/uams-statamic#261`](https://github.com/UAMS-Web/uams-statamic/issues/261).
