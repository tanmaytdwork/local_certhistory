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
 * PDF storage service.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\services;

/**
 * Handles generating and storing certificate PDFs.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pdf_service {
    /**
     * Generate and store a PDF snapshot for a certificate record.
     *
     * @param \stdClass $customcert The customcert record.
     * @param int $userid The user ID.
     * @param int $recordid The snapshot record ID.
     */
    public static function store_pdf(\stdClass $customcert, int $userid, int $recordid): void {
        try {
            $template = repository::get_template($customcert->templateid);
            if (!$template) {
                return;
            }

            $templateobj = new \mod_customcert\template($template);
            $pdfcontent = $templateobj->generate_pdf(false, $userid, true);

            if (empty($pdfcontent)) {
                return;
            }

            $context = \context_system::instance();
            $fs = get_file_storage();

            $fileinfo = [
                'contextid' => $context->id,
                'component' => 'local_certhistory',
                'filearea'  => 'certificates',
                'itemid'    => $recordid,
                'filepath'  => '/',
                'filename'  => 'certificate.pdf',
            ];

            $existing = $fs->get_file(
                $fileinfo['contextid'],
                $fileinfo['component'],
                $fileinfo['filearea'],
                $fileinfo['itemid'],
                $fileinfo['filepath'],
                $fileinfo['filename']
            );
            if ($existing) {
                $existing->delete();
            }

            $fs->create_file_from_string($fileinfo, $pdfcontent);
        } catch (\Exception $e) {
            debugging('local_certhistory: Failed to store PDF for record ' . $recordid .
                      ': ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
