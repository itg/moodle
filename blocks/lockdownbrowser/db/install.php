<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

function xmldb_block_lockdownbrowser_install() {

    // called immediately after the DB schema associated with the block is created

    global $DB;
    global $CFG;

    $enable_log  = false; // set TRUE to enable logging to temp file
    $install_log = "ldb_install.log";

    $warning_settings =
      "<p>lockdownbrowser block install warning: could not migrate quiz settings from legacy lockdown module</p>";
    $warning_quiz_files =
      "<p>lockdownbrowser block install warning: The /mod/lockdown integration changes to /mod/quiz are still in place.
      You must manually replace the changed pages in /mod/quiz.  The pages must be from the current version of Moodle.</p>";

    $block_settings_table  = "block_lockdownbrowser_sett";
    $module_settings_table = "lockdown_settings";

    $quiz_table          = "quiz";
    $quiz_file           = "$CFG->dirroot/mod/quiz/attempt.php";

    $browser_security_choice_key = "lockdownbrowser"; // must match browsersecuritychoicekey string

    $debug_info        = "* start: " . date("m-d-Y H:i:s") . "\r\n";
    $exception_msg     = "";
    $migrate_settings  = false;
    $warn_quiz_changes = false;

    try {
        $debug_info .= "* migrating settings from legacy ldb module to ldb block\r\n";
        $ok               = true;
        $dbman            = $DB->get_manager();
        $table            = new xmldb_table($module_settings_table);
        $migrate_settings = $dbman->table_exists($table);

        if (!$migrate_settings) {
            $debug_info .= "* no need to migrate settings: table $module_settings_table does not exist\r\n";
        }
        if ($migrate_settings) {
            $table = new xmldb_table($block_settings_table);
            $ok    = $dbman->table_exists($table);
            if (!$ok) {
                $debug_info .= "* error: table $block_settings_table does not exist\r\n";
            }
        }
        if ($migrate_settings && $ok) {
            $records = $DB->get_records($module_settings_table);
            $ok      = ($records !== false || count($records) != 0);
            if (!$ok) {
                $debug_info .= "* error: table $module_settings_table has no records\r\n";
            }
        }
        if ($migrate_settings && $ok) {
            foreach ($records as $modset) {
                if ($modset->attempts == 0 && $modset->reviews == 0) {
                    continue; // not LDB quiz, nothing to migrate
                }
                $quiz = $DB->get_record($quiz_table, array("id" => $modset->quizid));
                if ($quiz === false) {
                    continue; // quiz gone, no need to migrate
                }
                $blkset = $DB->get_record($block_settings_table, array("quizid" => $quiz->id));
                if ($blkset !== false) {
                    continue; // quiz settings already migrated
                }
                $blkset           = new stdClass;
                $blkset->course   = $quiz->course;
                $blkset->quizid   = $quiz->id;
                $blkset->attempts = 0;
                $blkset->reviews  = 0;
                $blkset->password = $modset->password;
                $blkset->monitor  = "";
                $ok               = $DB->insert_record($block_settings_table, $blkset);
                if (!$ok) {
                    $debug_info .= "* error: could not insert record into table $block_settings_table\r\n";
                    break;
                }
                $quiz->popup = 0;
                $quiz->browsersecurity = $browser_security_choice_key;
                $ok = $DB->update_record($quiz_table, $quiz);
                if (!$ok) {
                    $debug_info .= "* error: could not update quiz record for block settings record\r\n";
                    break;
                }
            }
        }
        if ($migrate_settings && $ok) {
            $debug_info .= "* settings migration succeeded\r\n";
        }
    } catch (Exception $e) {

        $info = get_exception_info($e);
        $msg  = "\r\nmessage: $info->message"
            . "\r\nerrorcode: $info->errorcode"
            . "\r\nbacktrace: $info->backtrace"
            . "\r\nlink: $info->link"
            . "\r\nmoreinfourl: $info->moreinfourl"
            . "\r\na: $info->a"
            . "\r\ndebuginfo: $info->debuginfo\r\n"
            . "\r\nstacktrace: "
            . $e->getTraceAsString();
        $debug_info .= "* exception occurred: $msg\r\n";
        $ok = false;
    }

    if ($migrate_settings && !$ok) {
        $exception_msg .= $warning_settings;
    }

    try {
        $debug_info .= "* checking for file changes made to quiz module by legacy ldb module\r\n";

        if (is_readable($quiz_file)) {
            $contents = file_get_contents($quiz_file);
            if ($contents !== false) {
                $pos               = strpos($contents, "## LockDown");
                $warn_quiz_changes = ($pos !== false);
            } else {
                $debug_info .= "* error(1): cannot read quiz file $quiz_file\r\n";
                $warn_quiz_changes = false;
            }
        } else {
            $debug_info .= "* error(2): cannot read quiz file $quiz_file\r\n";
            $warn_quiz_changes = false;
        }
        if ($warn_quiz_changes) {
            $debug_info .= "* need to restore quiz file changes: will display warning\r\n";
        } else {
            $debug_info .= "* assuming no need to restore quiz file changes: could not find edit marker string\r\n";
        }
    } catch (Exception $e) {

        $info = get_exception_info($e);
        $msg  = "\r\nmessage: $info->message"
            . "\r\nerrorcode: $info->errorcode"
            . "\r\nbacktrace: $info->backtrace"
            . "\r\nlink: $info->link"
            . "\r\nmoreinfourl: $info->moreinfourl"
            . "\r\na: $info->a"
            . "\r\ndebuginfo: $info->debuginfo\r\n"
            . "\r\nstacktrace: "
            . $e->getTraceAsString();
        $debug_info .= "* exception occurred: $msg\r\n";
        $ok = false;
    }

    if ($warn_quiz_changes) {
        $exception_msg .= $warning_quiz_files;
    }

    // write debug file
    $debug_info .= "* end\r\n";
    if ($enable_log) {
        if (isset($CFG->tempdir)) {
            $path = "$CFG->tempdir/$install_log";
        } else {
            $path = "$CFG->dataroot/temp/$install_log";
        }
        $handle = fopen($path, "wb");
        if ($handle !== false) {
            fwrite($handle, $debug_info, strlen($debug_info));
            fclose($handle);
        }
    }

    // inform user if migration warnings exist
    if (strlen($exception_msg) > 0) {
        throw new moodle_exception($exception_msg, "block_lockdownbrowser");
    }
}

function xmldb_block_lockdownbrowser_uninstall() {

    //  executed when the block is uninstalled, before dropping its DB schema
}
