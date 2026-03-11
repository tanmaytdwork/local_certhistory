<?php

defined('MOODLE_INTERNAL') || die();

function local_certhistory_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    require_login();

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea !== 'certificates') {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $record = $DB->get_record('local_certhistory_certs', ['id' => $itemid]);
    if (!$record || $record->userid != $USER->id) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_certhistory', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function local_certhistory_extend_navigation(global_navigation $nav) {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    if (!has_capability('local/certhistory:view', context_system::instance())) {
        return;
    }

    $nav->add(
        get_string('mycerthistory', 'local_certhistory'),
        new moodle_url('/local/certhistory/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_certhistory',
        new pix_icon('i/certificate', '')
    );
}


function local_certhistory_myprofile_navigation(
    \core_user\output\myprofile\tree $tree,
    $user,
    $iscurrentuser,
    $course
) {
    if (isguestuser($user)) {
        return;
    }

    if (!$iscurrentuser) {
        return;
    }

    if (!has_capability('local/certhistory:view', context_system::instance())) {
        return;
    }

    $url = new moodle_url('/local/certhistory/index.php');
    $node = new \core_user\output\myprofile\node(
        'miscellaneous',
        'certhistory',
        get_string('mycerthistory', 'local_certhistory'),
        null,
        $url
    );
    $tree->add_node($node);
}
