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
 * Certificate history page for the current user.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/certhistory:view', $context);

$pluginman = core_plugin_manager::instance();
if (!$pluginman->get_plugin_info('mod_customcert')) {
    throw new moodle_exception('nocustomcert', 'local_certhistory');
}

$pageurl = new moodle_url('/local/certhistory/index.php');
$title = get_string('mycerthistory', 'local_certhistory');

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$table = new \local_certhistory\tables\certhistory_table('local-certhistory', $USER->id, $pageurl);
$table->out(20, true);

$PAGE->requires->js_call_amd('local_certhistory/copy_verify_url', 'init');

echo $OUTPUT->footer();
