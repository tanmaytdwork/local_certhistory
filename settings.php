<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage(
        'local_certhistory_admin',
        get_string('admincerthistory', 'local_certhistory'),
        new moodle_url('/local/certhistory/admin.php'),
        'local/certhistory:viewall'
    ));
}
