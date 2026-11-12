<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Statamic\Entries\Entry as EntryItem;
use Statamic\Facades\Antlers;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry;
use Statamic\Facades\Stache;
use UAMSWeb\Aether\Tables\Services\TableCellEntryLinkResolver;

/**
 * Entry references in table cells render as the entry's URL (UAMS-Web/aether-tables#51).
 *
 * A cell's internal link is stored as `statamic://entry::<id>`, which the cell
 * sanitizer's scheme list strips. {@see TableCellEntryLinkResolver} resolves the
 * reference first, through the `sanitize_table_cell` modifier, so only rendered output
 * changes. Every resolution case renders through the modifier, so removing the
 * resolver from the render path turns them red. Moved from the site's
 * `tests/Feature/Tables/TableCellEntryLinkResolverTest.php` (UAMS-Web/uams-statamic#613).
 *
 * @covers \UAMSWeb\Aether\Tables\Services\TableCellEntryLinkResolver
 * @covers \UAMSWeb\Aether\Tables\Modifiers\SanitizeTableCell
 *
 * @command  vendor/bin/pest tests/Integration/Tables/TableCellEntryLinkResolverTest.php
 */

/**
 * The temporary directory for this worker, made on first use and removed with `$remove`.
 */
function entryLinkScratch(bool $remove = false): string
{
    static $dir = null;

    // Remove the directory made so far
    if ($remove) {
        if ($dir !== null) {
            (new Filesystem)->deleteDirectory($dir);
        }

        $dir = null;

        return '';
    }

    // Make a directory unique to this worker and test
    if ($dir === null) {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'entry-link-'.uniqid('', true);
        mkdir($dir, 0755, true);
    }

    return $dir;
}

beforeEach(function (): void {
    // Keep what Statamic saves out of the repository
    $scratch = entryLinkScratch();
    Stache::stores()->each(function ($store) use ($scratch): void {
        $store->directory($scratch.'/stache/'.$store->key());
    });

    // A routed collection, so its entries have URLs
    $pages = CollectionFacade::make('pages');
    $pages->title('Pages');
    $pages->routes('/{slug}');
    $pages->save();
});

afterEach(function (): void {
    entryLinkScratch(true);
});

/**
 * Persist an entry and return it.
 */
function tableLinkPage(string $id, string $slug, bool $published = true, string $collection = 'pages'): EntryItem
{
    /** @var EntryItem $entry */
    $entry = Entry::make();
    $entry->id($id);
    $entry->collection($collection);
    $entry->slug($slug);
    $entry->published($published);
    $entry->data(['title' => ucfirst($slug)]);
    $entry->save();

    return $entry;
}

/**
 * Render one cell through the `sanitize_table_cell` modifier.
 */
function renderTableCell(string $cell): string
{
    return (string) Antlers::parse('{{ value | sanitize_table_cell }}', ['value' => $cell]);
}

it('renders an entry reference as the entry URL', function (): void {
    tableLinkPage('kirk-id', 'kirk');

    expect(renderTableCell('<a href="statamic://entry::kirk-id">Kirk</a>'))
        ->toBe('<a href="/kirk">Kirk</a>');
});

it('keeps the query string and fragment, including an encoded ampersand', function (string $suffix): void {
    tableLinkPage('spock-id', 'spock');

    expect(renderTableCell('<a href="statamic://entry::spock-id'.$suffix.'">Spock</a>'))
        ->toBe('<a href="/spock'.$suffix.'">Spock</a>');
})->with([
    'query and fragment' => '?a=1&amp;b=2#f',
    'fragment only' => '#bridge',
    'query only' => '?deck=5',
]);

it('resolves a single-quoted reference', function (): void {
    tableLinkPage('uhura-id', 'uhura');

    expect(renderTableCell("<a href='statamic://entry::uhura-id'>Uhura</a>"))
        ->toBe('<a href="/uhura">Uhura</a>');
});

it('renders a reference to a missing or unpublished entry as text without an href', function (string $id): void {
    tableLinkPage('chekov-id', 'chekov', published: false);

    $html = renderTableCell('<a href="statamic://entry::'.$id.'#f">Crew</a>');

    expect($html)->toBe('<a>Crew</a>')
        ->and($html)->not->toContain('statamic://');
})->with([
    'missing' => 'no-such-entry',
    'unpublished' => 'chekov-id',
]);

it('renders a reference to an entry without a URL as text without an href', function (): void {
    // A collection with no route gives its entries no URL
    CollectionFacade::make('logs')->save();
    tableLinkPage('mccoy-id', 'mccoy', collection: 'logs');

    expect(renderTableCell('<a href="statamic://entry::mccoy-id">McCoy</a>'))
        ->toBe('<a>McCoy</a>');
});

it('still strips javascript and data links', function (string $href): void {
    expect(renderTableCell('<a href="'.$href.'">x</a>'))->toBe('<a>x</a>');
})->with([
    'javascript' => 'javascript:alert(1)',
    'data' => 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
]);

it('leaves the stored cell holding the reference after rendering', function (): void {
    tableLinkPage('sulu-id', 'sulu');
    $cell = '<a href="statamic://entry::sulu-id">Sulu</a>';

    /** @var EntryItem $host */
    $host = Entry::make();
    $host->id('scotty-id');
    $host->collection('pages');
    $host->slug('scotty');
    $host->data(['title' => 'Scotty', 'table_data' => [['cells' => [$cell]]]]);
    $host->save();

    // Render the stored cell
    /** @var list<array{cells: list<string>}> $rows */
    $rows = $host->get('table_data');
    $html = renderTableCell($rows[0]['cells'][0]);

    /** @var EntryItem $reloaded */
    $reloaded = Entry::find('scotty-id');

    expect($html)->toBe('<a href="/sulu">Sulu</a>')
        ->and($reloaded->get('table_data'))->toBe([['cells' => [$cell]]]);
});

it('passes non-string and reference-free values through unchanged', function (mixed $value): void {
    expect(app(TableCellEntryLinkResolver::class)->resolve($value))->toBe($value);
})->with([
    'null' => [null],
    'int' => [42],
    'plain link' => ['<a href="https://uams.edu">UAMS</a>'],
]);
