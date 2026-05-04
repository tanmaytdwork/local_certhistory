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
 * Integration tests for the certificate issued observer.
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory;

use advanced_testcase;
use local_certhistory\services\repository;

/**
 * Tests for observer::certificate_issued().
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     local_certhistory
 * @coversDefaultClass \local_certhistory\observer
 */
final class observer_test extends advanced_testcase {
    /**
     * Seed a course, customcert record, and user in the DB.
     *
     * @return array [$course, $user, $customcert]
     */
    private function seed_environment(): array {
        global $DB;

        $generator = $this->getDataGenerator();

        $course = $generator->create_course(['fullname' => 'Observer Test Course']);

        $user = $generator->create_user([
            'firstname' => 'Bob',
            'lastname' => 'Builder',
            'email' => 'bob@example.com',
        ]);

        $customcertid = $DB->insert_record('customcert', (object)[
            'course' => $course->id,
            'name' => 'Observer Test Cert',
            'templateid' => 0,
        ]);

        $customcert = $DB->get_record('customcert', ['id' => $customcertid], '*', MUST_EXIST);

        return [$course, $user, $customcert];
    }

    /**
     * Insert a customcert_issues row and return the issue id.
     *
     * @param int $userid User ID.
     * @param int $customcertid Customcert ID.
     * @param string $code Certificate code.
     * @return int
     */
    private function insert_issue(int $userid, int $customcertid, string $code): int {
        global $DB;
        return $DB->insert_record('customcert_issues', (object)[
            'userid' => $userid,
            'customcertid' => $customcertid,
            'code' => $code,
            'timecreated' => time(),
        ]);
    }

    /**
     * Directly call the observer with a given issue id.
     *
     * @param int $issueid Issue id to pass as objectid.
     * @param int $relateduserid User id for the event.
     */
    private function call_observer(int $issueid, int $relateduserid = 0): void {
        \local_certhistory\observer::certificate_issued(
            \mod_customcert\event\issue_created::create([
                'objectid' => $issueid,
                'context' => \context_system::instance(),
                'relateduserid' => $relateduserid,
            ])
        );
    }

    /**
     * Test that a snapshot is created for a valid issue.
     *
     * @covers ::certificate_issued
     */
    public function test_snapshot_is_created_for_valid_issue(): void {
        $this->resetAfterTest();

        [$course, $user, $customcert] = $this->seed_environment();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'OBSTEST1');

        $this->call_observer($issueid, $user->id);

        $this->assertTrue(repository::snapshot_exists($issueid));
    }

    /**
     * Test that studentname is stored as firstname space lastname.
     *
     * @covers ::certificate_issued
     */
    public function test_snapshot_stores_studentname_as_firstname_space_lastname(): void {
        global $DB;
        $this->resetAfterTest();

        [$course, $user, $customcert] = $this->seed_environment();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'OBSTEST2');

        $this->call_observer($issueid, $user->id);

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertEquals('Bob Builder', $snapshot->studentname);
    }

    /**
     * Test that the user email is stored in the snapshot.
     *
     * @covers ::certificate_issued
     */
    public function test_snapshot_stores_user_email(): void {
        global $DB;
        $this->resetAfterTest();

        [$course, $user, $customcert] = $this->seed_environment();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'OBSTEST3');

        $this->call_observer($issueid, $user->id);

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertEquals('bob@example.com', $snapshot->email);
    }

    /**
     * Test that the course name at time of issue is stored in the snapshot.
     *
     * @covers ::certificate_issued
     */
    public function test_snapshot_stores_coursename_at_issue_time(): void {
        global $DB;
        $this->resetAfterTest();

        [$course, $user, $customcert] = $this->seed_environment();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'OBSTEST4');

        $this->call_observer($issueid, $user->id);

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertEquals('Observer Test Course', $snapshot->coursename);
    }

    /**
     * Test that the certificate name at time of issue is stored in the snapshot.
     *
     * @covers ::certificate_issued
     */
    public function test_snapshot_stores_certname_at_issue_time(): void {
        global $DB;
        $this->resetAfterTest();

        [$course, $user, $customcert] = $this->seed_environment();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'OBSTEST5');

        $this->call_observer($issueid, $user->id);

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertEquals('Observer Test Cert', $snapshot->certname);
    }

    /**
     * Test that a second event for the same issue does not create a duplicate snapshot.
     *
     * @covers ::certificate_issued
     */
    public function test_second_event_for_same_issue_does_not_create_duplicate(): void {
        global $DB;
        $this->resetAfterTest();

        [$course, $user, $customcert] = $this->seed_environment();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'DUPTEST');

        $this->call_observer($issueid, $user->id);
        $this->call_observer($issueid, $user->id);

        $count = $DB->count_records('local_certhistory_certs', ['issueid' => $issueid]);
        $this->assertEquals(1, $count);
    }

    /**
     * Test that no snapshot is created when the issue record is not found.
     *
     * @covers ::certificate_issued
     */
    public function test_no_snapshot_when_issue_record_not_found(): void {
        $this->call_observer(99999, 0);
        $this->assertFalse(repository::snapshot_exists(99999));
    }

    /**
     * Test that no snapshot is created when the customcert record is not found.
     *
     * @covers ::certificate_issued
     */
    public function test_no_snapshot_when_customcert_not_found(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $issueid = $this->insert_issue($user->id, 99999, 'NOCERT');

        $this->call_observer($issueid, $user->id);

        $this->assertFalse(repository::snapshot_exists($issueid));
    }

    /**
     * Test that a snapshot is created with empty fields when the user is deleted.
     *
     * @covers ::certificate_issued
     */
    public function test_snapshot_created_with_empty_fields_when_user_is_deleted(): void {
        global $DB;
        $this->resetAfterTest();

        [$course, $user, $customcert] = $this->seed_environment();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'DELUSER');

        delete_user($user);

        $this->call_observer($issueid, $user->id);

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid]);
        $this->assertNotFalse($snapshot, 'Snapshot should still be created even when user is deleted');
        $this->assertEquals('', $snapshot->studentname);
        $this->assertEquals('', $snapshot->email);
    }
}
