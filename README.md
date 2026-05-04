# Certificate History

A Moodle local plugin that permanently archives `mod_customcert` certificate issuances as immutable snapshots. When a course or certificate activity is deleted, Moodle's built-in "My certificates" page silently drops those records — this plugin prevents that data loss entirely.

## Features

- Automatically snapshots every certificate at the exact moment it is issued, capturing metadata and a rendered PDF
- Snapshots are fully independent of the original course, activity, and user — data survives deletion of any of them
- Retroactive backfill on first install — an adhoc task snapshots all pre-existing `customcert_issues` so no historical data is lost
- Students can view their full certificate history from their Moodle profile under the "My Certificate History" page, including certificates from courses that have since been deleted
- Admin certificate management page under Site Administration → Reports with search and pagination across all users
- Public certificate verification page at `/local/certhistory/verify.php` — anyone can confirm a certificate is authentic by entering its unique code
- Web service API with two endpoints: retrieve a filtered/paginated list of certificates, or fetch a stored PDF as base64

## Requirements

- Moodle 4.4 or later
- PHP 8.2 or later
- `mod_customcert` installed and active

## Installation

1. Copy the `certhistory` folder into `local/` in your Moodle root directory.
2. Visit Site Administration → Notifications to run the plugin installation.
3. An adhoc task will run automatically to backfill snapshots for all previously issued certificates.

## Usage

### For students

Certificates are captured automatically — no action required. Students can view their full certificate history, including certificates from deleted courses, from the plugin's history page.

### For administrators

A searchable table of all certificates across the platform is available under Site Administration → Reports → Certificate History. Search by student name, email, course name, certificate name, or verification code.

### Certificate verification

Anyone can verify a certificate at `/local/certhistory/verify.php` by entering the unique code printed on the certificate. The page displays the holder's name, certificate name, course, and issue date.

### Web service API

Two web service functions are available for external integrations (requires `local/certhistory:viewall`):

- `local_certhistory_get_certificates` — returns a paginated, filterable list of certificate snapshots
- `local_certhistory_get_certificate_pdf` — returns the stored PDF for a given snapshot as a base64-encoded string


## Privacy

The plugin stores a copy of the student's name and email taken from their Moodle user record at the time of certificate issuance. This data is retained in `local_certhistory_certs` independently of the user account and is intentional — it is what makes the snapshot survive account deletion.

The plugin implements Moodle's privacy API. When a GDPR data export request is submitted, all certificate snapshots belonging to the user are included. When a data deletion request is processed, all snapshots for that user are permanently removed from the database.

## Bug tracker

Please report issues at: https://github.com/tanmaytdwork/moodle-local_certhistory/issues

## License

GNU GPL v3 or later — http://www.gnu.org/copyleft/gpl.html

## Author

Tanmay Deshmukh
