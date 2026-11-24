Written against v0.2.2.

The two triggers the add-on handles. The add-on listens for the class named in the [`trigger_event`](Configuration.md) setting. The event it receives carries three public properties: `entry` (an entry or `null`), `triggerHandle` and `formValues`. Each listener ignores an event whose `triggerHandle` is not its own, so both compose with the site's other listeners.

Each listener returns an array of field values for the Control Panel to apply to the open form, or an empty array for no change.

| Trigger handle | Reads from `formValues` | Returns |
| --- | --- | --- |
| `table_data_paste_trigger` | `table_data_paste`, a string | `table_data`, the parsed rows |
| `table_data_importer_trigger` | `table_data_importer`, an asset id | `table_data`, the rows of the file |

`table_data` is a list of rows, each `['cells' => [...]]`, the shape of Statamic's table fieldtype.

## table_data_paste_trigger

Parses the pasted text:

- An HTML table is recognized when the text starts with `<` and contains a `<table` element. Only the first table is read, from its `thead`, `tbody`, `tfoot` and direct `tr` children. Each cell's inner HTML is sanitized with the same allowlist the modifier uses.
- Otherwise the text is delimited: tab-separated when it contains a tab, comma-separated when it does not. Quotes follow CSV rules, each cell is trimmed, and a row with only empty cells is skipped. Delimited cells are stored as plain text.
- Every row is padded with empty cells to the width of the widest row.

It returns no change when the field is missing, is not a string, or yields no rows.

## table_data_importer_trigger

Reads an uploaded spreadsheet. See [Importing a spreadsheet](Importing-a-spreadsheet.md) for the steps.

- The asset is looked up in the `table_spreadsheets` container only. A bare path is resolved inside it, an id prefixed `table_spreadsheets::` is accepted, and an id that names any other container is refused with a warning.
- The extension, in any letter case, has to be `csv`, `xls` or `xlsx`.
- The value of `table_data_importer` can be an id or a list of ids, and the first is used.
- Each of these returns no change and is logged: no asset selected, an id that is not a string, an id naming another container, an asset that does not exist or has another extension, a file that cannot be read, and a path Flysystem rejects (such as `../`).
- A file with no non-empty cell, and a repeat of the same event in one process, return no change.

On an existing entry the listener sets `table_data` on the entry and saves it quietly, and also returns the value.
