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
     * Converts the spreadsheet data into an array.
     *
     * @param  list<list<scalar|null>>  $array  The spreadsheet data as a multidimensional array
     * @return list<list<scalar|null>> The same array, returned for further processing
     */
    public function array(
        array $array
    ): array {
        // Return the array as-is for processing by the importer listener
        return $array;
    }
}
