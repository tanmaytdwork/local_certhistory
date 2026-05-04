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
 * External function: get_certificates.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Returns a paginated list of certificate snapshots with optional filters.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_certificates extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Filter by user ID', VALUE_DEFAULT, 0),
            'email' => new external_value(PARAM_EMAIL, 'Filter by student email', VALUE_DEFAULT, ''),
            'courseid' => new external_value(PARAM_INT, 'Filter by course ID', VALUE_DEFAULT, 0),
            'certname' => new external_value(PARAM_TEXT, 'Partial search on cert name', VALUE_DEFAULT, ''),
            'studentname' => new external_value(PARAM_TEXT, 'Partial search on student name', VALUE_DEFAULT, ''),
            'code' => new external_value(PARAM_ALPHANUMEXT, 'Exact certificate code', VALUE_DEFAULT, ''),
            'limit' => new external_value(PARAM_INT, 'Number of records to return', VALUE_DEFAULT, 20),
            'offset' => new external_value(PARAM_INT, 'Offset for pagination', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $userid Filter by user ID.
     * @param string $email Filter by student email.
     * @param int $courseid Filter by course ID.
     * @param string $certname Partial search on cert name.
     * @param string $studentname Partial search on student name.
     * @param string $code Exact certificate code.
     * @param int $limit Number of records to return.
     * @param int $offset Offset for pagination.
     * @return array
     */
    public static function execute(
        int $userid = 0,
        string $email = '',
        int $courseid = 0,
        string $certname = '',
        string $studentname = '',
        string $code = '',
        int $limit = 20,
        int $offset = 0
    ): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'email' => $email,
            'courseid' => $courseid,
            'certname' => $certname,
            'studentname' => $studentname,
            'code' => $code,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/certhistory:viewall', $context);

        $params['limit'] = max(1, min((int) $params['limit'], 100));
        $params['offset'] = max(0, (int) $params['offset']);

        [$where, $sqlparams] = self::build_where($params);

        $fields = "id, userid, courseid, coursename, certname, code, studentname, email, timecreated";
        $sql = "SELECT $fields FROM {local_certhistory_certs} WHERE $where ORDER BY timecreated DESC";

        $records = $DB->get_records_sql($sql, $sqlparams, $params['offset'], $params['limit']);
        $total = $DB->count_records_sql("SELECT COUNT(1) FROM {local_certhistory_certs} WHERE $where", $sqlparams);

        $certificates = [];
        foreach ($records as $record) {
            $certificates[] = [
                'id' => (int) $record->id,
                'userid' => (int) $record->userid,
                'courseid' => (int) $record->courseid,
                'coursename' => $record->coursename,
                'certname' => $record->certname,
                'code' => $record->code,
                'studentname' => $record->studentname,
                'email' => $record->email,
                'timecreated' => (int) $record->timecreated,
            ];
        }

        return [
            'certificates' => $certificates,
            'total' => (int) $total,
        ];
    }

    /**
     * Define the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'certificates' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Snapshot record ID'),
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'courseid' => new external_value(PARAM_INT, 'Course ID'),
                    'coursename' => new external_value(PARAM_TEXT, 'Course name at time of issue'),
                    'certname' => new external_value(PARAM_TEXT, 'Certificate name at time of issue'),
                    'code' => new external_value(PARAM_ALPHANUMEXT, 'Verification code'),
                    'studentname' => new external_value(PARAM_TEXT, 'Student full name at time of issue'),
                    'email' => new external_value(PARAM_EMAIL, 'Student email at time of issue'),
                    'timecreated' => new external_value(PARAM_INT, 'Unix timestamp when certificate was issued'),
                ])
            ),
            'total' => new external_value(PARAM_INT, 'Total number of matching records'),
        ]);
    }

    /**
     * Build the WHERE clause and params array from the validated input.
     *
     * @param array $params Validated parameters.
     * @return array [$where, $sqlparams]
     */
    private static function build_where(array $params): array {
        global $DB;

        $conditions = ['1=1'];
        $sqlparams = [];

        if (!empty($params['userid'])) {
            $conditions[] = 'userid = :userid';
            $sqlparams['userid'] = (int) $params['userid'];
        }

        if (!empty($params['email'])) {
            $conditions[] = $DB->sql_like('email', ':email', false);
            $sqlparams['email'] = $params['email'];
        }

        if (!empty($params['courseid'])) {
            $conditions[] = 'courseid = :courseid';
            $sqlparams['courseid'] = (int) $params['courseid'];
        }

        if (!empty($params['certname'])) {
            $conditions[] = $DB->sql_like('certname', ':certname', false);
            $sqlparams['certname'] = '%' . $DB->sql_like_escape($params['certname']) . '%';
        }

        if (!empty($params['studentname'])) {
            $conditions[] = $DB->sql_like('studentname', ':studentname', false);
            $sqlparams['studentname'] = '%' . $DB->sql_like_escape($params['studentname']) . '%';
        }

        if (!empty($params['code'])) {
            $conditions[] = 'code = :code';
            $sqlparams['code'] = $params['code'];
        }

        return [implode(' AND ', $conditions), $sqlparams];
    }
}
