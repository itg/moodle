<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

$lockdownbrowser_stepslib_file =
  "$CFG->dirroot/blocks/lockdownbrowser/backup/moodle2/restore_lockdownbrowser_stepslib.php";
require_once($lockdownbrowser_stepslib_file);

class restore_lockdownbrowser_block_task extends restore_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {

        $this->add_step(new restore_lockdownbrowser_block_structure_step(
            "lockdownbrowser_structure", "lockdownbrowser.xml"));
    }

    public function get_fileareas() {

        return array();
    }

    public function get_configdata_encoded_attributes() {

        return array();
    }

    public function after_restore() {

        global $DB;

        $course_id = $this->get_courseid();

        $records = $DB->get_records("block_lockdownbrowser_sett", array("course" => $course_id));
        if ($records === false) {
            return;
        }

        $missing_ids = 0;
        $missing_records = 0;

        foreach ($records as $settings) {

            $old_quizid = $settings->quizid;
            if (empty($old_quizid)) {
                continue;
            }
            $quizmap = restore_dbops::get_backup_ids_record(
              $this->get_restoreid(), "quiz", $old_quizid);
            if (empty($quizmap)) {
                $missing_ids++;
                $DB->delete_records("block_lockdownbrowser_sett", array("quizid" => $old_quizid));
                continue;
            }
            $quiz = $DB->get_record("quiz", array("id" => $quizmap->newitemid));
            if ($quiz === false) {
                $missing_records++;
                $DB->delete_records("block_lockdownbrowser_sett", array("quizid" => $old_quizid));
                continue;
            }
            $settings->quizid = $quizmap->newitemid;
            $DB->update_record("block_lockdownbrowser_sett", $settings);

            $quiz->popup = 0;
            $quiz->browsersecurity = get_string("browsersecuritychoicekey", "block_lockdownbrowser");
            $DB->update_record("quiz", $quiz);
        }

        if ($missing_ids > 0 || $missing_records > 0) {
            $this->get_logger()->process(
                "Failed to restore dependency in block 'lockdownbrowser'. " .
                "Backup and restore will not work correctly unless you " .
                "include the dependent 'quiz' modules.",
                backup::LOG_ERROR
            );
        }
    }

    static public function define_decode_contents() {

        $contents = array();

        $contents[] = new restore_decode_content(
            "block_lockdownbrowser", array("intro"));

        return $contents;
    }

    static public function define_decode_rules() {

        $rules = array();

        $rules[] = new restore_decode_rule("LOCKDOWNBROWSERDASHBOARD",
            "/blocks/lockdownbrowser/dashboard.php?course=$1", "course");

        return $rules;
    }
}

