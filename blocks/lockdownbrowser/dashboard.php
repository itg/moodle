<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

define ("LOCKDOWNBROWSER_DASHBOARD_IFRAMEURL",
    "https://smc-service-cloud.respondus2.com/MONServer/lms/dashboard.do");

require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once("$CFG->dirroot/course/lib.php");
require_once("$CFG->dirroot/blocks/lockdownbrowser/locklib.php");

lockdownbrowser_check_plugin_dependencies(2);

$lockdownbrowser_urlcourse = required_param('course', PARAM_INT);

if (bccomp($CFG->version, 2013111800, 2) >= 0) {
    // Moodle 2.6.0+.
    $lockdownbrowser_context = context_course::instance($lockdownbrowser_urlcourse);
} else {
    // Prior to Moodle 2.6.0.
    $lockdownbrowser_context = get_context_instance(CONTEXT_COURSE, $lockdownbrowser_urlcourse);
}

if (!has_capability('moodle/course:manageactivities', $lockdownbrowser_context)
  && !has_capability('moodle/course:viewhiddenactivities', $lockdownbrowser_context) // Trac #3595
  ) {
    redirect($CFG->wwwroot . '/index.php');
    die;
}
if (!get_site()) {
    redirect($CFG->wwwroot . '/' . $CFG->admin . '/index.php');
}
if (!($lockdownbrowser_course = $DB->get_record('course',
    array('id' => $lockdownbrowser_urlcourse)))
) {
    print_error('invalidcourseid', 'error');
}

$PAGE->set_url("$CFG->wwwroot/blocks/lockdownbrowser/dashboard.php",
    array('course' => $lockdownbrowser_course->id)); // defined here to avoid notices on errors etc

require_login($lockdownbrowser_course);

$PAGE->set_title(get_string('course') . ': '
  . $lockdownbrowser_course->fullname . " - Dashboard");
$PAGE->set_heading('Respondus LockDown Browser');

echo $OUTPUT->header();

lockdownbrowser_purge_settings();

$lockdownbrowser_institution_id   = $CFG->block_lockdownbrowser_ldb_serverid;
$lockdownbrowser_server_name      = $CFG->block_lockdownbrowser_ldb_servername;
$lockdownbrowser_course_id        = $lockdownbrowser_urlcourse;
$lockdownbrowser_instructor_id    = $USER->username;
$lockdownbrowser_is_admin         = is_siteadmin() ? "true" : "false";
$lockdownbrowser_disable_settings = has_capability('moodle/course:manageactivities', $lockdownbrowser_context) ? "false" : "true"; // Trac #3595
$lockdownbrowser_time             = strval(time()) . "000";

$lockdownbrowser_mac = lockdownbrowser_dashboardgeneratemac2(
    $lockdownbrowser_institution_id . $lockdownbrowser_server_name
    . $lockdownbrowser_course_id . $lockdownbrowser_instructor_id
    . $lockdownbrowser_is_admin . $lockdownbrowser_disable_settings // Trac #3595
    . $lockdownbrowser_time
);

$lockdownbrowser_iframe_url = LOCKDOWNBROWSER_DASHBOARD_IFRAMEURL
    . "?institutionId=" . $lockdownbrowser_institution_id // assume url-encoded
    . "&serverName=" . $lockdownbrowser_server_name // assume url-encoded
    . "&courseId=" . urlencode($lockdownbrowser_course_id)
    . "&instructorId=" . urlencode($lockdownbrowser_instructor_id)
    . "&isAdmin=" . urlencode($lockdownbrowser_is_admin)
    . "&disableSettings=" . urlencode($lockdownbrowser_disable_settings) // Trac #3595
    . "&time=" . urlencode($lockdownbrowser_time)
    . "&mac=" . urlencode($lockdownbrowser_mac);

$lockdownbrowser_iframe_error = get_string("iframe_error", "block_lockdownbrowser");

echo "<div id=\"ldbFrameWait\" style=\"text-align: center\">";
echo "<img src=\"$CFG->wwwroot/blocks/lockdownbrowser/pix/round_activity_indicator.gif\"/>";
echo "<br/>";
echo "Please wait ...";
echo "</div>";
echo "<iframe  id=\"ldbFrame\" src=\"$lockdownbrowser_iframe_url\" onload=\"ldb_HideFrameWait()\" ";
echo "scrolling=\"no\" frameborder=\"0\" width=\"100%\" height=\"10000px\">";
echo "$lockdownbrowser_iframe_error";
echo "</iframe>";
echo "<script type=\"text/javascript\">";
echo "function ldb_HideFrameWait() {";
echo "    document.getElementById(\"ldbFrameWait\").style.display = \"none\";";
echo "}";
echo "function ldb_ResizeLDBFrame(height)";
echo "{";
echo "    document.getElementById(\"ldbFrame\").height = parseInt(height);";
echo "}";
echo "</script>";

echo $OUTPUT->footer();

exit;

function lockdownbrowser_dashboardgeneratemac($input) {

    // old-style mac;
    // broken for characters > 127 between Respondus clients and servers
    $chararray = preg_split('//', $input, -1, PREG_SPLIT_NO_EMPTY);
    $strdatavalue = 0;
    foreach ($chararray as $char) {
        $strdatavalue += ord($char);
    }
    $profile_secret = lockdownbrowser_get_profile_secret();
    $mac = md5($strdatavalue . $profile_secret);

    return $mac;
}

function lockdownbrowser_dashboardgeneratemac2($input) {

    // new-style mac;
    // need leading underscore so server can differentiate from old-style mac
    $profile_secret = lockdownbrowser_get_profile_secret();
    $mac = "_" . md5($input . $profile_secret);

    return $mac;
}

