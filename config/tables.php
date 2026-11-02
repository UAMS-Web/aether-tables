<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Tables configuration
|--------------------------------------------------------------------------
|
| The add-on merges this file into the application's `tables` config key, the
| key the table-cell sanitizer has always read, so a host that already has a
| `config/tables.php` keeps working: the host's top-level keys win. Publish it
| to the host's `config/` directory with:
|
|     php artisan vendor:publish --tag=aether-tables-config
|
| Give every setting a comment saying what it does and which values it takes,
| and a default that is safe to leave unset.
|
*/

// Per-worker isolation for the table-cell sanitizer's HTMLPurifier definition
// cache. Each paratest worker is given its own TEST_TOKEN within a band, so
// appending it to the serializer path hands every worker its own cache
// directory. Without it ~12 workers share one `storage/app/htmlpurifier` tree:
// they race to create it (the loser takes a `mkdir(): File exists` warning,
// promoted to ErrorException by the test error handler) and then race on the
// `.ser` files inside it, which HTMLPurifier writes with a non-atomic
// `file_put_contents()` and reads back through `unserialize()` behind nothing
// but a `file_exists()` check. TEST_TOKEN is unset in production and in serial
// runs, so the path falls back to the shared default there: a no-op outside a
// parallel test run, which keeps one warm definition cache in production. The
// suffix mirrors the file cache's per-worker path in a host's config/cache.php,
// including its process id: the bare token is the worker index, shared by the
// same-numbered worker of a second concurrent band, and these writes are not
// atomic.
$purifierCachePath = storage_path('app/htmlpurifier'.(($token = env('TEST_TOKEN')) ? '/'.$token.'-'.getmypid() : ''));

return [

    /*
    |--------------------------------------------------------------------------
    | Trigger event
    |--------------------------------------------------------------------------
    |
    | The class name of the event the host dispatches when a trigger field is
    | activated in the Control Panel. The paste and importer listeners are
    | registered for it. The event carries `entry`, `triggerHandle` and
    | `formValues` public properties. The default is the event the UAMS site
    | dispatches today.
    |
    */

    'trigger_event' => 'App\Events\TriggerActivated',

    /*
    |--------------------------------------------------------------------------
    | Table Cell Sanitizer
    |--------------------------------------------------------------------------
    |
    | Settings for the table-cell sanitizer, which renders every table cell
    | through HTMLPurifier against a small inline allowlist.
    |
    */

    'cell_sanitizer' => [

        // Where HTMLPurifier serializes its compiled HTML/URI definitions.
        // Kept under storage because vendor/ is read-only in production.
        'cache_path' => $purifierCachePath,

    ],

];
