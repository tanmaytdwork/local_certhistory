# local_certhistory 

> A local Moodle plugin built to solve a real data-loss problem in `mod_customcert`.
> Developed independently as a demonstration of Moodle plugin development, event-driven architecture, and deep Moodle API usage.

---

## The Problem I Identified

`mod_customcert` is the most widely used certificate plugin for Moodle. However, its "My certificates" page has a critical flaw:

```sql
-- Simplified version of what mod_customcert does internally
SELECT ci.* FROM {customcert_issues} ci
INNER JOIN {course} c ON c.id = ci.courseid   -- <-- the problem
WHERE ci.userid = :userid
```

The `INNER JOIN` on `{course}` means that **if a course is deleted, every certificate from that course silently disappears** from the user's view — even though the issue record in `{customcert_issues}` still exists.

This creates three compounding problems:

| Scenario | What happens to the user |
|----------|--------------------------|
| Course is deleted | Certificate vanishes from "My certificates" |
| Certificate activity is removed | Certificate vanishes from "My certificates" |
| Template is modified or deleted | PDF cannot be regenerated — it is lost forever |

For institutions that retire courses, run short programs, or periodically clean up old content, this is a serious issue. Students lose verifiable proof of their achievements through no fault of their own.

---

## My Solution: Event-Driven Snapshot Architecture

Rather than patching `mod_customcert` (which would create a maintenance burden and break with updates), I designed a fully decoupled local plugin that **listens for the `issue_created` event** and captures an immutable snapshot at the exact moment of issuance.

```
mod_customcert issues a certificate
        ↓
Fires \mod_customcert\event\issue_created
        ↓
local_certhistory\observer::certificate_issued()
        ↓
  ┌─────────────────────────────────────────┐
  │  1. Snapshot metadata into DB           │
  │     (course name, cert name, code,      │
  │      user ID, timestamps, activity IDs) │
  │                                         │
  │  2. Generate + store PDF to file API    │
  │     (actual rendered PDF, frozen in     │
  │      Moodle file storage forever)       │
  └─────────────────────────────────────────┘
        ↓
Data is permanently preserved — independent of
the original course, activity, or template
```



---

## Moodle APIs Utilised

This plugin intentionally exercises a broad range of Moodle's core APIs to demonstrate platform-level familiarity:

### 1. Event System (`db/events.php`, `classes/observer.php`)
Registered an observer for `\mod_customcert\event\issue_created` using Moodle's standard event observer mechanism. The observer is stateless and idempotent — a unique constraint on `issueid` in the DB prevents duplicate snapshots if the event somehow fires twice.

```php
// db/events.php
$observers = [[
    'eventname' => '\mod_customcert\event\issue_created',
    'callback'  => '\local_certhistory\observer::certificate_issued',
]];
```

### 2. File Storage API (`classes/observer.php`, `lib.php`, `download.php`)
Used `get_file_storage()` to permanently store the generated PDF inside Moodle's standard file system. Files are stored under:
- **Component:** `local_certhistory`
- **File area:** `certificates`
- **Context:** `CONTEXT_SYSTEM`
- **Itemid:** the `local_certhistory_certs` record ID

This means files survive course/activity deletion (they are associated with SYSTEM context, not course context) and can be served securely through Moodle's pluginfile mechanism.


### 3. Pluginfile Callback (`lib.php`)
Implemented `local_certhistory_pluginfile()` so that stored PDFs are served via Moodle's standard `/pluginfile.php/` URL scheme. This function enforces ownership — a user can only retrieve their own certificate files — before calling `$file->send_file()`.

### 4. Capability System (`db/access.php`)
Defined a custom capability `local/certhistory:view` at `CONTEXT_SYSTEM` level, defaulting to `CAP_ALLOW` for the `user` archetype. All pages enforce this via `require_capability()`, giving administrators the ability to restrict access if needed.

### 5. Navigation Hooks (`lib.php`)
Integrated into two points of Moodle's navigation tree without touching core:
- `local_certhistory_extend_navigation()` — adds a sidebar link with a certificate icon via `$navigation->add()`
- `local_certhistory_myprofile_navigation()` — adds a link in the user profile's "Miscellaneous" section, shown only on the user's own profile



## Plugin Structure

```
local/certhistory/
├── version.php                        #requirement, mod_customcert dependency
├── index.php                          
├── download.php                       #Secure PDF endpoint: ownership check, send_file()
├── lib.php                            
├── db/
│   ├── access.php                     
│   ├── events.php                     #Observer registration for issue_created
│   ├── install.xml                   
│   └── upgrade.php                    
├── classes/
│   ├── observer.php                                
│   └── tables/
│       └── certhistory_table.php     
└── lang/
    └── en/
        └── local_certhistory.php      
```

---


## Installation

1. Copy the `certhistory` directory to `{moodleroot}/local/`
2. Ensure `mod_customcert` is installed (the plugin dependency will prevent install otherwise)
3. Navigate to **Site Administration → Notifications** to run the DB upgrade
4. The plugin activates immediately — all new certificate issuances will be captured automatically

> **Note:** Certificates issued *before* installation are not retroactively captured. The observer only fires on new `issue_created` events going forward. Existing certificates remain accessible through `mod_customcert`'s own interface as long as their courses exist.

---


