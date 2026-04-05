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
 * Event observer for certificate issuance.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory;

use local_certhistory\services\repository;
use local_certhistory\services\pdf_service;

/**
 * Observes Moodle events related to certificate issuance.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Handle the certificate issued event.
     *
     * @param \mod_customcert\event\issue_created $event The event object.
     */
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
