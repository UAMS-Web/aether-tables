<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Tables\Modifiers;

use Statamic\Modifiers\Modifier;
use UAMSWeb\Aether\Tables\Services\TableCellEntryLinkResolver;
use UAMSWeb\Aether\Tables\Services\TableCellSanitizer;

/**
 * Antlers modifier: sanitize a table-cell value to a safe inline HTML allowlist.
 *
 * Table cells store raw HTML (imported WordPress tables carry inline links and
 * emphasis). This modifier renders that value through {@see TableCellSanitizer},
 * which permits inline links and basic emphasis and escapes everything else —
 * enabling cell links while closing the stored-XSS hole left by emitting the raw
 * `{{ value }}`.
 *
 * Usage in the table render partial:
 *
 * - `{{ value | sanitize_table_cell }}`
 *
 * Entry references (`statamic://entry::<id>`) are resolved to the entry's URL
 * by {@see TableCellEntryLinkResolver} first, because the sanitizer's scheme list
 * strips them.
 *
 * @see TableCellSanitizer
 */
class SanitizeTableCell extends Modifier
{
    /**
     * Sanitize the cell value to safe inline HTML.
     *
     * @param  mixed  $value  The raw cell value
     * @param  array<int|string, mixed>  $params  Unused modifier parameters
     * @param  array<int|string, mixed>  $context  Unused Statamic modifier context
     * @return string The sanitized HTML, safe to render unescaped
     */
    public function index(
        mixed $value,
        array $params,
        array $context
    ): string {
        // Point entry references at their URLs, which the sanitizer's scheme list
        // would otherwise strip
        $resolved = app(TableCellEntryLinkResolver::class)->resolve($value);

        // Resolve the shared sanitizer so its compiled definition is reused
        return app(TableCellSanitizer::class)->sanitize($resolved);
    }
}
