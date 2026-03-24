<?php

require_once(__DIR__ . '/../../config.php');

$code = optional_param('code', '', PARAM_ALPHANUM);

$pageurl = new moodle_url('/local/certhistory/verify.php', $code ? ['code' => $code] : []);
$title = get_string('verifycertificate', 'local_certhistory');

$PAGE->set_url($pageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$page = new \local_certhistory\output\verify_page($code);
echo $OUTPUT->render_from_template('local_certhistory/verify_page', $page->export_for_template($OUTPUT));

echo $OUTPUT->footer();
