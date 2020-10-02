<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once("$CFG->libdir/sessionlib.php");
require_once("$CFG->dirroot/blocks/lockdownbrowser/locklib.php");

// get parameters
$lockdownbrowser_session_parm_name = lockdownbrowser_get_sdk2015_session_parm();
$lockdownbrowser_session_parm_value =
  optional_param($lockdownbrowser_session_parm_name, false, PARAM_NOTAGS); // cannot use PARAM_BASE64

if ($lockdownbrowser_session_parm_value === false) {
    print_error("nosessionparm", "block_lockdownbrowser");
}
$lockdownbrowser_examid_parm_name = lockdownbrowser_get_sdk2015_examid_parm();
$lockdownbrowser_examid_parm_value =
  optional_param($lockdownbrowser_examid_parm_name, false, PARAM_INT);

if ($lockdownbrowser_examid_parm_value === false) {
    print_error("noexamidparm", "block_lockdownbrowser");
}
$lockdownbrowser_challenge_name = lockdownbrowser_get_challenge_name();
$lockdownbrowser_challenge_value =
  optional_param($lockdownbrowser_challenge_name, false, PARAM_ALPHANUMEXT);

if ($lockdownbrowser_challenge_value === false) {
    print_error("nochallengeparm", "block_lockdownbrowser");
}
// check session
$lockdownbrowser_session_cookie = lockdownbrowser_get_session_cookie();
if ($lockdownbrowser_session_cookie === false) {
    print_error("errinvalidsession", "block_lockdownbrowser");
}
if (strcmp($lockdownbrowser_session_cookie, $lockdownbrowser_session_parm_value) !== 0) {
    print_error("errsessionmatch", "block_lockdownbrowser");
}
// init SDK2015 session info
$_SESSION['lockdownbrowser_sdk2015_session']['active'] = true;
$_SESSION['lockdownbrowser_sdk2015_session']['challenge'] = $lockdownbrowser_challenge_value;
unset($_SESSION['lockdownbrowser_sdk2015_session']['response']);

// check for response to challenge
$lockdownbrowser_client_response = lockdownbrowser_get_client_response();
if ($lockdownbrowser_client_response !== false) {
    if (!lockdownbrowser_validate_client_response()) {
        print_error("errchallengeresponse", "block_lockdownbrowser");
    }
}
// get session key
$lockdownbrowser_session_key = sesskey();
if ($lockdownbrowser_session_key === false) {
    print_error("errsessionkey", "block_lockdownbrowser");
}
// create exam url
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_security_vhigh)
  || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_security_vhigh) == 0
  ) {
    print_error("errsdk2015securityvhigh", "block_lockdownbrowser");
}
$lockdownbrowser_security_name = $CFG->block_lockdownbrowser_ldb_sdk2015_security_vhigh;
$lockdownbrowser_security_value = "1";

$lockdownbrowser_exam_url = $CFG->wwwroot . "/mod/quiz/startattempt.php"
  . "?" . urlencode($lockdownbrowser_security_name)
  . "=" . urlencode($lockdownbrowser_security_value);

// emit response page
$lockdownbrowser_page_title = get_string('prestartpagetitle', 'block_lockdownbrowser');
$lockdownbrowser_page_text = get_string('prestartpagetext', 'block_lockdownbrowser');
$lockdownbrowser_prestart_fn = lockdownbrowser_get_sdk2015_prestart_fn();

$lockdownbrowser_page_html = "<html>\r\n"
  . "<!-- Respondus LockDown Browser Exam Pre-Start Page -->\r\n"
  . "<head>\r\n"
  . "<link rel=\"stylesheet\" type=\"text/css\" href=\"//fonts.googleapis.com/css?family=Open+Sans\" />\r\n"
  . "<link rel=\"stylesheet\" type=\"text/css\" href=\"//fonts.googleapis.com/css?family=Raleway\" />\r\n"
  . "<title>$lockdownbrowser_page_title</title>\r\n"
  . "<script>\r\n"
  . "function $lockdownbrowser_prestart_fn() {\r\n"
  . "    document.forms['startexam'].submit();\r\n"
  . "}\r\n"
  . "</script>\r\n"
  . "<style type=\"text/css\">\r\n"
  . "body {\r\n"
  . "  background-color: #ffffff;\r\n"
  . "}\r\n"
  . ".prestart-title {\r\n"
  . "  font-family: Raleway, Helvetica, sans-serif;\r\n"
  . "  font-size: 24px;\r\n"
  . "  font-weight: 300;\r\n"
  . "  font-style: normal;\r\n"
  . "  font-stretch: normal;\r\n"
  . "  line-height: normal;\r\n"
  . "  letter-spacing: normal;\r\n"
  . "  text-align: center;\r\n"
  . "  color: #1c3f53;\r\n"
  . "  margin-top: 36px;\r\n"
  . "  margin-bottom: 36px;\r\n"
  . "}\r\n"
  . ".prestart-content {\r\n"
  . "  font-family: \"Open Sans\", Helvetica, sans-serif;\r\n"
  . "  font-size: 14px;\r\n"
  . "  font-weight: normal;\r\n"
  . "  font-style: normal;\r\n"
  . "  font-stretch: normal;\r\n"
  . "  line-height: normal;\r\n"
  . "  letter-spacing: normal;\r\n"
  . "  text-align: center;\r\n"
  . "  color: #666666;\r\n"
  . "}\r\n"
  . ".spinner {\r\n"
  . "  width: 36px;\r\n"
  . "  height: 36px;\r\n"
  . "  margin-right: 10px;\r\n"
  . "  vertical-align: middle;\r\n"
  . "}\r\n"
  . "</style>\r\n"
  . "</head>\r\n"
  . "<body>\r\n"
  . "<div class=\"prestart-title\">$lockdownbrowser_page_title</div>\r\n"
  . "<div class=\"prestart-content\">\r\n"
  . "<img class=\"spinner\" src=\"pix/round_activity_indicator.gif\" />\r\n"
  . "$lockdownbrowser_page_text\r\n"
  . "</div>\r\n"
  . "<form name='startexam' method='post' style='display:none;' action='$lockdownbrowser_exam_url'>\r\n"
  . "<input type='hidden' name='cmid' value='$lockdownbrowser_examid_parm_value'/>\r\n"
  . "<input type='hidden' name='sesskey' value='$lockdownbrowser_session_key'/>\r\n"
  . "</form>\r\n"
  . "</body>\r\n"
  . "</html>\r\n";

echo $lockdownbrowser_page_html;

