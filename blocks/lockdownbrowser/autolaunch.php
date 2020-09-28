<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once("$CFG->dirroot/blocks/lockdownbrowser/locklib.php");

// get LDB check auto-launch parameters
$lockdownbrowser_ldbcheck_parm_name = lockdownbrowser_get_sdk2015_ldbcheck_parm();
$lockdownbrowser_ldbcheck_parm_value =
  optional_param($lockdownbrowser_ldbcheck_parm_name, false, PARAM_URL);

if ($lockdownbrowser_ldbcheck_parm_value !== false) {

    $lockdownbrowser_examtype_ldbonly = lockdownbrowser_get_ldb_examtype_ldbonly();
    $lockdownbrowser_examtype_monitor = lockdownbrowser_get_ldb_examtype_monitor();

    if (strcmp($lockdownbrowser_ldbcheck_parm_value, $lockdownbrowser_examtype_monitor) === 0) {
        $lockdownbrowser_redir_url = lockdownbrowser_get_sdk2015_monitor_check();
    }
    if (strcmp($lockdownbrowser_ldbcheck_parm_value, $lockdownbrowser_examtype_ldbonly) === 0) {
        $lockdownbrowser_redir_url = lockdownbrowser_get_sdk2015_ldbonly_check();
    }
    if (strlen($lockdownbrowser_redir_url) == 0) {
        print_error("errinvalidldbcheckparm", "block_lockdownbrowser");
    }
    header("Location: $lockdownbrowser_redir_url");
    exit;
}
// get exam auto-launch parameters
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
// get static server id parameter
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_serverid_cookie)
  || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_serverid_cookie) == 0
  ) {
    print_error("errsdk2015serveridname", "block_lockdownbrowser");
}
$lockdownbrowser_serverid_name = $CFG->block_lockdownbrowser_ldb_sdk2015_serverid_cookie;
$lockdownbrowser_serverid_value = "1";

// get challenge parameter
$lockdownbrowser_challenge_name = lockdownbrowser_get_challenge_name();
$lockdownbrowser_challenge_value =
  optional_param($lockdownbrowser_challenge_name, false, PARAM_ALPHANUMEXT);

