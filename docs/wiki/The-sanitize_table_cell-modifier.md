Written against v0.2.2.

The `sanitize_table_cell` Antlers modifier renders a table-cell value as safe inline HTML. Use it wherever a cell is printed, and print the result unescaped.

```antlers
{{ value | sanitize_table_cell }}
```

The modifier takes no parameters. Steps for linking to entries are in [Showing table cells safely](Showing-table-cells-safely.md).

| Allowed | Details |
| --- | --- |
| Links | `a` with `href`, `title` and `target`. Only `target="_blank"` is kept, and HTMLPurifier adds `rel="noreferrer noopener"` to it. |
| Link schemes | `http`, `https`, `mailto` and `tel`. A link with another scheme (`javascript:` for one) keeps its text and loses its `href`. |
| Emphasis and text | `br`, `strong`, `em`, `b`, `i`, `u`, `s`, `sub`, `sup`, `code` and `abbr` with `title`. |

Every other tag is removed. A `script` element is removed with its content, and the text of other removed tags stays. Attributes outside the table above, such as `onclick`, are removed. A bare `&` in the text is output as `&amp;`.

A value that is not a string or number produces an empty string, as does an empty one.

## Entry links

Before sanitizing, the modifier replaces `href="statamic://entry::<id>"` with the URL of that entry in the current site. See [Showing table cells safely](Showing-table-cells-safely.md) for the cases where the link is removed.

## When HTMLPurifier fails

The first failure is logged as a warning, the definition cache is abandoned for the rest of the process, and the cell is sanitized again without a cache. If that fails too, an error is logged and the cell is output as escaped text with its tags stripped, so no unsanitized markup reaches the page.
