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
 * Certificate download handler.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$id = required_param('id', PARAM_INT);

$context = context_system::instance();
require_capability('local/certhistory:view', $context);

$record = \local_certhistory\services\repository::get_snapshot_must_exist($id);

$isowner = $record->userid == $USER->id;
$isadmin = has_capability('local/certhistory:viewall', $context);

if (!$isowner && !$isadmin) {
    throw new moodle_exception('nopermission', 'error');
}

$fs = get_file_storage();
$file = $fs->get_file(
    $context->id,
    'local_certhistory',
    'certificates',
    $record->id,
    '/',
    'certificate.pdf'
);

if (!$file) {
    throw new moodle_exception('filenotfound', 'error');
}

$filename = clean_filename($record->certname . ' - ' . $record->coursename . '.pdf');

send_stored_file($file, 0, 0, true, ['filename' => $filename]);
