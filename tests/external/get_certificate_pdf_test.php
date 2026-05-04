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
 * Integration tests for the get_certificate_pdf external function.
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\tests\external;

use advanced_testcase;
use local_certhistory\external\get_certificate_pdf;
use local_certhistory\services\repository;

/**
 * Tests for get_certificate_pdf::execute().
 *
 * @package   local_certhistory
 * @category  test
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     local_certhistory
 * @coversDefaultClass \local_certhistory\external\get_certificate_pdf
 */
class get_certificate_pdf_test extends advanced_testcase {

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Insert a snapshot and return the record with its DB id.
     *
     * @param array $overrides
     * @return \stdClass
     */
    private function insert_snapshot(array $overrides = []): \stdClass {
        $record = (object) array_merge([
            'userid'          => 1,
            'issueid'         => 1,
            'customcertid'    => 1,
            'courseid'        => 1,
            'coursename'      => 'Test Course',
            'certname'        => 'Test Certificate',
            'code'            => 'PDF001',
            'studentname'     => 'John Doe',
            'email'           => 'john@example.com',
            'timecreated'     => time(),
            'timesnapshotted' => time(),
        ], $overrides);

        $record->id = repository::insert_snapshot($record);
        return $record;
    }

    /**
     * Store a fake PDF file in Moodle's file storage for the given snapshot ID.
     *
     * @param int    $snapshotid The snapshot record ID (itemid).
     * @param string $content    Raw file content.
     */
    private function store_fake_pdf(int $snapshotid, string $content = 'fake-pdf-content'): void {
        $fs      = get_file_storage();
        $context = \context_system::instance();

        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'local_certhistory',
            'filearea'  => 'certificates',
            'itemid'    => $snapshotid,
            'filepath'  => '/',
            'filename'  => 'certificate.pdf',
        ], $content);
    }

    // -------------------------------------------------------------------------
    // Capability check
    // -------------------------------------------------------------------------

    /**
     * @covers ::execute
     */
    public function test_requires_viewall_capability(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        get_certificate_pdf::execute(1);
    }

    // -------------------------------------------------------------------------
    // Snapshot not found
    // -------------------------------------------------------------------------

    /**
     * @covers ::execute
     */
    public function test_returns_not_found_for_missing_snapshot(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $result = get_certificate_pdf::execute(99999);

        $this->assertFalse($result['found']);
        $this->assertEquals('', $result['pdf']);
    }

    // -------------------------------------------------------------------------
    // Snapshot exists but no PDF stored
    // -------------------------------------------------------------------------

    /**
     * @covers ::execute
     */
    public function test_returns_found_true_with_empty_pdf_when_no_file_stored(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $snapshot = $this->insert_snapshot(['code' => 'NOPDF01', 'issueid' => 1]);

        $result = get_certificate_pdf::execute($snapshot->id);

        $this->assertTrue($result['found']);
        $this->assertEquals('', $result['pdf']);
    }

    // -------------------------------------------------------------------------
    // Snapshot exists with PDF
    // -------------------------------------------------------------------------

    /**
     * @covers ::execute
     */
    public function test_returns_found_true_with_base64_pdf(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $snapshot = $this->insert_snapshot(['code' => 'HASPDF01', 'issueid' => 1]);
        $this->store_fake_pdf($snapshot->id, 'fake-pdf-content');

        $result = get_certificate_pdf::execute($snapshot->id);

        $this->assertTrue($result['found']);
        $this->assertNotEmpty($result['pdf']);
    }

    /**
     * @covers ::execute
     */
    public function test_pdf_is_valid_base64(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $snapshot = $this->insert_snapshot(['code' => 'HASPDF02', 'issueid' => 1]);
        $this->store_fake_pdf($snapshot->id, 'fake-pdf-content');

        $result  = get_certificate_pdf::execute($snapshot->id);
        $decoded = base64_decode($result['pdf'], true);

        $this->assertNotFalse($decoded, 'PDF field should be valid base64');
    }

    /**
     * @covers ::execute
     */
    public function test_decoded_pdf_matches_original_content(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $originalcontent = '%PDF-1.4 fake content for testing';
        $snapshot        = $this->insert_snapshot(['code' => 'HASPDF03', 'issueid' => 1]);
        $this->store_fake_pdf($snapshot->id, $originalcontent);

        $result  = get_certificate_pdf::execute($snapshot->id);
        $decoded = base64_decode($result['pdf']);

        $this->assertEquals($originalcontent, $decoded);
    }

    // -------------------------------------------------------------------------
    // Response shape
    // -------------------------------------------------------------------------

    /**
     * @covers ::execute
     */
    public function test_response_always_has_found_and_pdf_keys(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $result = get_certificate_pdf::execute(99999);

        $this->assertArrayHasKey('found', $result);
        $this->assertArrayHasKey('pdf',   $result);
    }
}
