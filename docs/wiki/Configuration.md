Written against v0.2.2.

The settings of the add-on, under the `tables` config key. The add-on merges its defaults into that key, and a host's own top-level keys win, so a host that already has a `config/tables.php` keeps working.

Publish the file to the host's `config/` directory with:

```bash
php artisan vendor:publish --tag=aether-tables-config
```

| Key | Values | Default | Effect |
| --- | --- | --- | --- |
| `trigger_event` | A class name, or an empty string | `App\Events\TriggerActivated` | The event the host dispatches when a trigger field is activated in the Control Panel. The paste and import listeners are registered for it. An empty value registers neither. |
| `cell_sanitizer.cache_path` | A directory path | `storage/app/htmlpurifier` | Where HTMLPurifier stores its compiled definitions. The directory is created when it is missing. |

When the cache directory cannot be created, the sanitizer logs a warning and compiles its definitions once per process instead.

## Example

```php
// config/tables.php
return [
    'cell_sanitizer' => ['cache_path' => storage_path('app/htmlpurifier')],
    'trigger_event' => 'App\Events\TriggerActivated',
];
```

## Statamic's modifier allowlist

The add-on appends `sanitize_table_cell` to `statamic.antlers.allowedContentModifiers`. A list the host already sets keeps its entries and gains the handle, and an unset list becomes Statamic's defaults plus the handle.
