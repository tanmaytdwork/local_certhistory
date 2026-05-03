<?php
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
 * Adhoc task to import existing certificates.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\task;

use local_certhistory\services\repository;
use local_certhistory\services\pdf_service;

/**
 * Snapshots all existing customcert issues that have not yet been recorded.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_existing_certificates extends \core\task\adhoc_task {
    /**
     * Execute the task.
     */
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
            $record->studentname     = trim(($issue->firstname ?? '') . ' ' . ($issue->lastname ?? ''));
            $record->email           = $issue->email ?? '';
            $record->timecreated     = $issue->timecreated;
            $record->timesnapshotted = time();

            $recordid = repository::insert_snapshot($record);

            $customcert = (object)['templateid' => $issue->templateid];
            pdf_service::store_pdf($customcert, $issue->userid, $recordid);
        }

        $issues->close();
    }
}
