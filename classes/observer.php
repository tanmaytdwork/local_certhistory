<?php

namespace local_certhistory;

defined('MOODLE_INTERNAL') || die();

use local_certhistory\services\repository;
use local_certhistory\services\pdf_service;

class observer {

 
    public static function certificate_issued(\mod_customcert\event\issue_created $event): void {
        $issueid = $event->objectid;

        if (repository::snapshot_exists($issueid)) {
            return;
        }

        $issue = repository::get_issue($issueid);
        if (!$issue) {
            return;
        }

        $customcert = repository::get_customcert($issue->customcertid);
        if (!$customcert) {
            return;
        }

        $course = repository::get_course($customcert->course);
        if (!$course) {
            return;
        }

        $record = new \stdClass();
        $record->userid = $issue->userid;
        $record->issueid = $issueid;
        $record->customcertid = $issue->customcertid;
        $record->courseid = $course->id;
        $record->coursename = $course->fullname;
        $record->certname = $customcert->name;
        $record->code = $issue->code;
        $record->timecreated = $issue->timecreated;
        $record->timesnapshotted = time();

        $recordid = repository::insert_snapshot($record);

        pdf_service::store_pdf($customcert, $issue->userid, $recordid);
    }
}
