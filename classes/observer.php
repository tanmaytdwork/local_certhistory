<?php

namespace local_certhistory;

defined('MOODLE_INTERNAL') || die();


class observer {

 
    public static function certificate_issued(\mod_customcert\event\issue_created $event): void {
        global $DB;

        $issueid = $event->objectid;
        $userid = $event->relateduserid ?? $event->userid;

       
        if ($DB->record_exists('local_certhistory_certs', ['issueid' => $issueid])) {
            return;
        }

     
        $issue = $DB->get_record('customcert_issues', ['id' => $issueid]);
        if (!$issue) {
            return;
        }

        $customcert = $DB->get_record('customcert', ['id' => $issue->customcertid]);
        if (!$customcert) {
            return;
        }

     
        $course = $DB->get_record('course', ['id' => $customcert->course]);
        if (!$course) {
            return;
        }

        $record = new \stdClass();
        $record->userid = $issue->userid;
        $record->issueid = $issueid;
        $record->customcertid = $issue->customcertid;
        $record->courseid = $course->id;
        $record->coursename = $course->fullname;
        $record->certname = $customcert->name;
        $record->code = $issue->code;
        $record->timecreated = $issue->timecreated;
        $record->timesnapshotted = time();

        $recordid = $DB->insert_record('local_certhistory_certs', $record);

        self::store_pdf($customcert, $issue->userid, $recordid);
    }

   
    private static function store_pdf(\stdClass $customcert, int $userid, int $recordid): void {
        global $DB;

        try {
          
            $template = $DB->get_record('customcert_templates', ['id' => $customcert->templateid]);
            if (!$template) {
                return;
            }

            $templateobj = new \mod_customcert\template($template);
            $pdfcontent = $templateobj->generate_pdf(false, $userid, true);

            if (empty($pdfcontent)) {
                return;
            }

            $context = \context_system::instance();
            $fs = get_file_storage();

            $fileinfo = [
                'contextid' => $context->id,
                'component' => 'local_certhistory',
                'filearea'  => 'certificates',
                'itemid'    => $recordid,
                'filepath'  => '/',
                'filename'  => 'certificate.pdf',
            ];

            $existing = $fs->get_file(
                $fileinfo['contextid'],
                $fileinfo['component'],
                $fileinfo['filearea'],
                $fileinfo['itemid'],
                $fileinfo['filepath'],
                $fileinfo['filename']
            );
            if ($existing) {
                $existing->delete();
            }

            $fs->create_file_from_string($fileinfo, $pdfcontent);
        } catch (\Exception $e) {
            debugging('local_certhistory: Failed to store PDF for record ' . $recordid .
                      ': ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
