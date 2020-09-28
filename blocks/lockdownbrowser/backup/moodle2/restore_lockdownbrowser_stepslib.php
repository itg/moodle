<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

class restore_lockdownbrowser_block_structure_step extends restore_structure_step {

    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element("settings", "/block/settings");

        return $paths;
    }

    public function process_settings($data) {

        global $DB;

        $data  = (object)$data;
        $oldid = $data->id;

        if (!isset($data->quizid)) {
            // our settings data will not exist in older backup files
            return;
        }

        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record("block_lockdownbrowser_sett", $data);

        $this->set_mapping("block_lockdownbrowser_sett", $oldid, $newitemid);
    }
}
