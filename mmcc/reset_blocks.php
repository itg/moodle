<pre>
<?php
ini_set ('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

define('CLI_SCRIPT', true);

require_once('../config.php');
require_once($CFG->libdir.'/blocklib.php');

// Not sure if needed - Brandon 1/7/2015
require_once($CFG->dirroot. '/course/lib.php');

global $DB;

// Grab the courses we need to reset
// Fall 2013 courses:
// 		$conditions = array('category'=>'4');
// A single course by synonym:
// 		$conditions = array('idnumber'=>'45663');

//$conditions = array('category'=>'111');                //Get courses by category
//$conditions = array('idnumber'=>'314159265');          //Get courses by idnumber/synonym

//$conditions = array('id'=>'16442');                    //Get courses by course id (e.g. from URL)
//$conditions = array('id'=>'17265');                    //Get courses by course id (e.g. from URL)
//$conditions = array('id'=>'16622');                    //Get courses by course id (e.g. from URL)
//$conditions = array('id'=>'16436');                    //Get courses by course id (e.g. from URL)
//$conditions = array('id'=>'15501'); // example of course with html blocks                    //Get courses by course id (e.g. from URL)

//$conditions = array('id'=>'16456'); // Goffnett 2015WI

// These four blocks aren't enough!
$blocks_indicating_reset_needed =  array('calendar_upcoming', 'news_items', 'recent_activity', 'search_forums');
sort($blocks_indicating_reset_needed);

//$conditions = array('category'=>'109'); //2015wi

// fix kelley psy course
$conditions = array('id'=>'18433');                    //Get courses by course id (e.g. from URL)

$num_matched_courses = $DB->count_records('course', $conditions);
echo "Started at ". date('Y-m-d h:i:s a', time());
echo "\nProcessing $num_matched_courses course(s)\n";

$courses_rs = $DB->get_recordset('course', $conditions, '', 'id, category, fullname');

$change_count = 0;
$course_count = 0;
$error_count  = 0;
$courses_with_only_four_blocks = 0;
$courses_without_quickset = 0;
$courses_with_zero_blocks = 0;

//moodle 2.x format
foreach($courses_rs as $course) {
    try{

        $blocknames = course_get_format($course)->get_default_blocks();
        echo "Default block names:\n";
        print_r($blocknames);

        $context = context_course::instance($course->id);

        echo "\nInspecting course '{$course->fullname}' (context->id = {$context->id})\n";

        $blocks_exist_conditions = array('parentcontextid'=>$context->id);
        // We skip a course if the course has any blocks at all (the navigation and administration blocks do not appear in this list)
        $num_blocks = $DB->count_records('block_instances', $blocks_exist_conditions);
        //echo "Course has {$num_blocks}\n";

        // Retrieve info about the blocks
        $blocks_rs = $DB->get_recordset('block_instances', $blocks_exist_conditions);

        // Reset some metadata before inspecting this course
        $course_has_quickset = false;
        $course_blocknames = array();

        // What blocks does this course have? Need the names to find out if a reset is need and/or safe!
        foreach ($blocks_rs as $block) {
            //print_r($block);
            // echo "The blockname is {$block->blockname}\n";
            array_push($course_blocknames, $block->blockname);
            if ($block->blockname == 'quickset') {
                $course_has_quickset = true;
            }
        }

        // Clean up along the way
        if ($blocks_rs) {
            $blocks_rs->close();
        }

        sort($course_blocknames);
        //echo "The course had these blocks:\n";
        //print_r($course_blocknames);
        if ($course_blocknames == $blocks_indicating_reset_needed) {
            //echo "This course appears to have incomplete set of blocks. Please reset this course.\n";
            echo "{$course->fullname} only has the four blocks; reseting blocks...";
            $courses_with_only_four_blocks++;

            $course_context = context_course::instance($course->id);
            blocks_delete_all_for_context($course_context->id);
            blocks_add_default_course_blocks($course);
            $change_count++;

            echo "done\n";

        }

        // Was quickset missing? Count it!
        if (!$course_has_quickset) {
            $courses_without_quickset++;

            // Don't accidentally add the block a second time, brandon!
            if ($course_blocknames != $blocks_indicating_reset_needed) {
                echo "Trying to add just the quickset block...";
                $pagetypepattern = 'course-view-*';
                $page = new moodle_page();
                $page->set_course($course);
                // Brandon spent an unfortunate amount of time trying to add blocks before he realized
                // blocks must be associated with a position...
                // See this Moodle source code for all the inspiration Brandon needed: <https://github.com/moodle/moodle/blob/master/lib/blocklib.php>
                $page->blocks->add_blocks(array(BLOCK_POS_RIGHT => array('quickset')), $pagetypepattern);
                echo "done\n";
                $change_count++;
            }
        }


        if ($num_blocks) {
            // echo "Course has blocks - leaving this course alone\n";
        } else {
            $courses_with_zero_blocks++;
            //echo "Course has no blocks - adding default blocks\n";
            $course_context = context_course::instance($course->id);
            blocks_delete_all_for_context($course_context->id);
            blocks_add_default_course_blocks($course);
            $change_count++;
        }

        $course_count++;
    }
    catch (Exception $e) {
        $error_count++;
        echo "Error occurred while processing course '{$course->fullname}' (id:{$course->id}): {$e->getMessage()}\n";
        echo "Moving to next course\n";
    }
}

//Clean up our recordset object
if($courses_rs) {
    $courses_rs->close();
}

echo "Courses that had zero blocks: {$courses_with_zero_blocks}\n";
echo "Courses that had just the four blocks: {$courses_with_only_four_blocks}\n";
echo "Courses that had no quickset: {$courses_without_quickset}\n";
echo "<h2>Done checking {$course_count} courses ({$change_count} Modifications; {$error_count} Error(s))</h2>\n";
echo "Ended at ". date('Y-m-d h:i:s a', time());

?>
</pre>
