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
 * Integration tests for the import_existing_certificates adhoc task.
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory;

use advanced_testcase;
use local_certhistory\services\repository;
use local_certhistory\task\import_existing_certificates;

/**
 * Tests for the import_existing_certificates adhoc task.
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     local_certhistory
 * @coversDefaultClass \local_certhistory\task\import_existing_certificates
 */
final class import_existing_certificates_test extends advanced_testcase {
    /**
     * Seed a course and customcert in the DB, returning both records.
     *
     * @param string $coursename Course full name.
     * @param string $certname Certificate name.
     * @return array [$course, $customcert]
     */
    private function seed_course_and_cert(string $coursename = 'Test Course', string $certname = 'Test Cert'): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['fullname' => $coursename]);

        $customcertid = $DB->insert_record('customcert', (object)[
            'course' => $course->id,
            'name' => $certname,
            'templateid' => 0,
        ]);
        $customcert = $DB->get_record('customcert', ['id' => $customcertid], '*', MUST_EXIST);

        return [$course, $customcert];
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
     * Run the import task.
     */
    private function run_task(): void {
        (new import_existing_certificates())->execute();
    }

    /**
     * Test that the task creates a snapshot for an unsnapshotted issue.
     *
     * @covers ::execute
     */
    public function test_task_creates_snapshot_for_unsnapshotted_issue(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        [$course, $customcert] = $this->seed_course_and_cert();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'IMPORT01');

        $this->run_task();

        $this->assertTrue(repository::snapshot_exists($issueid));
    }

    /**
     * Test that the task stores the correct student name.
     *
     * @covers ::execute
     */
    public function test_task_stores_correct_studentname(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Jane',
            'lastname' => 'Doe',
        ]);
        [$course, $customcert] = $this->seed_course_and_cert();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'IMPORT02');

        $this->run_task();

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertEquals('Jane Doe', $snapshot->studentname);
    }

    /**
     * Test that the task stores the correct email.
     *
     * @covers ::execute
     */
    public function test_task_stores_correct_email(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        [$course, $customcert] = $this->seed_course_and_cert();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'IMPORT03');

        $this->run_task();

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertEquals('jane@example.com', $snapshot->email);
    }

    /**
     * Test that the task stores the correct course name.
     *
     * @covers ::execute
     */
    public function test_task_stores_correct_coursename(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        [$course, $customcert] = $this->seed_course_and_cert('My History Course');
        $issueid = $this->insert_issue($user->id, $customcert->id, 'IMPORT04');

        $this->run_task();

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertEquals('My History Course', $snapshot->coursename);
    }

    /**
     * Test that the task stores the correct certificate name.
     *
     * @covers ::execute
     */
    public function test_task_stores_correct_certname(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        [$course, $customcert] = $this->seed_course_and_cert('Course', 'My Cert Name');
        $issueid = $this->insert_issue($user->id, $customcert->id, 'IMPORT05');

        $this->run_task();

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertEquals('My Cert Name', $snapshot->certname);
    }

    /**
     * Test that the task stores the correct certificate code.
     *
     * @covers ::execute
     */
    public function test_task_stores_correct_code(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        [$course, $customcert] = $this->seed_course_and_cert();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'MYCODE99');

        $this->run_task();

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertEquals('MYCODE99', $snapshot->code);
    }

    /**
     * Test that the task imports multiple issues in one run.
     *
     * @covers ::execute
     */
    public function test_task_imports_multiple_issues_in_one_run(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        [$course, $customcert] = $this->seed_course_and_cert();

        $issueid1 = $this->insert_issue($user->id, $customcert->id, 'MULTI01');
        $issueid2 = $this->insert_issue($user->id, $customcert->id, 'MULTI02');
        $issueid3 = $this->insert_issue($user->id, $customcert->id, 'MULTI03');

        $this->run_task();

        $this->assertTrue(repository::snapshot_exists($issueid1));
        $this->assertTrue(repository::snapshot_exists($issueid2));
        $this->assertTrue(repository::snapshot_exists($issueid3));
    }

    /**
     * Test that the task skips an already-snapshotted issue.
     *
     * @covers ::execute
     */
    public function test_task_skips_already_snapshotted_issue(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        [$course, $customcert] = $this->seed_course_and_cert();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'ALREADY1');

        repository::insert_snapshot((object)[
            'userid' => $user->id,
            'issueid' => $issueid,
            'customcertid' => $customcert->id,
            'courseid' => $course->id,
            'coursename' => 'Pre-existing',
            'certname' => 'Pre-existing',
            'code' => 'ALREADY1',
            'studentname' => 'Pre Existing',
            'email' => 'pre@example.com',
            'timecreated' => time(),
            'timesnapshotted' => time(),
        ]);

        $this->run_task();

        $count = $DB->count_records('local_certhistory_certs', ['issueid' => $issueid]);
        $this->assertEquals(1, $count);
    }

    /**
     * Test that the task does not overwrite existing snapshot data.
     *
     * @covers ::execute
     */
    public function test_task_does_not_overwrite_existing_snapshot_data(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        [$course, $customcert] = $this->seed_course_and_cert();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'ALREADY2');

        repository::insert_snapshot((object)[
            'userid' => $user->id,
            'issueid' => $issueid,
            'customcertid' => $customcert->id,
            'courseid' => $course->id,
            'coursename' => 'Original Course Name',
            'certname' => 'Original Cert Name',
            'code' => 'ALREADY2',
            'studentname' => 'Original Name',
            'email' => 'original@example.com',
            'timecreated' => time(),
            'timesnapshotted' => time(),
        ]);

        $this->run_task();

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertEquals('Original Name', $snapshot->studentname);
        $this->assertEquals('Original Course Name', $snapshot->coursename);
    }

    /**
     * Test that the task imports an issue with empty fields when the user is deleted.
     *
     * @covers ::execute
     */
    public function test_task_imports_issue_with_empty_fields_when_user_deleted(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        [$course, $customcert] = $this->seed_course_and_cert();
        $issueid = $this->insert_issue($user->id, $customcert->id, 'DELUSR01');

        delete_user($user);

        $this->run_task();

        $snapshot = $DB->get_record('local_certhistory_certs', ['issueid' => $issueid]);
        $this->assertNotFalse($snapshot, 'Snapshot should be created even when user is deleted');
        $this->assertEquals('', $snapshot->studentname);
        $this->assertEquals('', $snapshot->email);
    }

    /**
     * Test that the task runs cleanly when there are no issues.
     *
     * @covers ::execute
     */
    public function test_task_runs_cleanly_with_no_issues(): void {
        $this->resetAfterTest();

        $this->run_task();

        $this->assertTrue(true);
    }
}