if ($lockdownbrowser_challenge_value === false) {
    // redirect to self adding server id and challenge parameters;
    // older CLDB clients require a completely-loaded document prior to receiving the challenge
    $lockdownbrowser_challenge_value = lockdownbrowser_create_server_challenge();
    $lockdownbrowser_redir_url = $CFG->wwwroot . "/blocks/lockdownbrowser/autolaunch.php"
      . "?" . urlencode($lockdownbrowser_session_parm_name) . "=" . urlencode($lockdownbrowser_session_parm_value)
      . "&" . urlencode($lockdownbrowser_examid_parm_name) . "=" . urlencode($lockdownbrowser_examid_parm_value)
      . "&" . urlencode($lockdownbrowser_serverid_name) . "=" . urlencode($lockdownbrowser_serverid_value)
      . "&" . urlencode($lockdownbrowser_challenge_name) . "=" . urlencode($lockdownbrowser_challenge_value);
    $lockdownbrowser_page_html = "<html>\r\n"
      . "<!-- Respondus LockDown Browser Auto-Launch Page (1) -->\r\n"
      . "<head>\r\n"
      . "<script>\r\n"
      . "function redirect() {\r\n"
      . "    document.location = '$lockdownbrowser_redir_url';\r\n"
      . "}\r\n"
      . "</script>\r\n"
      . "</head>\r\n"
      . "<body onload=\"redirect();\"/>\r\n"
      . "</html>\r\n";
    echo $lockdownbrowser_page_html;
    exit;
}
// get session cookie domain
$lockdownbrowser_session_cookie_domain = ""; // a valid value for the setcookie call
// reference prepare_cookies() in $CFG->libdir/classes/session/manager.php
if (isset($CFG->sessioncookiedomain) && strlen($CFG->sessioncookiedomain) > 0) {
    if (isset($CFG->wwwroot) && strlen($CFG->wwwroot) > 0) {
        $host = parse_url($CFG->wwwroot, PHP_URL_HOST);
    } else {
        print_error("nolmsrooturl", "block_lockdownbrowser");
    }
    if (strcmp($CFG->sessioncookiedomain, $host) === 0) {
        $lockdownbrowser_session_cookie_domain = $host;
    } else if (substr($CFG->sessioncookiedomain, 0, 1) === '.') {
        if (preg_match('|^.*' . preg_quote($CFG->sessioncookiedomain, '|') . '$|', $host)) {
            $lockdownbrowser_session_cookie_domain = $CFG->sessioncookiedomain;
        }
    } else if (preg_match('|^.*\.' . preg_quote($CFG->sessioncookiedomain, '|') . '$|', $host)) {
        $lockdownbrowser_session_cookie_domain = $CFG->sessioncookiedomain;
    }
    $lockdownbrowser_session_cookie_domain = $CFG->sessioncookiedomain;
}
// get session cookie path
$lockdownbrowser_session_cookie_path = ""; // a valid value for the setcookie call
// reference prepare_cookies() in $CFG->libdir/classes/session/manager.php
if (isset($CFG->sessioncookiepath)) {
    $lockdownbrowser_session_cookie_path = $CFG->sessioncookiepath;
}
if ($lockdownbrowser_session_cookie_path !== '/') {
    if (isset($CFG->wwwroot) && strlen($CFG->wwwroot) > 0) {
        $path = parse_url($CFG->wwwroot, PHP_URL_PATH) . '/';
    } else {
        print_error("nolmsrooturl", "block_lockdownbrowser");
    }
    if (strlen($lockdownbrowser_session_cookie_path) === 0) {
        $lockdownbrowser_session_cookie_path = $path;
    } else {
        if (strpos($path, $lockdownbrowser_session_cookie_path) !== 0
          || substr($lockdownbrowser_session_cookie_path, -1) !== '/'
          ) {
            $lockdownbrowser_session_cookie_path = $path;
        }
    }
}
// set session cookie
if (!isset($CFG->block_lockdownbrowser_ldb_session_cookie)
  || strlen($CFG->block_lockdownbrowser_ldb_session_cookie) == 0
  ) {
    print_error("errsessioncookiename", "block_lockdownbrowser");
}
$lockdownbrowser_session_cookie_name = $CFG->block_lockdownbrowser_ldb_session_cookie . $CFG->sessioncookie;

if (!setcookie($lockdownbrowser_session_cookie_name, $lockdownbrowser_session_parm_value,
  0, $lockdownbrowser_session_cookie_path, $lockdownbrowser_session_cookie_domain)) {
    print_error("errsetsession", "block_lockdownbrowser");
}
// create prestart URL
$lockdownbrowser_prestart_url = $CFG->wwwroot . "/blocks/lockdownbrowser/autoprestart.php"
  . "?" . urlencode($lockdownbrowser_session_parm_name) . "=" . urlencode($lockdownbrowser_session_parm_value)
  . "&" . urlencode($lockdownbrowser_examid_parm_name) . "=" . urlencode($lockdownbrowser_examid_parm_value)
  . "&" . urlencode($lockdownbrowser_serverid_name) . "=" . urlencode($lockdownbrowser_serverid_value)
  . "&" . urlencode($lockdownbrowser_challenge_name) . "=" . urlencode($lockdownbrowser_challenge_value);

// create exit browser URL
$lockdownbrowser_command_scheme = lockdownbrowser_get_sdk2015_command_scheme();
$lockdownbrowser_command_exitb = lockdownbrowser_get_sdk2015_command_exitb();
$lockdownbrowser_exitb_value = "1";

$lockdownbrowser_exitb_url = $lockdownbrowser_command_scheme
  . ":" . urlencode($lockdownbrowser_command_exitb)
  . "=" . urlencode($lockdownbrowser_exitb_value);

// emit response page
$lockdownbrowser_launch_button_title = get_string('prestartexambutton', 'block_lockdownbrowser');
$lockdownbrowser_exit_button_title = get_string('exitbrowserbutton', 'block_lockdownbrowser');
$lockdownbrowser_page_title = get_string('autolaunchpagetitle', 'block_lockdownbrowser');

