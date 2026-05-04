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
 * Web service function definitions.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_certhistory_get_certificates' => [
        'classname'     => \local_certhistory\external\get_certificates::class,
        'methodname'    => 'execute',
        'description'   => 'Returns a paginated list of certificate snapshots with optional filters.',
        'type'          => 'read',
        'capabilities'  => 'local/certhistory:viewall',
        'ajax'          => false,
        'loginrequired' => true,
    ],
    'local_certhistory_get_certificate_pdf' => [
        'classname'     => \local_certhistory\external\get_certificate_pdf::class,
        'methodname'    => 'execute',
        'description'   => 'Returns the base64-encoded PDF for a single certificate snapshot by ID.',
        'type'          => 'read',
        'capabilities'  => 'local/certhistory:viewall',
        'ajax'          => false,
        'loginrequired' => true,
    ],
];
