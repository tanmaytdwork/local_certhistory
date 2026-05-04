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
 * External function: get_certificate_pdf.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_certhistory\services\repository;

/**
 * Returns the base64-encoded PDF for a single certificate snapshot.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_certificate_pdf extends external_api {

    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Certificate snapshot record ID'),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $id The certificate snapshot record ID.
     * @return array
     */
    public static function execute(int $id): array {
        $params = self::validate_parameters(self::execute_parameters(), ['id' => $id]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/certhistory:viewall', $context);

        $record = repository::get_snapshot((int) $params['id']);

        if (!$record) {
            return [
                'found' => false,
                'pdf'   => '',
            ];
        }

        $fs   = get_file_storage();
        $file = $fs->get_file(
            $context->id,
            'local_certhistory',
            'certificates',
            $record->id,
            '/',
            'certificate.pdf'
        );

        if (!$file) {
            return [
                'found' => true,
                'pdf'   => '',
            ];
        }

        return [
            'found' => true,
            'pdf'   => base64_encode($file->get_content()),
        ];
    }

    /**
     * Define the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'found' => new external_value(PARAM_BOOL, 'Whether a snapshot with this ID exists'),
            'pdf'   => new external_value(PARAM_RAW,  'Base64-encoded PDF content, empty string if unavailable'),
        ]);
    }
}
