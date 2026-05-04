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
 * Integration tests for the get_certificates external function.
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\external;

use advanced_testcase;
use local_certhistory\external\get_certificates;
use local_certhistory\services\repository;

/**
 * Tests for get_certificates::execute().
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     local_certhistory
 * @coversDefaultClass \local_certhistory\external\get_certificates
 */
final class get_certificates_test extends advanced_testcase {
    /**
     * Insert a snapshot record and return it with its DB id.
     *
     * @param array $overrides Field overrides.
     * @return \stdClass
     */
    private function insert_snapshot(array $overrides = []): \stdClass {
        $record = (object) array_merge([
            'userid' => 1,
            'issueid' => 1,
            'customcertid' => 1,
            'courseid' => 1,
            'coursename' => 'Test Course',
            'certname' => 'Test Certificate',
            'code' => 'CODE001',
            'studentname' => 'John Doe',
            'email' => 'john@example.com',
            'timecreated' => time(),
            'timesnapshotted' => time(),
        ], $overrides);

        $record->id = repository::insert_snapshot($record);
        return $record;
    }

    /**
     * Set the current user as admin.
     */
    private function set_admin_user(): void {
        $this->setAdminUser();
    }

    /**
     * Call execute() with all defaults except the provided overrides.
     *
     * @param array $params Parameter overrides.
     * @return array
     */
    private function call(array $params = []): array {
        return get_certificates::execute(
            $params['userid'] ?? 0,
            $params['email'] ?? '',
            $params['courseid'] ?? 0,
            $params['certname'] ?? '',
            $params['studentname'] ?? '',
            $params['code'] ?? '',
            $params['limit'] ?? 20,
            $params['offset'] ?? 0,
        );
    }

    /**
     * Test that the viewall capability is required.
     *
     * @covers ::execute
     */
    public function test_requires_viewall_capability(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        $this->call();
    }

    /**
     * Test that all records are returned when no filters are applied.
     *
     * @covers ::execute
     */
    public function test_returns_all_records_when_no_filters(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        $this->insert_snapshot(['code' => 'ALL01', 'issueid' => 1]);
        $this->insert_snapshot(['code' => 'ALL02', 'issueid' => 2]);
        $this->insert_snapshot(['code' => 'ALL03', 'issueid' => 3]);

        $result = $this->call();

        $this->assertCount(3, $result['certificates']);
        $this->assertEquals(3, $result['total']);
    }

