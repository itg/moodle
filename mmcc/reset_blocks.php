<pre>
<?php
ini_set ('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

define('CLI_SCRIPT', true);

require_once('../config.php');
require_once($CFG->libdir.'/blocklib.php');

global $DB;

// Grab the courses we need to reset
// Fall 2013 courses:
// 		$conditions = array('category'=>'4'));
// A single course by synonym:
// 		$conditions = array('idnumber'=>'45663'));

//$conditions = array('category'=>'111'));                //Get courses by category
//$conditions = array('idnumber'=>'314159265'));          //Get courses by idnumber/synonym
//$conditions = array('id'=>'15429'));                    //Get courses by course id (e.g. from URL)

//$conditions = array('category'=>'14');

$num_matched_courses = $DB->count_records('course', $conditions);
echo "Started at ". date('Y-m-d h:i:s a', time());
echo "\nProcessing $num_matched_courses course(s)\n";

//$courses_rs = $DB->get_recordset('course', $conditions, '', 'id, category, fullname');

$change_count = 0;
$course_count = 0;
$error_count  = 0;

//moodle 2.x format
foreach($courses_rs as $course) {
    try{
        $context = context_course::instance($course->id);

        echo "\nInspecting course '{$course->fullname}' (context->id = {$context->id})\n";

        $blocks_exist_conditions = array('parentcontextid'=>$context->id);
        //We skip a course if the course has any blocks at all (the navigation and administration blocks do not appear in this list)
        if ($DB->count_records('block_instances', $blocks_exist_conditions)) {
            echo "Course has blocks - leaving this course alone\n";
        }
        else {
            echo "Course has no blocks - adding default blocks\n";
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

echo "<h2>Done checking {$course_count} courses ({$change_count} Modifications; {$error_count} Error(s))</h2>\n";
echo "Ended at ". date('Y-m-d h:i:s a', time());

?>
</pre>
