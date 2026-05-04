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
 * Database access layer.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\services;

/**
 * Provides all database queries for the plugin.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository {
    /**
     * Check whether a snapshot already exists for a given issue.
     *
     * @param int $issueid The issue ID.
     * @return bool
     */
    public static function snapshot_exists(int $issueid): bool {
        global $DB;
        return $DB->record_exists('local_certhistory_certs', ['issueid' => $issueid]);
    }

    /**
     * Get a customcert issue record.
     *
     * @param int $issueid The issue ID.
     * @return \stdClass|null
     */
    public static function get_issue(int $issueid): ?\stdClass {
        global $DB;
        return $DB->get_record('customcert_issues', ['id' => $issueid]) ?: null;
    }

    /**
     * Get a customcert record.
     *
     * @param int $customcertid The customcert ID.
     * @return \stdClass|null
     */
    public static function get_customcert(int $customcertid): ?\stdClass {
        global $DB;
        return $DB->get_record('customcert', ['id' => $customcertid]) ?: null;
    }

    /**
     * Get a course record.
     *
     * @param int $courseid The course ID.
     * @return \stdClass|null
     */
    public static function get_course(int $courseid): ?\stdClass {
        global $DB;
        return $DB->get_record('course', ['id' => $courseid]) ?: null;
    }

    /**
     * Get a customcert template record.
     *
     * @param int $templateid The template ID.
     * @return \stdClass|null
     */
    public static function get_template(int $templateid): ?\stdClass {
        global $DB;
        return $DB->get_record('customcert_templates', ['id' => $templateid]) ?: null;
    }

    /**
     * Insert a new certificate snapshot record.
     *
     * @param \stdClass $record The record to insert.
     * @return int The new record ID.
     */
    public static function insert_snapshot(\stdClass $record): int {
        global $DB;
        return $DB->insert_record('local_certhistory_certs', $record);
    }

    /**
     * Get a snapshot record by ID.
     *
     * @param int $id The snapshot ID.
     * @return \stdClass|null
     */
    public static function get_snapshot(int $id): ?\stdClass {
        global $DB;
        return $DB->get_record('local_certhistory_certs', ['id' => $id]) ?: null;
    }

    /**
     * Get a snapshot record by ID, throwing an exception if not found.
     *
     * @param int $id The snapshot ID.
     * @return \stdClass
     */
    public static function get_snapshot_must_exist(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_certhistory_certs', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Get a recordset of all issues that do not yet have a snapshot.
     *
     * @return \moodle_recordset
     */
    public static function get_unsnapshotted_issues_recordset(): \moodle_recordset {
        global $DB;
        $sql = "SELECT ci.id, ci.userid, ci.customcertid, ci.code, ci.timecreated,
                       cc.name AS certname, cc.templateid,
                       c.id AS courseid, c.fullname AS coursename,
                       u.firstname, u.lastname, u.email
                  FROM {customcert_issues} ci
                  JOIN {customcert} cc ON cc.id = ci.customcertid
                  JOIN {course} c ON c.id = cc.course
             LEFT JOIN {user} u ON u.id = ci.userid
             LEFT JOIN {local_certhistory_certs} lch ON lch.issueid = ci.id
                 WHERE lch.id IS NULL";
        return $DB->get_recordset_sql($sql);
    }

    /**
     * Get a snapshot record by verification code.
     *
     * @param string $code The certificate code.
     * @return \stdClass|null
     */
    public static function get_snapshot_by_code(string $code): ?\stdClass {
        global $DB;
        return $DB->get_record('local_certhistory_certs', ['code' => $code]) ?: null;
    }

    /**
     * Get a user record with name fields.
     *
     * @param int $userid The user ID.
     * @return \stdClass|null
     */
    public static function get_user(int $userid): ?\stdClass {
        global $DB;
        return $DB->get_record(
            'user',
            ['id' => $userid],
            'id, firstname, lastname, email'
        ) ?: null;
    }
}
