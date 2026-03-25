<?php

namespace local_certhistory\task;

defined('MOODLE_INTERNAL') || die();

use local_certhistory\services\repository;
use local_certhistory\services\pdf_service;

class import_existing_certificates extends \core\task\adhoc_task {

    public function execute(): void {
        $issues = repository::get_unsnapshotted_issues_recordset();

        foreach ($issues as $issue) {
            $record                  = new \stdClass();
            $record->userid          = $issue->userid;
            $record->issueid         = $issue->id;
            $record->customcertid    = $issue->customcertid;
            $record->courseid        = $issue->courseid;
            $record->coursename      = $issue->coursename;
            $record->certname        = $issue->certname;
            $record->code            = $issue->code;
            $record->timecreated     = $issue->timecreated;
            $record->timesnapshotted = time();

            $recordid = repository::insert_snapshot($record);

            $customcert = (object)['templateid' => $issue->templateid];
            pdf_service::store_pdf($customcert, $issue->userid, $recordid);
        }

        $issues->close();
    }
}
