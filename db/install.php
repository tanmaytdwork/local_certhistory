<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_certhistory_install() {
    $task = new \local_certhistory\task\import_existing_certificates();
    \core\task\manager::queue_adhoc_task($task);

    return true;
}
