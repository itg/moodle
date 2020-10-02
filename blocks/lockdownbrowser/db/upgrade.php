<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

// upgrade block database structure
function xmldb_block_lockdownbrowser_upgrade($oldversion = 0) {

    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012082000) {

        // table names limited to 28 characters in Moodle 2.3+
        $table = new xmldb_table("block_lockdownbrowser_settings");
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, "block_lockdownbrowser_sett");
        }

        $table = new xmldb_table("block_lockdownbrowser_tokens");
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, "block_lockdownbrowser_toke");
        }

        $table = new xmldb_table("block_lockdownbrowser_sessions");
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, "block_lockdownbrowser_sess");
        }

        upgrade_block_savepoint(true, 2012082000, "lockdownbrowser");
    }
    if ($oldversion < 2013011800) {

        $table = new xmldb_table("block_lockdownbrowser");

        $index = new xmldb_index("course_ix");
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array("course"));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table("block_lockdownbrowser_sett");

        $field = new xmldb_field("course", XMLDB_TYPE_INTEGER, "10",
            XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, "id");
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field("monitor", XMLDB_TYPE_TEXT, "small",
            null, null, null, null, "password");
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index("course_ix");
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array("course"));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index("quiz_ix");
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array("quizid"));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_block_savepoint(true, 2013011800, "lockdownbrowser");
    }
    if ($oldversion < 2015063000) {

        $table = new xmldb_table("block_lockdownbrowser_sett");

        // there is apparently no way to check for key existence, so we'll drop
        // it (ignoring any errors), and then re-add it
        $key = new xmldb_key("quizid");
        $key->set_attributes(XMLDB_KEY_FOREIGN, array("quizid"), "quiz", array("id"));
        try {
            $dbman->drop_key($table, $key);
        } catch (Exception $e) {
            // ignore
        }
        $dbman->add_key($table, $key);

        upgrade_block_savepoint(true, 2015063000, "lockdownbrowser");
    }
    if ($oldversion < 2017020700) {

        // change to using quiz access rule (Trac #3111)
        $ldb_settings = $DB->get_records("block_lockdownbrowser_sett");
        foreach ($ldb_settings as $settings) {
            $quiz = $DB->get_record("quiz", array("id" => $settings->quizid));
            if ($quiz === false) {
                try {
                    // remove any orphaned block settings records for which no quiz record exists
                    $DB->delete_records('block_lockdownbrowser_sett', array('quizid' => $settings->quizid));
                } catch (Exception $e) {
                    // ignore
                }
            } else {
                // update quiz record for new access rule
                $quiz->popup = 0;
                $quiz->browsersecurity = 'lockdownbrowser';
                $DB->update_record("quiz", $quiz);
            }
        }
        upgrade_block_savepoint(true, 2017020700, "lockdownbrowser");
    }
    if ($oldversion < 2018091200) {

        $table = new xmldb_table("block_lockdownbrowser_sess");
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table("block_lockdownbrowser_toke");
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        upgrade_block_savepoint(true, 2018091200, "lockdownbrowser");
    }
    return true;
}

