Written against v0.2.2.

One entry per symptom, as you meet it: the message or behavior, its cause, and the fix.

- [A trigger does nothing](#a-trigger-does-nothing)
- [The importer logs "Asset not found or not a supported format"](#the-importer-logs-asset-not-found-or-not-a-supported-format)
- [The importer logs "Refusing asset id outside the intended container"](#the-importer-logs-refusing-asset-id-outside-the-intended-container)
- [The importer logs "Skipping duplicate TriggerActivated event processing"](#the-importer-logs-skipping-duplicate-triggeractivated-event-processing)
- [A cell shows its text but the link does not work](#a-cell-shows-its-text-but-the-link-does-not-work)
- [Cells show unsanitized markup](#cells-show-unsanitized-markup)
- [The sanitizer warns about its cache directory](#the-sanitizer-warns-about-its-cache-directory)
- [Fixed](#fixed)

## A trigger does nothing

Activating `table_data_paste_trigger` or `table_data_importer_trigger` leaves `table_data` as it was, and the log holds nothing from the add-on.

**Cause:** the add-on listens for the class in `tables.trigger_event`, and registers nothing when that value is empty. If the site dispatches a differently named event, no listener runs. A paste with no usable rows also returns no value.

**Fix:** set `tables.trigger_event` to the class the site dispatches (see [Configuration](Configuration.md)), and check that the paste holds at least one non-empty row.

## The importer logs "Asset not found or not a supported format"

```text
local.ERROR: HandleTableDataImporterTrigger::handle: Asset not found or not a supported format
```

**Cause:** the id does not resolve to an asset in the `table_spreadsheets` container, or the file's extension is not `csv`, `xls` or `xlsx`.

**Fix:** upload the file to the `table_spreadsheets` container and select it again. Rename it with one of the three extensions if needed.

## The importer logs "Refusing asset id outside the intended container"

```text
local.WARNING: HandleTableDataImporterTrigger::handle: Refusing asset id outside the intended container
```

**Cause:** the selected id names another container. The importer reads from `table_spreadsheets` only.

**Fix:** move the file into that container. The log context names the requested container.

## The importer logs "Skipping duplicate TriggerActivated event processing"

```text
local.NOTICE: HandleTableDataImporterTrigger::handle: Skipping duplicate TriggerActivated event processing
```

**Cause:** an identical event, with the same entry, trigger handle and form values, reached the listener twice in one process. The second is skipped.

**Fix:** none is needed when the first run filled the table. To import again, change the selection or the entry and activate the trigger again.

## A cell shows its text but the link does not work

**Cause:** the modifier removes the `href` of a link whose scheme is not `http`, `https`, `mailto` or `tel`, and of a `statamic://entry::<id>` link whose entry does not exist, is not published in the current site or has no URL.

**Fix:** use a supported scheme, or publish the entry in the current site.

## Cells show unsanitized markup

**Cause:** the template prints the value without `sanitize_table_cell`, or the modifier did not run. Statamic skips a modifier that is not on `statamic.antlers.allowedContentModifiers` for content data and returns the raw value. The add-on adds its own handle, but a host that replaces the list after the add-on boots drops it.

**Fix:** pass the value through `{{ value | sanitize_table_cell }}`, and keep `sanitize_table_cell` in the host's list.

## The sanitizer warns about its cache directory

```text
local.WARNING: Could not create the table-cell sanitizer cache directory; HTMLPurifier will recompile its definition once per process.
```

**Cause:** `tables.cell_sanitizer.cache_path` cannot be created or written.

**Fix:** point it at a writable directory (see [Configuration](Configuration.md)). Output is still sanitized meanwhile, only slower.

## Fixed

Problems fixed in a release stay here, with the release that fixed them, instead of being deleted.
