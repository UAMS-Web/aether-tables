<?php

declare(strict_types=1);

use UAMSWeb\Aether\Tables\Services\PastedTableParser;

/**
 * Pins what the pasted-table parser leaves behind and what it skips in the DOM it
 * builds: the shared libxml error state, whitespace and comment nodes between
 * rows and cells, and non-row children of a section.
 *
 * The mutants that no input can distinguish (libxml lower-cases element names, the
 * `php://temp` open cannot fail, and similar) carry their reason on the
 * `@pest-mutate-ignore` annotation in `PastedTableParser`.
 *
 * @command  vendor/bin/pest tests/Integration/Tables/PastedTableParserMutationTest.php
 */
/**
 * @return list<array{cells: list<string>}>
 */
function parseMutationTable(string $pasted): array
{
    return app(PastedTableParser::class)->parse($pasted);
}

it('parses an html table that follows leading whitespace', function (): void {
    // The leading text must be trimmed before the markup is recognized
    expect(parseMutationTable("\n  <table><tr><td>a</td></tr></table>"))->toBe([['cells' => ['a']]]);
});

it('returns nothing for markup that mentions a table without containing one', function (): void {
    // The comment matches the table pattern but libxml builds no table element
    expect(parseMutationTable('<!-- <table> --><p>x</p>'))->toBe([]);
});

it('keeps only cells and rows when whitespace sits between them', function (): void {
    $html = "<table>\n <tr>\n <td>a</td>\n <td>b</td>\n </tr>\n <tr><td>c</td></tr>\n</table>";

    // Text nodes between the rows must be skipped without ending the walk, and text between the cells must not become a cell
    expect(parseMutationTable($html))->toBe([
        ['cells' => ['a', 'b']],
        ['cells' => ['c', '']],
    ]);
});

it('ignores a non-cell element inside a row', function (): void {
    expect(parseMutationTable('<table><tr><span>s</span><td>a</td></tr></table>'))->toBe([['cells' => ['a']]]);
});

it('ignores a comment between rows', function (): void {
    expect(parseMutationTable('<table><!-- note --><tr><td>a</td></tr><tr><td>b</td></tr></table>'))
        ->toBe([['cells' => ['a']], ['cells' => ['b']]]);
});

it('reads only rows from a section and skips other elements inside it', function (): void {
    // A wrapper element holding a cell is not a row
    $html = '<table><tbody><div><td>x</td></div><tr><td>a</td></tr></tbody></table>';

    expect(parseMutationTable($html))->toBe([['cells' => ['a']]]);
});

it('leaves libxml internal error handling as it found it', function (bool $before): void {
    $original = libxml_use_internal_errors($before);

    try {
        parseMutationTable('<table><tr><td>a</table><foo></bar>');

        expect(libxml_use_internal_errors())->toBe($before);
    } finally {
        libxml_use_internal_errors($original);
    }
})->with([
    'collecting' => [true],
    'not collecting' => [false],
]);

it('leaves no libxml errors behind for a caller that is collecting them', function (): void {
    $original = libxml_use_internal_errors(true);
    libxml_clear_errors();

    try {
        parseMutationTable('<table><tr><td>a</table><foo></bar>');

        expect(libxml_get_errors())->toBe([]);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($original);
    }
});
