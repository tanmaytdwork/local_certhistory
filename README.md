# Certificate History

## Overview
**Certificate History** is a Moodle local plugin that solves a critical data-loss problem in `mod_customcert`. When courses or certificate activities are deleted, Moodle's built-in "My certificates" page silently drops all certificates from those courses due to an `INNER JOIN` on the course table. This plugin preserves certificates permanently by capturing immutable snapshots — both metadata and a rendered PDF — at the exact moment of issuance, completely independent of the original course or activity lifecycle.

## Key Capabilities

The plugin listens for `\mod_customcert\event\issue_created` and "captures an immutable snapshot of the certificate metadata and a rendered PDF" the instant a certificate is issued. Users can view, download, and share their full certificate history even after course deletion, while administrators can search and manage certificates across all users from a dedicated reports page.

## Technical Requirements
- Moodle 4.2 or later
- PHP 8.1 or later
- `mod_customcert` installed and active

## Installation Process
Copy the `certhistory` folder to `local/`, visit Site Administration → Notifications to trigger the DB upgrade, and the plugin activates immediately. On first install, an adhoc task runs automatically to retroactively snapshot all previously issued certificates still present in `customcert_issues`.

## Notable Features

**Event-Driven Snapshot Architecture**: Rather than patching `mod_customcert`, the plugin uses Moodle's standard observer mechanism to hook into `issue_created` events. A unique constraint on `issueid` in the database makes the observer "idempotent — a duplicate snapshot cannot be created even if the event fires twice."

**PDF Preservation**: Generated PDFs are stored via Moodle's File Storage API under `CONTEXT_SYSTEM`, meaning they "survive course and activity deletion" and are served securely through the standard pluginfile mechanism with ownership enforcement.

**Retroactive Import**: A scheduled adhoc task runs on installation and "iterates all unsnapshotted issues to create snapshots and PDFs for existing certificates," ensuring no historical data is lost at the point of adoption.

**Public Certificate Verification**: A login-free verification page at `/local/certhistory/verify.php` allows anyone to look up a certificate by its unique code, displaying the holder's name, certificate name, course, and issue date as a public attestation of authenticity.

**Admin Search & Management**: A dedicated admin page under Site Administration → Reports provides a searchable, sortable table of all certificates across the platform, with search across usernames, course names, certificate names, and verification codes.

## Author & License
Created by Tanmay Deshmukh under GNU GPL v3 or later licensing.
