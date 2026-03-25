<?php

namespace local_certhistory\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use moodle_url;

class admin_search implements renderable, templatable {

    public function __construct(
        private moodle_url $formaction,
        private string $search
    ) {
    }

    public function export_for_template(renderer_base $output): array {
        return [
            'formaction' => $this->formaction->out(false),
            'search'     => $this->search,
            'has_search' => $this->search !== '',
        ];
    }
}