$lockdownbrowser_page_html = "<html>\r\n"
  . "<!-- Respondus LockDown Browser Auto-Launch Page (2) -->\r\n"
  . "<head>\r\n"
  . "<link rel=\"stylesheet\" type=\"text/css\" href=\"//fonts.googleapis.com/css?family=Open+Sans\" />\r\n"
  . "<link rel=\"stylesheet\" type=\"text/css\" href=\"//fonts.googleapis.com/css?family=Raleway\" />\r\n"
  . "<title>$lockdownbrowser_page_title</title>\r\n"
  . "<script>\r\n"
  . "function prestartExam() {\r\n"
  . "    window.open('$lockdownbrowser_prestart_url', 'LockDownBrowserPrestart');\r\n"
  . "}\r\n"
  . "function exitBrowser() {\r\n"
  . "    document.location = '$lockdownbrowser_exitb_url';\r\n"
  . "}\r\n"
  . "</script>\r\n"
  . "<style type=\"text/css\">\r\n"
  . "body {\r\n"
  . "  background-color: #ffffff;\r\n"
  . "}\r\n"
  . ".autolaunch-title {\r\n"
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
  . ".autolaunch-content {\r\n"
  . "  text-align: center;\r\n"
  . "}\r\n"
  . ".launch-exam-button {\r\n"
  . "  width: 128px;\r\n"
  . "  height: 40px;\r\n"
  . "  border-radius: 10px;\r\n"
  . "  background-color: #387da6;\r\n"
  . "  cursor: pointer;\r\n"
  . "  display: inline-block;\r\n"
  . "  margin-right: 20px;\r\n"
  . "}\r\n"
  . ":hover.launch-exam-button {\r\n"
  . "  background-color: #569FCA;\r\n"
  . "}\r\n"
  . ".launch-exam-text {\r\n"
  . "  font-family: Raleway, Helvetica, sans-serif;\r\n"
  . "  font-size: 14px;\r\n"
  . "  font-weight: bold;\r\n"
  . "  font-style: normal;\r\n"
  . "  font-stretch: normal;\r\n"
  . "  line-height: normal;\r\n"
  . "  letter-spacing: normal;\r\n"
  . "  color: #fafcea;\r\n"
  . "  text-align: center;\r\n"
  . "  line-height: 40px;\r\n"
  . "}\r\n"
  . ".exit-browser-button {\r\n"
  . "  width: 138px;\r\n"
  . "  height: 40px;\r\n"
  . "  border-radius: 10px;\r\n"
  . "  background-color: #efefef;\r\n"
  . "  border: solid 1px #cccccc;\r\n"
  . "  cursor: pointer;\r\n"
  . "  display: inline-block;\r\n"
  . "}\r\n"
  . ":hover.exit-browser-button {\r\n"
  . "  background-color: white;\r\n"
  . "}\r\n"
  . ".exit-browser-text {\r\n"
  . "  font-family: Raleway, Helvetica, sans-serif;\r\n"
  . "  font-size: 14px;\r\n"
  . "  font-weight: bold;\r\n"
  . "  font-style: normal;\r\n"
  . "  font-stretch: normal;\r\n"
  . "  line-height: normal;\r\n"
  . "  letter-spacing: normal;\r\n"
  . "  color: #666666;\r\n"
  . "  text-align: center;\r\n"
  . "  line-height: 40px;\r\n"
  . "}\r\n"
  . "</style>\r\n"
  . "</head>\r\n"
  . "<body>\r\n"
  . "<div class=\"autolaunch-title\">$lockdownbrowser_page_title</div>\r\n"
  . "<div class=\"autolaunch-content\">\r\n"
  . "<div class=\"launch-exam-button\" onclick=\"prestartExam();\">\r\n"
  . "<div class=\"launch-exam-text\">$lockdownbrowser_launch_button_title</div>\r\n"
  . "</div>\r\n"
  . "<div class=\"exit-browser-button\" onclick=\"exitBrowser();\">\r\n"
  . "<div class=\"exit-browser-text\">$lockdownbrowser_exit_button_title</div>\r\n"
  . "</div>\r\n"
  . "</div>\r\n"
  . "</body>\r\n"
  . "</html>\r\n";

echo $lockdownbrowser_page_html;

