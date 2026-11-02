<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Tables\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use UAMSWeb\Aether\Tables\Listeners\HandleTableDataImporterTrigger;

/**
 * Import class for reading spreadsheet data into an array.
 *
 * Implements the `ToArray` concern from `maatwebsite/excel` to convert a
 * spreadsheet file into a PHP array, which {@see HandleTableDataImporterTrigger}
 * then turns into the `table_data` value.
 */
class SpreadsheetImport implements ToArray
{
    /**
     * Receives the spreadsheet data; the Excel facade returns it to the caller.
     *
     * @param  list<list<scalar|null>>  $array  The spreadsheet data as a multidimensional array
     */
    public function array(
        array $array
    ): void {
        // `Excel::toArray` collects the rows itself; the import has nothing to keep or return.
    }
}
