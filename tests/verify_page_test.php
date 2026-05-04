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
 * Unit tests for the verify_page output class.
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory;

use advanced_testcase;
use local_certhistory\output\verify_page;
use local_certhistory\services\repository;

/**
 * Tests for verify_page::export_for_template().
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     local_certhistory
 * @coversDefaultClass \local_certhistory\output\verify_page
 */
final class verify_page_test extends advanced_testcase {
    /**
     * Build a minimal valid snapshot record and insert it.
     *
     * @param array $overrides Field overrides.
     * @return \stdClass The inserted snapshot (with id).
     */
    private function insert_snapshot(array $overrides = []): \stdClass {
        $record = (object) array_merge([
            'userid' => 1,
            'issueid' => 1,
            'customcertid' => 1,
            'courseid' => 1,
            'coursename' => 'Test Course',
            'certname' => 'Test Certificate',
            'code' => 'TEST01',
            'studentname' => 'Alice Smith',
            'email' => 'alice@example.com',
            'timecreated' => mktime(0, 0, 0, 1, 15, 2025),
            'timesnapshotted' => time(),
        ], $overrides);

        $record->id = repository::insert_snapshot($record);
        return $record;
    }

    /**
     * Get a core renderer for export_for_template calls.
     *
     * @return \renderer_base
     */
    private function get_renderer(): \renderer_base {
        global $PAGE;
        return $PAGE->get_renderer('core');
    }

    /**
     * Find a detail entry value by its lang string key.
     *
     * @param array $details The details array from export_for_template.
     * @param string $langkey The lang string key.
     * @return string|null
     */
    private function find_detail(array $details, string $langkey): ?string {
        $label = get_string($langkey, 'local_certhistory');
        foreach ($details as $detail) {
            if ($detail['label'] === $label) {
                return $detail['value'];
            }
        }
        return null;
    }

    /**
     * Test that an empty code returns no result keys.
     *
     * @covers ::export_for_template
     */
    public function test_empty_code_returns_no_result_keys(): void {
        $data = (new verify_page(''))->export_for_template($this->get_renderer());

        $this->assertArrayNotHasKey('result_valid', $data);
        $this->assertArrayNotHasKey('result_notfound', $data);
    }

    /**
     * Test that an empty code always includes the formaction key.
     *
     * @covers ::export_for_template
     */
    public function test_empty_code_always_includes_formaction(): void {
        $data = (new verify_page(''))->export_for_template($this->get_renderer());

        $this->assertArrayHasKey('formaction', $data);
        $this->assertNotEmpty($data['formaction']);
    }

    /**
     * Test that an invalid code sets result_notfound.
     *
     * @covers ::export_for_template
     */
    public function test_invalid_code_sets_result_notfound(): void {
        $data = (new verify_page('INVALIDCODE'))->export_for_template($this->get_renderer());

        $this->assertTrue($data['result_notfound']);
        $this->assertArrayNotHasKey('result_valid', $data);
    }

    /**
     * Test that a valid code sets result_valid.
     *
     * @covers ::export_for_template
     */
    public function test_valid_code_sets_result_valid(): void {
        $this->resetAfterTest();

        $this->insert_snapshot(['code' => 'VALID01']);

        $data = (new verify_page('VALID01'))->export_for_template($this->get_renderer());

        $this->assertTrue($data['result_valid']);
        $this->assertArrayNotHasKey('result_notfound', $data);
    }

    /**
     * Test that a valid code shows the student name as certificate holder.
     *
     * @covers ::export_for_template
     */
    public function test_valid_code_shows_studentname_as_certificate_holder(): void {
        $this->resetAfterTest();

        $this->insert_snapshot(['code' => 'VALID02', 'studentname' => 'Alice Smith']);

        $data = (new verify_page('VALID02'))->export_for_template($this->get_renderer());
        $holder = $this->find_detail($data['details'], 'certificateholder');

        $this->assertEquals('Alice Smith', $holder);
    }

    /**
     * Test that a valid code shows the certificate name in the details.
     *
     * @covers ::export_for_template
     */
    public function test_valid_code_shows_certname(): void {
        $this->resetAfterTest();

        $this->insert_snapshot(['code' => 'VALID03', 'certname' => 'My Certificate']);

        $data = (new verify_page('VALID03'))->export_for_template($this->get_renderer());
        $certname = $this->find_detail($data['details'], 'certificatename');

        $this->assertEquals('My Certificate', $certname);
    }

    /**
     * Test that a valid code shows the course name in the details.
     *
     * @covers ::export_for_template
     */
    public function test_valid_code_shows_coursename(): void {
        $this->resetAfterTest();

        $this->insert_snapshot(['code' => 'VALID04', 'coursename' => 'My Course']);

        $data = (new verify_page('VALID04'))->export_for_template($this->get_renderer());
        $coursename = $this->find_detail($data['details'], 'coursename');

        $this->assertEquals('My Course', $coursename);
    }

    /**
     * Test that a valid code shows the code value in the details.
     *
     * @covers ::export_for_template
     */
    public function test_valid_code_shows_code_in_details(): void {
        $this->resetAfterTest();

        $this->insert_snapshot(['code' => 'VALID05']);

        $data = (new verify_page('VALID05'))->export_for_template($this->get_renderer());
        $code = $this->find_detail($data['details'], 'code');

        $this->assertEquals('VALID05', $code);
    }

    /**
     * Test that an empty studentname renders without error.
     *
     * @covers ::export_for_template
     */
    public function test_empty_studentname_renders_without_error(): void {
        $this->resetAfterTest();

        $this->insert_snapshot(['code' => 'DELUSER01', 'studentname' => '', 'email' => '']);

        $data = (new verify_page('DELUSER01'))->export_for_template($this->get_renderer());
        $holder = $this->find_detail($data['details'], 'certificateholder');

        $this->assertTrue($data['result_valid']);
        $this->assertEquals('', $holder);
    }
}
