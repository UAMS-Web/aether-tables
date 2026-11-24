Written against v0.2.2.

A table cell holds HTML, typically a link or emphasis, and the template has to show it without opening the page to injected markup.

## Steps

1. In the template that renders each cell, pass the value through the modifier: `{{ value | sanitize_table_cell }}`.
2. To link a cell to an entry, store the link as `statamic://entry::<id>` in the anchor's `href`. The modifier replaces it with the entry's URL.
3. Leave the allowlist for content data as it is. The add-on adds `sanitize_table_cell` to `statamic.antlers.allowedContentModifiers` itself, because Statamic runs only an allowlisted modifier on content data and otherwise returns the raw value.

## What happens to a link to an entry

The URL is the one the entry has in the current site. The link is removed (the anchor stays, without an `href`) when the entry does not exist, is not published in the current site, or has no URL. A query string or fragment after the id is kept on the URL.

## Reference

- [The sanitize_table_cell modifier](The-sanitize_table_cell-modifier.md): what the modifier allows and removes.
