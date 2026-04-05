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
 * Renderable for the certificate verification page.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\output;

use renderable;
use templatable;
use renderer_base;
use moodle_url;
use local_certhistory\services\repository;

/**
 * Provides template data for the certificate verification page.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verify_page implements renderable, templatable {
    /** @var string The certificate code to verify. */
    private string $code;

    /**
     * Constructor.
     *
     * @param string $code The certificate code to verify.
     */
    public function __construct(string $code) {
        $this->code = $code;
    }

    /**
     * Export data for template rendering.
     *
     * @param renderer_base $output The renderer.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $data = [
            'formaction' => (new moodle_url('/local/certhistory/verify.php'))->out(false),
            'code'       => $this->code,
        ];

        if ($this->code === '') {
            return $data;
        }

        $record = repository::get_snapshot_by_code($this->code);

        if (!$record) {
            $data['result_notfound'] = true;
            return $data;
        }

        $user = repository::get_user($record->userid);

        $data['result_valid'] = true;
        $data['details'] = [
            [
                'label' => get_string('certificateholder', 'local_certhistory'),
                'value' => fullname($user),
            ],
            [
                'label' => get_string('certificatename', 'local_certhistory'),
                'value' => format_string($record->certname),
            ],
            [
                'label' => get_string('coursename', 'local_certhistory'),
                'value' => format_string($record->coursename),
            ],
            [
                'label' => get_string('dateissued', 'local_certhistory'),
                'value' => userdate($record->timecreated, get_string('strftimedatetimeshort', 'core_langconfig')),
            ],
            [
                'label' => get_string('code', 'local_certhistory'),
                'value' => $record->code,
            ],
        ];

        return $data;
    }
}
