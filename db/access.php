<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/certhistory:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'user' => CAP_ALLOW,
        ],
    ],
];
