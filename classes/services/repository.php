<?php

namespace local_certhistory\services;

defined('MOODLE_INTERNAL') || die();

class repository {

    public static function snapshot_exists(int $issueid): bool {
        global $DB;
        return $DB->record_exists('local_certhistory_certs', ['issueid' => $issueid]);
    }

    public static function get_issue(int $issueid): ?\stdClass {
        global $DB;
        return $DB->get_record('customcert_issues', ['id' => $issueid]) ?: null;
    }

    public static function get_customcert(int $customcertid): ?\stdClass {
        global $DB;
        return $DB->get_record('customcert', ['id' => $customcertid]) ?: null;
    }

    public static function get_course(int $courseid): ?\stdClass {
        global $DB;
        return $DB->get_record('course', ['id' => $courseid]) ?: null;
    }

    public static function get_template(int $templateid): ?\stdClass {
        global $DB;
        return $DB->get_record('customcert_templates', ['id' => $templateid]) ?: null;
    }

    public static function insert_snapshot(\stdClass $record): int {
        global $DB;
        return $DB->insert_record('local_certhistory_certs', $record);
    }

    public static function get_snapshot(int $id): ?\stdClass {
        global $DB;
        return $DB->get_record('local_certhistory_certs', ['id' => $id]) ?: null;
    }

    public static function get_snapshot_must_exist(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_certhistory_certs', ['id' => $id], '*', MUST_EXIST);
    }
}
