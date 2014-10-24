<?php
/** 
 * This script is intended to replace blocks in Moodle courses.
 * Specifically, this script will replace Quickmail blocks 
 * with Clampmail blocks (where appropriate).
 *
 * Basic workflow:
 *  -Get list of all courses
 *  -For each course
 *  --if Quickmail block exists
 *  ---if course is in category to force replacement
 *  ----delete any Quickmail history
 *  ----convert Quickmail block to Clampmail block
 *  ---else
 *  ----if Quickmail block has history
 *  -----Do nothing / leave block alone
 *  ----else
 *  -----if Clampmail block exists
 *  ------delete Quickmail block
 *  -----else
 *  ------convert Quickmail block to Clampmail block
 *
 * 2014-10-03
 * Matt Rice
 */

ini_set ('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

define('CLI_SCRIPT', true);

require_once('../config.php');

//Flush after every output
ob_implicit_flush(true);

global $DB;

//Any courses in these categories will have Quickmail replaced with Clampmail
//  regardless of history (any existing history will be deleted)
$force_replace_cats = array (
    7,         //Master courses
);

$course_count = $DB->count_records('course');
$current_count = 0;
$error_count = 0;
$change_count = 0;
$courses_rs = $DB->get_recordset('course', NULL, '', 'id, category, fullname');

foreach($courses_rs as $course) {
    $change_me = '';
    $current_count++;

    try {
        $context = context_course::instance($course->id);
        $qm_exists_conditions = array('parentcontextid'=>$context->id, 'blockname'=>'quickmail');
        if ($DB->record_exists('block_instances', $qm_exists_conditions)) {
            $quickmail_block = $DB->get_record('block_instances', $qm_exists_conditions);
            if (0 < count($force_replace_cats) && in_array($course->category, $force_replace_cats)) {
                $change_count++;
                $change_me = "M: Force-replacing Quickmail block with Clampmail in course '{$course->fullname}'";
                //Delete Quickmail history
                $DB->delete_records('block_quickmail_log', array('courseid'=>$context->id));
                //Convert to Clampmail
                $DB->update_record('block_instances', array('id'=>$quickmail_block->id, 'blockname'=>'clampmail'));
            }
            else {
                if (0 < $DB->count_records('block_quickmail_log', array('courseid'=>$course->id))) {
                    $change_me = "N: Quickmail history exists - leaving this block alone";
                }
                else {
                    $cm_exists_conditions = array('parentcontextid'=>$context->id, 'blockname'=>'clampmail');
                    if ($DB->record_exists('block_instances', $cm_exists_conditions)) {
                        $change_count++;
                        $change_me = "M: Found Clampmail block - deleting Quickmail block (leaving Clampmail block alone)";
                        $DB->delete_records('block_instances', $qm_exists_conditions);
                    }
                    else {
                        $change_count++;
                        $change_me = "M: Converting Quickmail block to Clampmail block";
                        $DB->update_record('block_instances', array('id'=>$quickmail_block->id, 'blockname'=>'clampmail'));
                    }
                }
            }
        }
        if ('' !== $change_me) {
            echo "\n({$current_count}/{$course_count}) Working on {$course->fullname}\n";
            echo $change_me . "\n";
        }
    }
    catch (Exception $e) {
        echo "Error occurred while processing course '{$course->fullname}' (id:{$course->id}): {$e->getMessage()}\n";
        echo "Moving to next course\n";
    }
}

//Clean up our recordset object
$courses_rs->close();

echo "\nDone checking {$course_count} courses ({$change_count} Modifications; {$error_count} Error(s))\n";