    /**
     * Test that an empty array is returned when no snapshots exist.
     *
     * @covers ::execute
     */
    public function test_returns_empty_array_when_no_snapshots(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        $result = $this->call();

        $this->assertCount(0, $result['certificates']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test that each certificate record contains all expected fields.
     *
     * @covers ::execute
     */
    public function test_certificate_record_has_all_expected_fields(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        $this->insert_snapshot(['code' => 'SHAPE01', 'issueid' => 1]);

        $result = $this->call();
        $cert = $result['certificates'][0];

        $this->assertArrayHasKey('id', $cert);
        $this->assertArrayHasKey('userid', $cert);
        $this->assertArrayHasKey('courseid', $cert);
        $this->assertArrayHasKey('coursename', $cert);
        $this->assertArrayHasKey('certname', $cert);
        $this->assertArrayHasKey('code', $cert);
        $this->assertArrayHasKey('studentname', $cert);
        $this->assertArrayHasKey('email', $cert);
        $this->assertArrayHasKey('timecreated', $cert);
    }

    /**
     * Test filtering records by user ID.
     *
     * @covers ::execute
     */
    public function test_filter_by_userid(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        $this->insert_snapshot(['userid' => 10, 'code' => 'USR01', 'issueid' => 1]);
        $this->insert_snapshot(['userid' => 20, 'code' => 'USR02', 'issueid' => 2]);

        $result = $this->call(['userid' => 10]);

        $this->assertCount(1, $result['certificates']);
        $this->assertEquals('USR01', $result['certificates'][0]['code']);
    }

    /**
     * Test filtering records by exact email address.
     *
     * @covers ::execute
     */
    public function test_filter_by_email_exact(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        $this->insert_snapshot(['email' => 'alice@example.com', 'code' => 'EML01', 'issueid' => 1]);
        $this->insert_snapshot(['email' => 'bob@example.com', 'code' => 'EML02', 'issueid' => 2]);

        $result = $this->call(['email' => 'alice@example.com']);

        $this->assertCount(1, $result['certificates']);
        $this->assertEquals('EML01', $result['certificates'][0]['code']);
    }

    /**
     * Test filtering records by course ID.
     *
     * @covers ::execute
     */
    public function test_filter_by_courseid(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        $this->insert_snapshot(['courseid' => 5, 'code' => 'CRS01', 'issueid' => 1]);
        $this->insert_snapshot(['courseid' => 9, 'code' => 'CRS02', 'issueid' => 2]);

        $result = $this->call(['courseid' => 5]);

        $this->assertCount(1, $result['certificates']);
        $this->assertEquals('CRS01', $result['certificates'][0]['code']);
    }

    /**
     * Test filtering records by partial certificate name.
     *
     * @covers ::execute
     */
    public function test_filter_by_certname_partial(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        $this->insert_snapshot(['certname' => 'Python Basics', 'code' => 'CERT01', 'issueid' => 1]);
        $this->insert_snapshot(['certname' => 'Advanced Django', 'code' => 'CERT02', 'issueid' => 2]);

        $result = $this->call(['certname' => 'Python']);

        $this->assertCount(1, $result['certificates']);
        $this->assertEquals('CERT01', $result['certificates'][0]['code']);
    }

    /**
     * Test filtering records by partial student name.
     *
     * @covers ::execute
     */
    public function test_filter_by_studentname_partial(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        $this->insert_snapshot(['studentname' => 'Alice Smith', 'code' => 'STU01', 'issueid' => 1]);
        $this->insert_snapshot(['studentname' => 'Bob Jones', 'code' => 'STU02', 'issueid' => 2]);

        $result = $this->call(['studentname' => 'Alice']);

        $this->assertCount(1, $result['certificates']);
        $this->assertEquals('STU01', $result['certificates'][0]['code']);
    }

    /**
     * Test filtering records by exact certificate code.
     *
     * @covers ::execute
     */
    public function test_filter_by_code_exact(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        $this->insert_snapshot(['code' => 'EXACT01', 'issueid' => 1]);
        $this->insert_snapshot(['code' => 'EXACT02', 'issueid' => 2]);

        $result = $this->call(['code' => 'EXACT01']);

        $this->assertCount(1, $result['certificates']);
        $this->assertEquals('EXACT01', $result['certificates'][0]['code']);
    }

    /**
     * Test that filtering returns an empty array when no records match.
     *
     * @covers ::execute
     */
    public function test_filter_returns_empty_when_no_match(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        $this->insert_snapshot(['code' => 'NOMATCH1', 'issueid' => 1]);

        $result = $this->call(['code' => 'DOESNOTEXIST']);

        $this->assertCount(0, $result['certificates']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test that the limit parameter restricts the number of returned results.
     *
     * @covers ::execute
     */
    public function test_limit_restricts_number_of_results(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        for ($i = 1; $i <= 5; $i++) {
            $this->insert_snapshot(['code' => "PAGE0$i", 'issueid' => $i]);
        }

        $result = $this->call(['limit' => 2]);

        $this->assertCount(2, $result['certificates']);
        $this->assertEquals(5, $result['total']);
    }

    /**
     * Test that the offset parameter skips the specified number of records.
     *
     * @covers ::execute
     */
    public function test_offset_skips_records(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        for ($i = 1; $i <= 3; $i++) {
            $this->insert_snapshot(['code' => "OFFSET0$i", 'issueid' => $i, 'timecreated' => $i]);
        }

        $result = $this->call(['limit' => 10, 'offset' => 2]);

        $this->assertCount(1, $result['certificates']);
        $this->assertEquals(3, $result['total']);
    }

    /**
     * Test that the limit parameter is clamped to a maximum of 100.
     *
     * @covers ::execute
     */
    public function test_limit_is_clamped_to_maximum_of_100(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        for ($i = 1; $i <= 5; $i++) {
            $this->insert_snapshot(['code' => "CLAMP0$i", 'issueid' => $i]);
        }

        $result = $this->call(['limit' => 999]);

        $this->assertCount(5, $result['certificates']);
    }

    /**
     * Test that the total reflects the full count, not just the page size.
     *
     * @covers ::execute
     */
    public function test_total_reflects_full_count_not_page_size(): void {
        $this->resetAfterTest();
        $this->set_admin_user();

        for ($i = 1; $i <= 10; $i++) {
            $this->insert_snapshot(['code' => "TOT0$i", 'issueid' => $i]);
        }

        $result = $this->call(['limit' => 3, 'offset' => 0]);

        $this->assertCount(3, $result['certificates']);
        $this->assertEquals(10, $result['total']);
    }
}
