<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Renderable for the admin search form.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\output;

use renderable;
use templatable;
use renderer_base;
use moodle_url;

/**
 * Provides template data for the admin certificate search form.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_search implements renderable, templatable {
    /** @var moodle_url The form action URL. */
    private moodle_url $formaction;

    /** @var string The current search query. */
    private string $search;

    /**
     * Constructor.
     *
     * @param moodle_url $formaction The form action URL.
     * @param string $search The current search query.
     */
    public function __construct(moodle_url $formaction, string $search) {
        $this->formaction = $formaction;
        $this->search = $search;
    }

    /**
     * Export data for template rendering.
     *
     * @param renderer_base $output The renderer.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'formaction' => $this->formaction->out(false),
            'search'     => $this->search,
            'has_search' => $this->search !== '',
        ];
    }
}
