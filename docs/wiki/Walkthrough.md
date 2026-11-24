Written against v0.2.2.

By the end of this page you will have a table in a `table_data` field, filled by pasting a copied table, and shown on a page with every cell sanitized.

It assumes the add-on is installed (see [Installation (README)](https://github.com/UAMS-Web/aether-tables#installation)) in a Statamic 6.35 or later site. The add-on listens for an event the site dispatches when a trigger field is activated in the Control Panel. The site's blueprint supplies the fields below; the add-on does not create them. See [Triggers](Triggers.md) for the contract.

## 1. Paste the table

Copy a table from a spreadsheet, a web page, or HTML or CSV text. In the entry's `table_data_paste` field, paste it.

The add-on recognizes three shapes: an HTML `<table>`, tab-separated values (the format a spreadsheet puts on the clipboard) and comma-separated values.

## 2. Activate the paste trigger

Activate the `table_data_paste_trigger` field. The Control Panel replaces the `table_data` value in the open form with the parsed rows. Nothing is saved until the entry is saved.

Every row has the same number of cells: a short row is padded with empty cells to the width of the widest row.

**Note:** a paste with no usable rows leaves `table_data` unchanged.

## 3. Show the table

In the template that renders the table, pass each cell through the modifier:

```antlers
{{ value | sanitize_table_cell }}
```

Links and basic emphasis in a cell survive. Anything else is removed. See [The sanitize_table_cell modifier](The-sanitize_table_cell-modifier.md).

## Go further

- [Importing a spreadsheet](Importing-a-spreadsheet.md): fill the table from an uploaded file instead of a paste.
- [Showing table cells safely](Showing-table-cells-safely.md): links to entries, and what the modifier does to a cell.
