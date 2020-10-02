<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

$lockdownbrowser_stepslib_file =
    "$CFG->dirroot/blocks/lockdownbrowser/backup/moodle2/backup_lockdownbrowser_stepslib.php";
require_once($lockdownbrowser_stepslib_file);

class backup_lockdownbrowser_block_task extends backup_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {

        $this->add_step(new backup_lockdownbrowser_block_execution_step(
            "backup_lockdownbrowser_block_execution_step"));
        $this->add_step(new backup_lockdownbrowser_block_structure_step(
            "lockdownbrowser_structure", "lockdownbrowser.xml"));
    }

    public function get_fileareas() {

        return array();
    }

    public function get_configdata_encoded_attributes() {

        return array();
    }

    static public function encode_content_links($content) {

        global $CFG;

        $result = $content;
        $base   = preg_quote($CFG->wwwroot, "/");

        $search = "/(" . $base . "\/blocks\/lockdownbrowser\/dashboard.php\?course\=)([0-9]+)/";
        $result = preg_replace($search, '$@LOCKDOWNBROWSERDASHBOARD*$2@$', $result);

        return $result;
    }
}

