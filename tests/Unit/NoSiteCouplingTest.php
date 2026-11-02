<?php

declare(strict_types=1);

/**
 * The add-on must not depend on the site that uses it: nothing under `src/` may
 * import or name a class in the `App` namespace. The site's trigger event is
 * named only in `config/tables.php`, as the default of a setting.
 *
 * @command  vendor/bin/pest tests/Unit/NoSiteCouplingTest.php
 */

/**
 * Whether PHP source imports, declares or fully qualifies a name in `App\`.
 *
 * @param  string  $source  The PHP source to scan.
 * @return bool True when the source names the `App` namespace.
 */
function namesAppNamespace(string $source): bool
{
    // A use statement, a namespace declaration, or a fully qualified \App\ name
    return preg_match('/^\s*(?:use|namespace)\s+\\\\?App\\\\|(?<![\w\\\\])\\\\App\\\\/m', $source) === 1;
}

it('imports nothing from the App namespace', function (): void {
    // List every file under src/
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/src', FilesystemIterator::SKIP_DOTS));

    $offenders = [];

    foreach ($files as $file) {
        // Keep the PHP files that name the site's namespace
        if ($file instanceof SplFileInfo && $file->getExtension() === 'php' && namesAppNamespace((string) file_get_contents($file->getPathname()))) {
            $offenders[] = $file->getPathname();
        }
    }

    // Expect no file to reference the site
    expect($offenders)->toBe([]);
});

it('detects each way of naming the App namespace, and not a look-alike', function (): void {
    expect(namesAppNamespace("use App\\Events\\TriggerActivated;\n"))->toBeTrue()
        ->and(namesAppNamespace("namespace App\\Listeners;\n"))->toBeTrue()
        ->and(namesAppNamespace("\$x = new \\App\\Foo();\n"))->toBeTrue()
        ->and(namesAppNamespace("use UAMSWeb\\Aether\\Tables\\Tests\\App\\Thing;\n"))->toBeFalse()
        ->and(namesAppNamespace("use Illuminate\\Support\\Application;\n"))->toBeFalse();
});
