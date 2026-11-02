# Decision log

The canonical record of **why** this repository is the way it is: the reasoning and the **rejected alternatives** behind decisions that have closed. It is the guard against silently relitigating a settled decision.

**Consult it before acting.** Before proposing a change, reopening a question, or picking an approach, check here first: the path under consideration may already be a decided-and-recorded one, or an explicitly rejected one. If it is, follow the record (or make the case to revisit it); do not quietly re-decide.

**When a seam closes, record the verdict in both places.** Capture the *what*, *why* and rejected alternatives **here** (the cross-ticket index) **and** in the GitHub issue's `## Decision` section (the per-ticket record), keeping the two in sync. This file does not replace the issue tracker, which stays the system of record for a single ticket's status.

See the [`design-decision-forks`](../.claude/rules/design-decision-forks.md) rule for how a fork is surfaced and driven to resolution before its verdict lands here.

## How to add an entry

Oldest first, one entry per closed decision, so **a new entry is appended to the end of the file**. Keep it terse; the issue carries the depth. Each entry is preceded by exactly one blank line, which `scripts/check-decision-log-order.mjs` enforces along with the date order. The file is marked `merge=union` in `.gitattributes`, so two branches that each append an entry merge without a conflict.

```markdown
## YYYY-MM-DD: <short decision title> (<#issue or area>)

**Decision.** <what was decided, in a sentence or two>

**Why.** <the reasoning that settled it>

**Rejected.** <the alternatives considered, and why each lost>

**Companion.** <link to the GitHub issue holding the full reasoning>
```

---

## 2026-10-14: Started from the add-on template (repository setup)

**Decision.** This repository started from `UAMS-Web/statamic-addon-template`. It carries no synced files (`.agents/`, `.claude/`, `.cursor/`) and no `LICENSE`, and `composer.json` has no `license` field.

**Why.** The shared rules and skills reach a repository by onboarding it to the sync in `UAMS-Web/uams-claude-skills`, not by copying a snapshot that goes stale. The license waits on the general decision from UAMS legal in `UAMS-Web/uams-statamic#261`.

**Rejected.** Snapshotting the synced files into the template: every new add-on would begin with a stale copy that conflicts with its first sync pull request. Copying a license from an existing add-on: legal has not decided which license applies.

**Companion.** `UAMS-Web/uams-statamic#261`.

---

## 2026-11-02: Named the package `uams-web/aether-tables` and kept the config file

**Decision.** The package is `uams-web/aether-tables`, namespace `UAMSWeb\Aether\Tables\`, handle `aether-tables`. `config/aether-tables.php` stays as an empty file, published with the tag `aether-tables-config`.

**Why.** The template's name would collide on Packagist. The table sanitizer and pasted-table code that moves in next is expected to need settings, so the publish path is wired from the start instead of added later.

**Rejected.** Dropping the config file and its test now: it would have to be added back when the code move needs its first setting.

**Companion.** `UAMS-Web/aether-tables#2`.

---

## 2026-11-02: Moved the table sanitizer and paste handling in without the site

**Decision.** The sanitizer, the pasted-table parser, the `sanitize_table_cell` modifier and the two trigger listeners live here, with their tests. The config file is `config/tables.php`, merged under the unchanged `tables` key and published with the tag `aether-tables-config`; this replaces the `config/aether-tables.php` of the entry above. The listeners take the site's trigger event as an object with `entry`, `triggerHandle` and `formValues`, registered for the class named in `tables.trigger_event` (default `App\Events\TriggerActivated`), so nothing under `src/` imports from `App\`. The add-on adds its modifier to `statamic.antlers.allowedContentModifiers`. It carries its own `SpreadsheetImport`. The Table set's template, script and styles stay in the site.

**Why.** Every handle and key stays as it is, so stored entries and the importer's writes are unaffected. Statamic runs only allowlisted modifiers on content data, and its default list covers `App\Modifiers` but not an add-on's, so without the allowlist entry the sanitizer would be skipped silently and cells would render unsanitized once the site deletes its copy. The site's `SpreadsheetImport` is also used by another site class, so the site keeps its copy.

**Rejected.** Moving `TriggerActivated` into the add-on: the site's route and its other listeners use it. Depending on the site class by import: it fails the rule that the add-on imports nothing from `App\`. Moving the template, script and styles: they are tied to the site's theme.

**Companion.** `UAMS-Web/aether-tables#3`.
