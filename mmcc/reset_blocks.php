<pre>
<?php
ini_set ('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once('../config.php');
require_once($CFG->libdir.'/blocklib.php');

global $DB;

// Grab the courses we need to reset
// Fall 2013 courses:
// 		$courses = $DB->get_records('course', array('category'=>'4'));
// A single course by synonym:
// 		$courses = $DB->get_records('course', array('idnumber'=>'45663'));

//$courses = $DB->get_records('course', array('category'=>'9'));
$courses = $DB->get_records('course', array('idnumber'=>'46875'));

$course_count = 0;

//moodle 2.x format
foreach($courses as $course) {
	echo "loop\n";
	print_r($course);
	$context = get_context_instance(CONTEXT_COURSE,$course->id);
	blocks_delete_all_for_context($context->id);
	blocks_add_default_course_blocks($course);

	$course_count++;
}

echo "<h2>Done! Reset blocks for $course_count course(s).</h2>";

?>
</pre>
