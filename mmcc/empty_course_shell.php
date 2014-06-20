<?php
ini_set ('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
require_once('../config.php');

//The id (from the URL) of the course to empty
$courseid = 15429;

global $CFG;
require_once($CFG->libdir.'/moodlelib.php');

echo "Removing content from course id={$courseid}";

$result = remove_course_contents($courseid);

if ($result) {
  echo "<h3>Course successfully emptied</h3>";
}
else {
  echo "<h3>Some content not removed!</h3>";
}



