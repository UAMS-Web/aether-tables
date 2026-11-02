<?php

declare(strict_types=1);

use UAMSWeb\Aether\Tables\Listeners\HandlePasteTableDataTrigger;
use UAMSWeb\Aether\Tables\Tests\Support\Doubles\TriggerActivated;

/**
 * Coverage for the paste-to-table trigger (UAMS-Web/uams-statamic-archive#226).
 *
 * {@see HandlePasteTableDataTrigger} parses the `table_data_paste` textarea into
 * the `table_data` value when the `table_data_paste_trigger` fires, ignoring
 * other triggers. The activate route that dispatches the event lives in the host site, so the
 * route-level tests stay there.
 *
 * @param  array<string, mixed>  $formValues
 * @return array<string, mixed>
 *
 * @command  vendor/bin/pest tests/Integration/Tables/HandlePasteTableDataTriggerTest.php
 */
function handlePasteTrigger(string $handle, array $formValues): array
{
    return app(HandlePasteTableDataTrigger::class)->handle(
        new TriggerActivated(null, $handle, $formValues)
    );
}

// ---
// Listener guards
// ---

it('ignores a trigger it does not own', function (): void {
    expect(handlePasteTrigger('table_data_importer_trigger', ['table_data_paste' => "a,b\n1,2"]))
        ->toBe([]);
});

it('returns no change when the paste field is missing or blank', function (mixed $value): void {
    expect(handlePasteTrigger('table_data_paste_trigger', ['table_data_paste' => $value]))
        ->toBe([]);
})->with([
    'missing' => [null],
    'empty' => [''],
    'whitespace' => ['   '],
]);

it('returns no change when the paste field is absent entirely', function (): void {
    expect(handlePasteTrigger('table_data_paste_trigger', []))->toBe([]);
});

it('returns no change when the paste parses to no rows', function (): void {
    // A non-empty paste that yields no table (an empty table element)
    expect(handlePasteTrigger('table_data_paste_trigger', ['table_data_paste' => '<table></table>']))
        ->toBe([]);
});

// ---
// Listener parsing
// ---

it('returns parsed table_data for a TSV paste', function (): void {
    expect(handlePasteTrigger('table_data_paste_trigger', ['table_data_paste' => "a\tb\n1\t2"]))
        ->toBe([
            'table_data' => [
                ['cells' => ['a', 'b']],
                ['cells' => ['1', '2']],
            ],
        ]);
});

it('returns sanitized table_data for an HTML paste', function (): void {
    $html = '<table><tr><td><a href="https://uams.edu">L</a></td><td>x<script>alert(1)</script></td></tr></table>';

    expect(handlePasteTrigger('table_data_paste_trigger', ['table_data_paste' => $html]))
        ->toBe([
            'table_data' => [
                ['cells' => ['<a href="https://uams.edu">L</a>', 'x']],
            ],
        ]);
});
