<?php

declare(strict_types=1);

use Maatwebsite\Excel\Facades\Excel;
use UAMSWeb\Aether\Tables\Imports\SpreadsheetImport;

/**
 * Reads real fixture files through `maatwebsite/excel`, with nothing mocked.
 *
 * `HandleTableDataImporterTriggerTest` fakes `Excel::toArray`, so only these tests exercise
 * the library's own reading: the sheet, row and cell shape, and the PHP type of each cell.
 * The CSV and XLSX fixtures hold the same table, so both formats must produce the same rows.
 *
 * @covers \UAMSWeb\Aether\Tables\Imports\SpreadsheetImport
 *
 * @command  vendor/bin/pest tests/Integration/Tables/SpreadsheetImportRealFileTest.php
 */
const SPREADSHEET_FIXTURE_DIRECTORY = __DIR__.'/../../Fixtures/spreadsheets';

/**
 * The rows both fixtures hold, as the library returns them.
 *
 * Numeric cells arrive as `int` and `float`, an empty cell as `null`, and a digit string with
 * leading zeros stays a string.
 *
 * @return list<list<int|float|string|null>>
 */
function expectedSpreadsheetRows(): array
{
    return [
        ['Name', 'Count', 'Ratio', 'Code', 'Note'],
        ['Alpha', 3, 1.5, '007', null],
        ['Beta', null, 0.25, 'x1', 'with, comma'],
    ];
}

it('reads a real file into one sheet of rows with typed cells', function (string $file): void {
    $sheets = Excel::toArray(new SpreadsheetImport, SPREADSHEET_FIXTURE_DIRECTORY.'/'.$file);

    // One sheet, and its rows in file order with each cell's type intact
    expect($sheets)->toHaveCount(1)
        ->and($sheets[0])->toBe(expectedSpreadsheetRows());
})->with(['csv' => 'table.csv', 'xlsx' => 'table.xlsx']);

it('keeps a digit string with leading zeros as a string', function (string $file): void {
    $rows = Excel::toArray(new SpreadsheetImport, SPREADSHEET_FIXTURE_DIRECTORY.'/'.$file)[0];

    expect($rows[1][3])->toBe('007');
})->with(['csv' => 'table.csv', 'xlsx' => 'table.xlsx']);

it('returns an empty cell as null rather than an empty string', function (string $file): void {
    $rows = Excel::toArray(new SpreadsheetImport, SPREADSHEET_FIXTURE_DIRECTORY.'/'.$file)[0];

    expect($rows[1][4])->toBeNull()
        ->and($rows[2][1])->toBeNull();
})->with(['csv' => 'table.csv', 'xlsx' => 'table.xlsx']);

it('returns a numeric cell as a number, not a string', function (string $file): void {
    $rows = Excel::toArray(new SpreadsheetImport, SPREADSHEET_FIXTURE_DIRECTORY.'/'.$file)[0];

    expect($rows[1][1])->toBeInt()
        ->and($rows[1][2])->toBeFloat();
})->with(['csv' => 'table.csv', 'xlsx' => 'table.xlsx']);
