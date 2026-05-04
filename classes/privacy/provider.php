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
 * Privacy provider for local_certhistory.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Handles GDPR export and deletion for certificate snapshot data.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection Metadata collection to populate.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_certhistory_certs',
            [
                'userid' => 'privacy:metadata:local_certhistory_certs:userid',
                'studentname' => 'privacy:metadata:local_certhistory_certs:studentname',
                'email' => 'privacy:metadata:local_certhistory_certs:email',
                'coursename' => 'privacy:metadata:local_certhistory_certs:coursename',
                'certname' => 'privacy:metadata:local_certhistory_certs:certname',
                'code' => 'privacy:metadata:local_certhistory_certs:code',
                'timecreated' => 'privacy:metadata:local_certhistory_certs:timecreated',
            ],
            'privacy:metadata:local_certhistory_certs'
        );
        return $collection;
    }

    /**
     * Return the contexts that contain personal data for the given user.
     *
     * @param int $userid User ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();
        if ($DB->record_exists('local_certhistory_certs', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * Return all users who have data in the given context.
     *
     * @param userlist $userlist The userlist to populate.
     */
    public static function get_users_in_context(userlist $userlist): void {
        if (!$userlist->get_context() instanceof \context_system) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT DISTINCT userid FROM {local_certhistory_certs}', []);
    }

    /**
     * Export all personal data for the user in the given contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts to export data for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }

            $records = $DB->get_records('local_certhistory_certs', ['userid' => $userid]);
            foreach ($records as $record) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_certhistory'), $record->id],
                    (object)[
                        'studentname' => $record->studentname,
                        'email' => $record->email,
                        'coursename' => $record->coursename,
                        'certname' => $record->certname,
                        'code' => $record->code,
                        'timecreated' => transform::datetime($record->timecreated),
                    ]
                );
            }
        }
    }

    /**
     * Delete all personal data for all users in the given context.
     *
     * @param \context $context Context to delete data in.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if (!$context instanceof \context_system) {
            return;
        }
        $DB->delete_records('local_certhistory_certs');
    }

    /**
     * Delete all personal data for the user in the given approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts to delete data in.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }
            $DB->delete_records('local_certhistory_certs', ['userid' => $userid]);
        }
    }

    /**
     * Delete personal data for multiple users in the given context.
     *
     * @param approved_userlist $userlist The approved userlist to delete data for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        if (!$userlist->get_context() instanceof \context_system) {
            return;
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_certhistory_certs', "userid $insql", $inparams);
    }
}
