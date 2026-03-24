<?php
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
