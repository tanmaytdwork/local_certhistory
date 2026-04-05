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
 * Admin certificate history page.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/certhistory:viewall', $context);

$search = optional_param('search', '', PARAM_TEXT);

$pageurl = new moodle_url('/local/certhistory/admin.php', $search !== '' ? ['search' => $search] : []);
$title = get_string('admincerthistory', 'local_certhistory');

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$searchform = new \local_certhistory\output\admin_search(new moodle_url('/local/certhistory/admin.php'), $search);
echo $OUTPUT->render_from_template('local_certhistory/admin_search', $searchform->export_for_template($OUTPUT));

$table = new \local_certhistory\tables\admin_certhistory_table('local-certhistory-admin', $pageurl, $search);
$table->out(20, true);

$PAGE->requires->js_call_amd('local_certhistory/copy_verify_url', 'init');

echo $OUTPUT->footer();
