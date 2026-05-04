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
 * Integration tests for the repository service.
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\tests;

use advanced_testcase;
use local_certhistory\services\repository;

/**
 * Tests for the repository data-access layer.
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     local_certhistory
 * @coversDefaultClass \local_certhistory\services\repository
 */
class repository_test extends advanced_testcase {

    /**
     * Build a minimal valid snapshot record.
     *
     * @param array $overrides Field overrides.
     * @return \stdClass
     */
    private function make_snapshot(array $overrides = []): \stdClass {
        return (object) array_merge([
            'userid'          => 1,
            'issueid'         => 1,
            'customcertid'    => 1,
            'courseid'        => 1,
            'coursename'      => 'Test Course',
            'certname'        => 'Test Certificate',
            'code'            => 'ABC123',
            'studentname'     => 'John Doe',
            'email'           => 'john@example.com',
            'timecreated'     => time(),
            'timesnapshotted' => time(),
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // snapshot_exists
    // -------------------------------------------------------------------------

    /**
     * @covers ::snapshot_exists
     */
    public function test_snapshot_exists_returns_false_when_no_record(): void {
        $this->assertFalse(repository::snapshot_exists(999));
    }

    /**
     * @covers ::snapshot_exists
     */
    public function test_snapshot_exists_returns_true_after_insert(): void {
        $this->resetAfterTest();

        repository::insert_snapshot($this->make_snapshot(['issueid' => 42]));

        $this->assertTrue(repository::snapshot_exists(42));
    }

    // -------------------------------------------------------------------------
    // insert_snapshot
    // -------------------------------------------------------------------------

    /**
     * @covers ::insert_snapshot
     */
    public function test_insert_snapshot_returns_positive_integer_id(): void {
        $this->resetAfterTest();

        $id = repository::insert_snapshot($this->make_snapshot());

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    /**
     * @covers ::insert_snapshot
     */
    public function test_insert_snapshot_persists_all_fields(): void {
        global $DB;
        $this->resetAfterTest();

        $now = time();
        $id  = repository::insert_snapshot($this->make_snapshot([
            'userid'          => 7,
            'issueid'         => 10,
            'customcertid'    => 3,
            'courseid'        => 5,
            'coursename'      => 'My Course',
            'certname'        => 'My Cert',
            'code'            => 'XYZ999',
            'studentname'     => 'Jane Smith',
            'email'           => 'jane@example.com',
            'timecreated'     => $now,
            'timesnapshotted' => $now,
        ]));

        $row = $DB->get_record('local_certhistory_certs', ['id' => $id], '*', MUST_EXIST);

        $this->assertEquals(7,                  $row->userid);
        $this->assertEquals(10,                 $row->issueid);
        $this->assertEquals('My Course',        $row->coursename);
        $this->assertEquals('My Cert',          $row->certname);
        $this->assertEquals('XYZ999',           $row->code);
        $this->assertEquals('Jane Smith',       $row->studentname);
        $this->assertEquals('jane@example.com', $row->email);
        $this->assertEquals($now,               (int) $row->timecreated);
    }

    // -------------------------------------------------------------------------
    // get_snapshot
    // -------------------------------------------------------------------------

    /**
     * @covers ::get_snapshot
     */
    public function test_get_snapshot_returns_record_by_id(): void {
        $this->resetAfterTest();

        $id     = repository::insert_snapshot($this->make_snapshot(['code' => 'GET001']));
        $result = repository::get_snapshot($id);

        $this->assertNotNull($result);
        $this->assertEquals('GET001', $result->code);
    }

    /**
     * @covers ::get_snapshot
     */
    public function test_get_snapshot_returns_null_for_missing_id(): void {
        $this->assertNull(repository::get_snapshot(99999));
    }

    // -------------------------------------------------------------------------
    // get_snapshot_must_exist
    // -------------------------------------------------------------------------

    /**
     * @covers ::get_snapshot_must_exist
     */
    public function test_get_snapshot_must_exist_returns_record(): void {
        $this->resetAfterTest();

        $id     = repository::insert_snapshot($this->make_snapshot(['code' => 'MUSTEXIST']));
        $result = repository::get_snapshot_must_exist($id);

        $this->assertEquals('MUSTEXIST', $result->code);
    }

    /**
     * @covers ::get_snapshot_must_exist
     */
    public function test_get_snapshot_must_exist_throws_for_missing_id(): void {
        $this->expectException(\dml_missing_record_exception::class);
        repository::get_snapshot_must_exist(99999);
    }

    // -------------------------------------------------------------------------
    // get_snapshot_by_code
    // -------------------------------------------------------------------------

    /**
     * @covers ::get_snapshot_by_code
     */
    public function test_get_snapshot_by_code_returns_correct_record(): void {
        $this->resetAfterTest();

        repository::insert_snapshot($this->make_snapshot([
            'code'        => 'VERIFY01',
            'studentname' => 'John Doe',
        ]));

        $result = repository::get_snapshot_by_code('VERIFY01');

        $this->assertNotNull($result);
        $this->assertEquals('VERIFY01', $result->code);
        $this->assertEquals('John Doe', $result->studentname);
    }

    /**
     * @covers ::get_snapshot_by_code
     */
    public function test_get_snapshot_by_code_returns_null_for_invalid_code(): void {
        $this->assertNull(repository::get_snapshot_by_code('DOESNOTEXIST'));
    }

    // -------------------------------------------------------------------------
    // get_user
    // -------------------------------------------------------------------------

    /**
     * @covers ::get_user
     */
    public function test_get_user_returns_required_fields(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Alice',
            'lastname'  => 'Wonder',
            'email'     => 'alice@example.com',
        ]);

        $result = repository::get_user($user->id);

        $this->assertNotNull($result);
        $this->assertEquals('Alice',             $result->firstname);
        $this->assertEquals('Wonder',            $result->lastname);
        $this->assertEquals('alice@example.com', $result->email);
    }

    /**
     * @covers ::get_user
     */
    public function test_get_user_returns_null_for_nonexistent_user(): void {
        $this->assertNull(repository::get_user(99999));
    }

    // -------------------------------------------------------------------------
    // get_unsnapshotted_issues_recordset
    // -------------------------------------------------------------------------

    /**
     * @covers ::get_unsnapshotted_issues_recordset
     */
    public function test_get_unsnapshotted_issues_excludes_already_snapshotted(): void {
        global $DB;
        $this->resetAfterTest();

        $issueid = $DB->insert_record('customcert_issues', (object)[
            'userid'       => 1,
            'customcertid' => 1,
            'code'         => 'SNAPPED',
            'timecreated'  => time(),
        ]);

        repository::insert_snapshot($this->make_snapshot([
            'issueid' => $issueid,
            'code'    => 'SNAPPED',
        ]));

        $recordset = repository::get_unsnapshotted_issues_recordset();
        foreach ($recordset as $row) {
            $this->assertNotEquals($issueid, $row->id);
        }
        $recordset->close();
    }
}
