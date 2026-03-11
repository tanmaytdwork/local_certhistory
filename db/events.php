<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_customcert\event\issue_created',
        'callback'  => '\local_certhistory\observer::certificate_issued',
    ],
];
