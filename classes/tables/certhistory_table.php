<?php

namespace local_certhistory\tables;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

use table_sql;
use moodle_url;
use html_writer;


class certhistory_table extends table_sql {

    protected int $userid;
    protected int $rownumber = 0;
    protected \file_storage $fs;
    protected \context_system $syscontext;
 
    public function __construct(string $uniqueid, int $userid, moodle_url $baseurl) {
        parent::__construct($uniqueid);
        $this->userid = $userid;
        $this->rownumber = 0;
        $this->fs = get_file_storage();
        $this->syscontext = \context_system::instance();

        $columns = ['rownumber', 'coursename', 'certname', 'timecreated', 'code', 'enrollstatus', 'download'];
        $headers = [
            '#',
            get_string('coursename', 'local_certhistory'),
            get_string('certificatename', 'local_certhistory'),
            get_string('dateissued', 'local_certhistory'),
            get_string('code', 'local_certhistory'),
            get_string('enrollmentstatus', 'local_certhistory'),
            get_string('download', 'local_certhistory'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($baseurl);

        $this->no_sorting('rownumber');
        $this->no_sorting('download');

        // $this->sortable(true, 'timecreated', SORT_DESC);
        $this->collapsible(false);

        $this->set_attribute('class', 'generaltable generalbox local-certhistory-table');

        $this->setup_sql();
    }

   
    protected function setup_sql(): void {
        $fields = "ch.id,
                   ch.issueid,
                   ch.customcertid,
                   ch.courseid,
                   ch.coursename,
                   ch.certname,
                   ch.code,
                   ch.timecreated,
                   co.id AS currentcourseid,
                   co.visible AS coursevisible,
                   CASE
                       WHEN co.id IS NULL THEN 'deleted'
                       WHEN ue_active.userid IS NOT NULL THEN 'active'
                       WHEN ue_any.userid IS NOT NULL THEN 'suspended'
                       ELSE 'notenrolled'
                   END AS enrollstatus";

        $from = "{local_certhistory_certs} ch
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

        $where = "ch.userid = :userid";
        $params = ['userid' => $this->userid];

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql(
            "SELECT COUNT(1) FROM {local_certhistory_certs} ch WHERE ch.userid = :userid",
            $params
        );
    }

  
    public function col_rownumber($row): string {
        $this->rownumber++;
        return (string)$this->rownumber;
    }

 
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

    public function col_certname($row): string {
        return format_string($row->certname);
    }


    public function col_timecreated($row): string {
        return userdate($row->timecreated, get_string('strftimedatetimeshort', 'core_langconfig'));
    }


    public function col_code($row): string {
        return $row->code;
    }


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


    public function col_download($row): string {
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
            );
        }

        $url = new moodle_url('/local/certhistory/download.php', [
            'id' => $row->id,
        ]);

        return html_writer::link(
            $url,
            get_string('downloadcert', 'local_certhistory'),
            ['class' => 'btn btn-sm btn-outline-primary']
        );
    }
}
