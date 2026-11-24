Written against v0.2.2.

An editor has a table in a CSV, XLS or XLSX file and wants it in a `table_data` field.

## Steps

1. Upload the file to the asset container with the handle `table_spreadsheets`. The importer reads only from that container.
2. In the entry's `table_data_importer` field, select the uploaded file.
3. Activate the `table_data_importer_trigger` field.
4. The Control Panel fills `table_data` with the file's rows. Save the entry to keep them.

## What the import does

- It reads the first sheet of the file.
- It turns every cell into a string, trims it, and pads each row with empty cells to the width of the first row.
- A file with no non-empty cell leaves `table_data` unchanged.
- On an entry that already exists, it also sets `table_data` on the entry and saves it without firing further events. A new entry has no record to save, so only the returned value changes the form.
- The same event handled twice in one process is skipped, and a notice is logged.

**Note:** cells of an imported file are stored as the plain text they hold. They are sanitized when shown, through the `sanitize_table_cell` modifier.

**Warning:** a file in any container other than `table_spreadsheets`, or with an extension other than `csv`, `xls` or `xlsx`, is refused and logged. See [Troubleshooting](Troubleshooting.md).

## Reference

- [Triggers](Triggers.md): the fields the importer reads, what it returns and each case it refuses.
- [Configuration](Configuration.md): the `trigger_event` setting that connects the importer to the site's event.
