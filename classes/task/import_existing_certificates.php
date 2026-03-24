<?php

namespace local_certhistory\task;

defined('MOODLE_INTERNAL') || die();

use local_certhistory\services\repository;
use local_certhistory\services\pdf_service;

class import_existing_certificates extends \core\task\adhoc_task {

    public function execute(): void {
        $issues = repository::get_all_issues_recordset();

        foreach ($issues as $issue) {
            if (repository::snapshot_exists($issue->id)) {
                continue;
            }

            $customcert = repository::get_customcert($issue->customcertid);
            if (!$customcert) {
                continue;
            }

            $course = repository::get_course($customcert->course);
            if (!$course) {
                continue;
            }

            $record = new \stdClass();
            $record->userid          = $issue->userid;
            $record->issueid         = $issue->id;
            $record->customcertid    = $issue->customcertid;
            $record->courseid        = $course->id;
            $record->coursename      = $course->fullname;
            $record->certname        = $customcert->name;
            $record->code            = $issue->code;
            $record->timecreated     = $issue->timecreated;
            $record->timesnapshotted = time();

            $recordid = repository::insert_snapshot($record);

            pdf_service::store_pdf($customcert, $issue->userid, $recordid);
        }

        $issues->close();
    }
}
