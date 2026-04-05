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
 * Admin certificate history table.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_certhistory\tables;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

use table_sql;
use moodle_url;
use html_writer;

/**
 * Displays a searchable table of all certificate snapshots for admins.
 *
 * @package   local_certhistory
 * @copyright 2026 Tanmay Deshmukh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_certhistory_table extends table_sql {
    /** @var int Running row counter for the current page. */
    protected int $rownumber = 0;

    /** @var \file_storage File storage instance. */
    protected \file_storage $fs;

    /** @var \context_system System context instance. */
    protected \context_system $syscontext;

    /** @var string Current search query. */
    protected string $search;

    /**
     * Constructor.
     *
     * @param string $uniqueid Unique table identifier.
     * @param moodle_url $baseurl The base URL for sorting/paging links.
     * @param string $search Optional search query.
     */
    public function __construct(string $uniqueid, moodle_url $baseurl, string $search = '') {
        parent::__construct($uniqueid);
        $this->rownumber = 0;
        $this->fs = get_file_storage();
        $this->syscontext = \context_system::instance();
        $this->search = $search;

        $columns = ['rownumber', 'username', 'coursename', 'certname', 'timecreated', 'code', 'enrollstatus', 'download'];
        $headers = [
            '#',
            get_string('user', 'local_certhistory'),
            get_string('coursename', 'local_certhistory'),
            get_string('certificatename', 'local_certhistory'),
            get_string('dateissued', 'local_certhistory'),
            get_string('code', 'local_certhistory'),
            get_string('enrollmentstatus', 'local_certhistory'),
            '',
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($baseurl);

        $this->no_sorting('rownumber');
        $this->no_sorting('download');

        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->collapsible(false);

        $this->set_attribute('class', 'generaltable generalbox local-certhistory-admin-table');

        $this->setup_sql();
    }

    /**
     * Configure the SQL query for this table.
     */
    protected function setup_sql(): void {
        $fields = "ch.id,
                   ch.issueid,
                   ch.userid,
                   ch.customcertid,
                   ch.courseid,
                   ch.coursename,
                   ch.certname,
                   ch.code,
                   ch.timecreated,
                   u.firstname,
                   u.lastname,
                   co.id AS currentcourseid,
                   co.visible AS coursevisible,
                   CASE
                       WHEN co.id IS NULL THEN 'deleted'
                       WHEN ue_active.userid IS NOT NULL THEN 'active'
                       WHEN ue_any.userid IS NOT NULL THEN 'suspended'
                       ELSE 'notenrolled'
                   END AS enrollstatus";

        $from = "{local_certhistory_certs} ch
                 JOIN {user} u ON u.id = ch.userid
                 LEFT JOIN {course} co ON co.id = ch.courseid
                 LEFT JOIN (
                     SELECT DISTINCT ue.userid, e.courseid
                     FROM {user_enrolments} ue
                     JOIN {enrol} e ON e.id = ue.enrolid
                     WHERE ue.status = 0 AND e.status = 0
                 ) ue_active ON ue_active.userid = ch.userid AND ue_active.courseid = ch.courseid
                 LEFT JOIN (
                     SELECT DISTINCT ue.userid, e.courseid
                     FROM {user_enrolments} ue
                     JOIN {enrol} e ON e.id = ue.enrolid
                 ) ue_any ON ue_any.userid = ch.userid AND ue_any.courseid = ch.courseid";

        global $DB;

        $where = '1=1';
        $params = [];

        if ($this->search !== '') {
            $val = '%' . $DB->sql_like_escape($this->search) . '%';
            $where = '(' .
                $DB->sql_like('u.firstname', ':searchfirst', false) . ' OR ' .
                $DB->sql_like('u.lastname', ':searchlast', false) . ' OR ' .
                $DB->sql_like($DB->sql_concat('u.firstname', "' '", 'u.lastname'), ':searchfullname', false) . ' OR ' .
                $DB->sql_like('ch.coursename', ':searchcourse', false) . ' OR ' .
                $DB->sql_like('ch.certname', ':searchcert', false) . ' OR ' .
                $DB->sql_like('ch.code', ':searchcode', false) .
            ')';
            $params = [
                'searchfirst'    => $val,
                'searchlast'     => $val,
                'searchfullname' => $val,
                'searchcourse'   => $val,
                'searchcert'     => $val,
                'searchcode'     => $val,
            ];
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);
    }

    /**
     * Render the row number column.
     *
     * @param \stdClass $row The current row.
     * @return string
     */
    public function col_rownumber($row): string {
        $this->rownumber++;
        $offset = $this->currpage * $this->pagesize;
        return (string)($offset + $this->rownumber);
    }

    /**
     * Render the username column as a link to the user's profile.
     *
     * @param \stdClass $row The current row.
     * @return string
     */
    public function col_username($row): string {
        $fullname = fullname($row);
        $url = new moodle_url('/user/view.php', ['id' => $row->userid]);
        return html_writer::link($url, $fullname);
    }

    /**
     * Render the course name column with status badges.
     *
     * @param \stdClass $row The current row.
     * @return string
     */
    public function col_coursename($row): string {
        $name = format_string($row->coursename);

        if (empty($row->currentcourseid)) {
            return $name . ' ' . html_writer::span(
                get_string('coursedeleted', 'local_certhistory'),
                'badge badge-danger'
            );
        }

        if (empty($row->coursevisible)) {
            return html_writer::span($name, 'text-muted') . ' ' .
                   html_writer::span(
                       get_string('hiddencourse', 'local_certhistory'),
                       'badge badge-secondary'
                   );
        }

        $url = new moodle_url('/course/view.php', ['id' => $row->currentcourseid]);
        return html_writer::link($url, $name);
    }

    /**
     * Render the certificate name column.
     *
     * @param \stdClass $row The current row.
     * @return string
     */
    public function col_certname($row): string {
        return format_string($row->certname);
    }

    /**
     * Render the date issued column.
     *
     * @param \stdClass $row The current row.
     * @return string
     */
    public function col_timecreated($row): string {
        return userdate($row->timecreated, get_string('strftimedatetimeshort', 'core_langconfig'));
    }

    /**
     * Render the certificate code column.
     *
     * @param \stdClass $row The current row.
     * @return string
     */
    public function col_code($row): string {
        return $row->code;
    }

    /**
     * Render the enrolment status column.
     *
     * @param \stdClass $row The current row.
     * @return string
     */
    public function col_enrollstatus($row): string {
        switch ($row->enrollstatus) {
            case 'active':
                return html_writer::span(
                    get_string('statusactive', 'local_certhistory'),
                    'badge badge-success'
                );
            case 'suspended':
                return html_writer::span(
                    get_string('statussuspended', 'local_certhistory'),
                    'badge badge-warning'
                );
            case 'notenrolled':
                return html_writer::span(
                    get_string('statusnotenrolled', 'local_certhistory'),
                    'badge badge-secondary'
                );
            case 'deleted':
                return html_writer::span(
                    get_string('statusdeleted', 'local_certhistory'),
                    'badge badge-danger'
                );
            default:
                return $row->enrollstatus;
        }
    }

    /**
     * Render the download/share column.
     *
     * @param \stdClass $row The current row.
     * @return string
     */
    public function col_download($row): string {
        global $OUTPUT;

        $verifyurl = new moodle_url('/local/certhistory/verify.php', ['code' => $row->code]);
        $shareicon = html_writer::tag('i', '', ['class' => 'fa fa-share-alt fa-fw', 'aria-hidden' => 'true']);
        $sharebtn = html_writer::tag(
            'button',
            $shareicon,
            [
                'class'       => 'btn btn-link p-0 border-0 align-baseline',
                'data-action' => 'copy-verify-url',
                'data-url'    => $verifyurl->out(false),
                'title'       => get_string('copyverifylink', 'local_certhistory'),
                'aria-label'  => get_string('copyverifylink', 'local_certhistory'),
            ]
        );

        $file = $this->fs->get_file(
            $this->syscontext->id,
            'local_certhistory',
            'certificates',
            $row->id,
            '/',
            'certificate.pdf'
        );

        if (!$file) {
            return html_writer::span(
                get_string('unavailable', 'local_certhistory'),
                'text-muted'
            ) . ' ' . $sharebtn;
        }

        $url = new moodle_url('/local/certhistory/download.php', ['id' => $row->id]);
        $downloadlink = html_writer::link(
            $url,
            $OUTPUT->pix_icon('t/download', get_string('downloadcert', 'local_certhistory')),
            ['title' => get_string('downloadcert', 'local_certhistory')]
        );

        return $downloadlink . ' ' . $sharebtn;
    }
}
