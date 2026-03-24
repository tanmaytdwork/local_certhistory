<?php

namespace local_certhistory\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use moodle_url;
use local_certhistory\services\repository;

class verify_page implements renderable, templatable {

    public function __construct(private string $code) {
    }

    public function export_for_template(renderer_base $output): array {
        $data = [
            'formaction' => (new moodle_url('/local/certhistory/verify.php'))->out(false),
            'code'       => $this->code,
        ];

        if ($this->code === '') {
            return $data;
        }

        $record = repository::get_snapshot_by_code($this->code);

        if (!$record) {
            $data['result_notfound'] = true;
            return $data;
        }

        $user = repository::get_user($record->userid);

        $data['result_valid'] = true;
        $data['details'] = [
            [
                'label' => get_string('certificateholder', 'local_certhistory'),
                'value' => fullname($user),
            ],
            [
                'label' => get_string('certificatename', 'local_certhistory'),
                'value' => format_string($record->certname),
            ],
            [
                'label' => get_string('coursename', 'local_certhistory'),
                'value' => format_string($record->coursename),
            ],
            [
                'label' => get_string('dateissued', 'local_certhistory'),
                'value' => userdate($record->timecreated, get_string('strftimedatetimeshort', 'core_langconfig')),
            ],
            [
                'label' => get_string('code', 'local_certhistory'),
                'value' => $record->code,
            ],
        ];

        return $data;
    }
}
