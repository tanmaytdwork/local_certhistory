<?php

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
