<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

class backup_lockdownbrowser_block_execution_step extends backup_execution_step {

    protected function define_execution() {

        global $DB;

        // remove any orphaned records from our settings table;
        // orphans occur whenever a quiz is deleted without first removing the LDB
        // requirement in our dashboard
        $records = $DB->get_records('block_lockdownbrowser_sett');
        if (count($records) > 0) {
            foreach ($records as $settings) {
                if ($DB->record_exists('quiz', array('id' => $settings->quizid)) === false) {
                    $DB->delete_records('block_lockdownbrowser_sett', array('quizid' => $settings->quizid));
                }
            }
        }
    }
}

class backup_lockdownbrowser_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {

        $settings = new backup_nested_element(
            "settings", array("id"), array(
                                          "course",
                                          "quizid",
                                          "attempts",
                                          "reviews",
                                          "password",
                                          "monitor"
                                     ));

        $settings->set_source_table(
            "block_lockdownbrowser_sett", array("course" => backup::VAR_COURSEID));

        $settings->annotate_ids("quiz", "quizid");

        return $this->prepare_block_structure($settings);
    }
}
