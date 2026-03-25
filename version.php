<?php
defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2024120206;
$plugin->requires  = 2024042200;      
$plugin->component = 'local_certhistory';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '2.0.0';
$plugin->dependencies = [
    'mod_customcert' => ANY_VERSION,
];
