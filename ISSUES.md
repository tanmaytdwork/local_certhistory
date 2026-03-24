# Code Issues — local_certhistory

Issues identified against the [Moodle Coding Style Guide](https://moodledev.io/general/development/policies/codingstyle).

---

## Issue 1 — Missing File-Level Copyright/License Headers

**Severity:** High
**Affects:** All files
**Files:**
- `version.php`
- `lib.php`
- `index.php`
- `download.php`
- `classes/observer.php`
- `classes/tables/certhistory_table.php`
- `db/access.php`
- `db/events.php`
- `db/upgrade.php`
- `db/install.xml`
- `lang/en/local_certhistory.php`

**Description:**
Moodle requires every PHP file to begin with a GPL license and copyright block. None of the files in this plugin include it. This is mandatory for any plugin distributed or reviewed as part of the Moodle ecosystem and will cause failures in the official `moodle-plugin-ci` linting pipeline.

**Required format:**
```php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Description of this file.
 *
 * @package    local_certhistory
 * @copyright  2024 Your Name <you@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

---

## Issue 2 — Missing PHPDoc Blocks on Classes and Methods

**Severity:** High
**Affects:** Classes and all their methods, standalone functions
**Files:**
- `classes/observer.php` — class `observer`, methods `certificate_issued()`, `store_pdf()`
- `classes/tables/certhistory_table.php` — class `certhistory_table`, methods `__construct()`, `setup_sql()`, `col_rownumber()`, `col_coursename()`, `col_certname()`, `col_timecreated()`, `col_code()`, `col_enrollstatus()`, `col_download()`
- `lib.php` — functions `local_certhistory_pluginfile()`, `local_certhistory_extend_navigation()`, `local_certhistory_myprofile_navigation()`

**Description:**
Moodle coding standards require PHPDoc docblocks for every class, method, and function. These must include a description, `@param` tags for each parameter (with type and description), and a `@return` tag. The entire plugin is missing all docblocks. Without them, auto-generated API documentation is incomplete and code reviewers cannot understand intent or expected types at a glance.

**Example of what is required:**
```php
/**
 * Handles the certificate issued event and stores a permanent snapshot.
 *
 * @param \mod_customcert\event\issue_created $event The triggered event.
 * @return void
 */
public static function certificate_issued(\mod_customcert\event\issue_created $event): void {
```

---

## Issue 3 — Missing `global` Declarations in `download.php`

**Severity:** High
**Affects:** `download.php` lines 12 and 14
**File:** `download.php`

**Description:**
`$DB` and `$USER` are used directly without being declared as global variables. While Moodle's entry-point bootstrap makes these technically available in the global scope, Moodle coding standards require explicitly declaring them with `global` before use. Omitting this makes the dependency invisible, hinders static analysis tools, and is flagged as an error by `moodle-plugin-ci`.

**Current code:**
```php
$record = $DB->get_record('local_certhistory_certs', ['id' => $id], '*', MUST_EXIST);

if ($record->userid != $USER->id) {
```

**Fix — add at the top of the file after `require_login()`:**
```php
global $DB, $USER;
```

---

## Issue 4 — Commented-Out Code in `index.php`

**Severity:** Low
**Affects:** `index.php` line 21
**File:** `index.php`

**Description:**
Moodle coding standards explicitly prohibit leaving commented-out code in committed files. Dead code should be removed entirely. If a line was disabled temporarily during development, it must be cleaned up before the code is considered production-ready. The presence of this line suggests unfinished cleanup.

**Current code:**
```php
// $PAGE->set_heading($title);
```

**Fix:** Delete the line entirely.

---

## Issue 5 — Inconsistent Indentation

**Severity:** Low
**Affects:** `classes/tables/certhistory_table.php` line 18
**File:** `classes/tables/certhistory_table.php`

**Description:**
Moodle requires 4-space indentation throughout. The property declaration `$rownumber` on line 18 is indented with 1 space instead of 4, making it inconsistent with the adjacent `$userid` declaration on line 16. While functionally harmless, this violates the style guide and will be flagged by PHP_CodeSniffer with the Moodle ruleset.

**Current code:**
```php
    protected int $userid;
 protected int $rownumber = 0;   // ← 1 space
```

**Fix:**
```php
    protected int $userid;
    protected int $rownumber = 0;  // ← 4 spaces
```

---

## Issue 6 — Missing Blank Line After `<?php`

**Severity:** Low
**Affects:** 3 files
**Files:**
- `version.php` (line 1–2)
- `db/access.php` (line 1–2)
- `db/events.php` (line 1–2)

**Description:**
Moodle coding standards require a blank line between the opening `<?php` tag and the first statement. The three files above have `defined('MOODLE_INTERNAL') || die();` or `$plugin->...` immediately on the next line with no separation. This is a minor but consistent style violation caught by PHP_CodeSniffer.

**Current code:**
```php
<?php
defined('MOODLE_INTERNAL') || die();
```

**Fix:**
```php
<?php

defined('MOODLE_INTERNAL') || die();
```

---

## Issue 7 — Privacy API Declared but Not Implemented

**Severity:** Medium
**Affects:** Plugin-wide GDPR compliance
**Files:**
- `lang/en/local_certhistory.php` (line 24) — defines `privacy:metadata`
- `classes/privacy/provider.php` — **does not exist**

**Description:**
The language file includes a `privacy:metadata` string, which signals that the plugin has registered its data storage with Moodle's Privacy API. However, there is no `classes/privacy/provider.php` implementing the `\core_privacy\local\metadata\provider` interface. This creates a broken declaration — Moodle's privacy registry will look for a provider class and fail to find one. For a plugin that stores personal data (user IDs, certificate PDFs, course names), a full privacy provider is required for GDPR compliance and plugin directory submission.

**Fix — create `classes/privacy/provider.php`** implementing at minimum:
```php
namespace local_certhistory\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\data_provider;

class provider implements \core_privacy\local\metadata\provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_certhistory_certs', [
            'userid'       => 'privacy:metadata:local_certhistory_certs:userid',
            'coursename'   => 'privacy:metadata:local_certhistory_certs:coursename',
            'certname'     => 'privacy:metadata:local_certhistory_certs:certname',
            'code'         => 'privacy:metadata:local_certhistory_certs:code',
            'timecreated'  => 'privacy:metadata:local_certhistory_certs:timecreated',
        ], 'privacy:metadata:local_certhistory_certs');
        return $collection;
    }
}
```

---

## Summary

| # | Issue | Severity | Files Affected |
|---|-------|----------|----------------|
| 1 | Missing copyright/license headers | High | All files |
| 2 | Missing PHPDoc blocks | High | `observer.php`, `certhistory_table.php`, `lib.php` |
| 3 | Missing `global` declarations | High | `download.php` |
| 4 | Commented-out dead code | Low | `index.php` |
| 5 | Inconsistent indentation | Low | `certhistory_table.php` |
| 6 | Missing blank line after `<?php` | Low | `version.php`, `db/access.php`, `db/events.php` |
| 7 | Privacy API declared but not implemented | Medium | `lang/en/local_certhistory.php` |
