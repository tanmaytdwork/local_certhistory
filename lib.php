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
 * Plugin library functions.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serve plugin files.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The context object.
 * @param string $filearea The file area.
 * @param array $args The file arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options Additional options.
 * @return bool False if file not found.
 */
function local_certhistory_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $USER;

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

    $record = \local_certhistory\services\repository::get_snapshot($itemid);
    $isowner = $record && $record->userid == $USER->id;
    $isadmin = has_capability('local/certhistory:viewall', $context);

    if (!$record || (!$isowner && !$isadmin)) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_certhistory', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Extend the global navigation.
 *
 * @param global_navigation $nav The global navigation object.
 */
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

    if (has_capability('local/certhistory:viewall', context_system::instance())) {
        $nav->add(
            get_string('admincerthistory', 'local_certhistory'),
            new moodle_url('/local/certhistory/admin.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_certhistory_admin',
            new pix_icon('i/report', '')
        );
    }
}

/**
 * Add links to user profile navigation.
 *
 * @param \core_user\output\myprofile\tree $tree The profile tree.
 * @param stdClass $user The user object.
 * @param bool $iscurrentuser Whether viewing own profile.
 * @param stdClass $course The course object.
 */
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
