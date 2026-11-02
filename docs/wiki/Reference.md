Written against v0.0.0.

<What this area is for.>

| Key | Values | Default | Effect |
| --- | --- | --- | --- |
| `some_key` | `value`, `other` | `value` | <What it does.> |

## Example

```php
// config/tables.php
return [
    'cell_sanitizer' => ['cache_path' => storage_path('app/htmlpurifier')],
    'trigger_event' => 'App\Events\TriggerActivated',
];
```
