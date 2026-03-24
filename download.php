<?php

require_once(__DIR__ . '/../../config.php');

require_login();

$id = required_param('id', PARAM_INT);

$context = context_system::instance();
require_capability('local/certhistory:view', $context);

$record = \local_certhistory\services\repository::get_snapshot_must_exist($id);

if ($record->userid != $USER->id) {
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
