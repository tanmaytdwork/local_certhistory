<?php

namespace local_certhistory\services;

defined('MOODLE_INTERNAL') || die();

class pdf_service {

    public static function store_pdf(\stdClass $customcert, int $userid, int $recordid): void {
        try {
            $template = repository::get_template($customcert->templateid);
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
