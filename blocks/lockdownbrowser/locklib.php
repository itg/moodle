<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

define("LOCKDOWNBROWSER_LOCKLIB_ENABLE_LOG", false); // set true to enable logging to temp file
define("LOCKDOWNBROWSER_LOCKLIB_LOG", "ldb_locklib.log");
define("LOCKDOWNBROWSER_LOCKLIB_ENABLE_4851", true); // set true to enable changes for Trac #4851

if (!isset($CFG)) {
    require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");
}
require_once("$CFG->libdir/moodlelib.php");

require_once(dirname(__FILE__) . "/locklibcfg.php");
require_once(dirname(__FILE__) . "/blowfish.php"); // Trac #3884

function lockdownbrowser_set_settings($quizid, $reqquiz, $reqreview, $exitpass, $monitor) {

    $ldbopt           = new stdClass;
    $ldbopt->attempts = $reqquiz;
    $ldbopt->reviews  = $reqreview;
    $ldbopt->password = $exitpass;
    $ldbopt->monitor  = $monitor;
    return lockdownbrowser_set_quiz_options($quizid, $ldbopt);
}

function lockdownbrowser_get_quiz_options($quizid) {

    global $DB;

    $ldbopt = $DB->get_record('block_lockdownbrowser_sett', array('quizid' => $quizid));
    if ($ldbopt) {
        $quiz = $DB->get_record("quiz", array("id" => $quizid));
        if ($quiz === false || $quiz->course != $ldbopt->course) {
            // this will catch some orphans (deleted quizzes) and some false
            // positives (orphans that are false matches due to backup/restore);
            // also see quizaccess_lockdownbrowser::delete_settings
            try {
                lockdownbrowser_delete_options($quizid);
            } catch (Exception $ex) {
                // ignore possible multi-session conflicts
            }
            $ldbopt = false;
        }
    }
    return $ldbopt;
}

function lockdownbrowser_set_quiz_options($quizid, $ldbopt = null) {

    global $DB;

    if ($ldbopt == null) {
        $ldbopt           = new stdClass;
        $ldbopt->attempts = 0;
        $ldbopt->reviews  = 0;
        $ldbopt->password = "";
        $ldbopt->monitor  = "";
    }
    $quiz = $DB->get_record("quiz", array("id" => $quizid));
    if ($quiz === false) {
        return false;
    }
    $ok          = true;
    $newsettings = false;
    if (is_null($ldbopt->attempts)) {
        $ldbopt->attempts = 0;
    }
    if (is_null($ldbopt->reviews)) {
        $ldbopt->reviews = 0;
    }
    if (is_null($ldbopt->password)) {
        $ldbopt->password = "";
    }
    if (is_null($ldbopt->monitor)) {
        $ldbopt->monitor = "";
    }
    $existing = lockdownbrowser_get_quiz_options($quizid);
    if ($existing) {
        $existing->attempts = $ldbopt->attempts;
        $existing->reviews  = $ldbopt->reviews;
        $existing->password = $ldbopt->password;
        $existing->monitor  = $ldbopt->monitor;
        $existing->course    = $quiz->course;
        $ok    = $DB->update_record('block_lockdownbrowser_sett', $existing);
    } else {
        $newsettings    = true;
        $ldbopt->course = $quiz->course;
        $ldbopt->quizid = $quizid;
        $ok = $DB->insert_record('block_lockdownbrowser_sett', $ldbopt);
    }
    return $ok;
}

function lockdownbrowser_delete_options($quizid) {

    global $DB;
    $DB->delete_records('block_lockdownbrowser_sett', array('quizid' => $quizid));
}

function lockdownbrowser_purge_settings() {

    global $DB;

    // remove any orphaned records from our settings table;
    // orphans occur whenever a quiz is deleted without first removing the LDB
    //   requirement in our dashboard;
    // also see dashboard.php and quizaccess_lockdownbrowser::delete_settings
    $records = $DB->get_records('block_lockdownbrowser_sett');
    if (count($records) > 0) {
        foreach ($records as $settings) {
            if ($DB->record_exists('quiz', array('id' => $settings->quizid)) === false) {
                try {
                    lockdownbrowser_delete_options($settings->quizid);
                } catch (Exception $ex) {
                    // ignore possible multi-session conflicts
                }
            }
        }
    }
}

function lockdownbrowser_get_settings($quizid) {

    $existing = lockdownbrowser_get_quiz_options($quizid);
    if ($existing) {
        return $existing;
    } else {
        $ldbopt           = new stdClass;
        $ldbopt->attempts = 0;
        $ldbopt->reviews  = 0;
        $ldbopt->password = "";
        $ldbopt->monitor  = "";
        return $ldbopt;
    }
}

function lockdownbrowser_browser_standard_detected() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_id_cookie)
      || strlen($CFG->block_lockdownbrowser_ldb_id_cookie) == 0
      ) {
        print_error("errstandardclientidname", "block_lockdownbrowser");
    }
    $clientid_cookie_name = $CFG->block_lockdownbrowser_ldb_id_cookie;

    if (!isset($_COOKIE[$clientid_cookie_name])) {
        return false;
    }
    $client_cookie_value = $_COOKIE[$clientid_cookie_name];

    if (strcmp($client_cookie_value, "y") !== 0) {
        return false;
    }
    return true;
}

function lockdownbrowser_browser_sdk2015_detected() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_clientid_cookie)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_clientid_cookie) == 0
      ) {
        print_error("errsdk2015clientidname", "block_lockdownbrowser");
    }
    $clientid_cookie_name = $CFG->block_lockdownbrowser_ldb_sdk2015_clientid_cookie;

    if (!isset($_COOKIE[$clientid_cookie_name])) {
        return false;
    }
    $client_cookie_value = $_COOKIE[$clientid_cookie_name];

    if (strcmp($client_cookie_value, "1") !== 0) {
        return false;
    }
    return true;
}

function lockdownbrowser_check_for_lock($quizobj, $prevent_launch) {

    // called from quizaccess_lockdownbrowser::prevent_access();
    // $quizobj is an object of class quiz declared in /mod/quiz/attemptlib.php;
    // not currently using $quizobj here, but it might already contain some of the information we determine below;
    // $prevent_launch is a boolean override indicating whether quiz launch should be prevented even if access is allowed;
    // returns false if access should be allowed;
    // returns an html string if access should be prevented
    global $CFG, $DB;

    $id      = optional_param('id', 0, PARAM_INT); // Course Module ID
    $q       = optional_param('q', 0, PARAM_INT); // or quiz ID
    $attempt = optional_param('attempt', 0, PARAM_INT); // A particular attempt ID for review
    $cmid      = optional_param('cmid', 0, PARAM_INT); // Some pages use this for course Module ID

    $script = substr(strrchr($_SERVER['SCRIPT_NAME'], "/"), 1);
    $discriminator = "";
    if ($attempt) {
        $discriminator = $attempt;
    } else if ($q) {
        $discriminator = $q;
    } else if ($id) {
        $discriminator = $id;
    } else if ($cmid) {
        $discriminator = $cmid;
    }

    if (isset($_SESSION['LOCKDOWNBROWSER_CONTEXT'])) {
        if ($script . "NONE" . $discriminator == $_SESSION['LOCKDOWNBROWSER_CONTEXT']
          || $script . "VALID" . $discriminator == $_SESSION['LOCKDOWNBROWSER_CONTEXT']
          ) {
            return false;
        }
    }

    if ($id) {

        if (!$cm = get_coursemodule_from_id('quiz', $id)) {
            print_error("errcmid", "block_lockdownbrowser", "", $id);
        }
        if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
            print_error("errcourse", "block_lockdownbrowser");
        }
        if (!$quiz = $DB->get_record("quiz", array("id" => $cm->instance))) {
            print_error("errnoquiz1", "block_lockdownbrowser", "", $cm);
        }
    } else if ($cmid) {

        if (!$cm = get_coursemodule_from_id('quiz', $cmid)) {
            print_error("errcmid", "block_lockdownbrowser", "", $cmid);
        }
        if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
            print_error("errcourse", "block_lockdownbrowser");
        }
        if (!$quiz = $DB->get_record("quiz", array("id" => $cm->instance))) {
            print_error("errnoquiz1", "block_lockdownbrowser", "", $cm);
        }
    } else if ($q) {

        if (!$quiz = $DB->get_record("quiz", array("id" => $q))) {
            print_error("errquizid", "block_lockdownbrowser", "", $q);
        }
        if (!$course = $DB->get_record("course", array("id" => $quiz->course))) {
            print_error("errnocourse", "block_lockdownbrowser", "", $quiz);
        }
        if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
            print_error("errnocm", "block_lockdownbrowser", "", $q);
        }
    } else if ($attempt) {

        if (!$attempt = $DB->get_record("quiz_attempts", array("id" => $attempt))) {
            print_error("errattempt", "block_lockdownbrowser");
        }
        if (!$quiz = $DB->get_record("quiz", array("id" => $attempt->quiz))) {
            print_error("errnoquiz2", "block_lockdownbrowser", "", $attempt);
        }
        if (!$course = $DB->get_record("course", array("id" => $quiz->course))) {
            print_error("errnocourse", "block_lockdownbrowser", "", $quiz);
        }
        if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
            print_error("errnocm", "block_lockdownbrowser", "", $quiz->id);
        }
    } else {

        echo "<div style='font-size: 150%; color: red'>Cannot get quiz from: " . me() . "</div>";
        die;
    }

    $ldbopt = lockdownbrowser_get_quiz_options($quiz->id);

    if (!$ldbopt) {

        $_SESSION['LOCKDOWNBROWSER_CONTEXT'] = $script . "NONE" . $discriminator;
        return false;

    } else {

        if (bccomp($CFG->version, 2013111800, 2) >= 0) {
            // Moodle 2.6.0+.
            $context = context_module::instance($cm->id);
        } else {
            // Prior to Moodle 2.6.0.
            $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        }

        if (has_capability('mod/quiz:manage', $context)
          || has_capability('mod/quiz:grade', $context) // Trac #3595
          || !has_capability('mod/quiz:view', $context)
          || !has_capability('mod/quiz:attempt', $context)
          ) {
            // May want to change this - see Trac #2341
            $_SESSION['LOCKDOWNBROWSER_CONTEXT'] = $script . "NONE" . $discriminator;
            return false;

        } else { // student

            $ok      = true;
            $myerror = get_string('errunknown', 'block_lockdownbrowser');

            $lockdownbrowser_session_cookie = lockdownbrowser_get_session_cookie();
            if ($lockdownbrowser_session_cookie === false) {
                $errmsg = get_string('errinvalidsession', 'block_lockdownbrowser');
                $myerror = "<div style='font-size: 150%; color:red; text-align: center; padding: 30px'>$errmsg</div>";
                $ok = false;
            }

            if ($ok) {

                if (lockdownbrowser_browser_sdk2015_detected()) {

                    if ($_SESSION['lockdownbrowser_sdk2015_session']['active'] === true) {

                        if (strcmp($script, "view.php") === 0) {
                            $myerror = lockdownbrowser_prevent_access_fragment_for_quiz($quiz, 2, $prevent_launch); // LDB-exit fragment
                            $ok = false;
                        } else if (strcmp($script, "attempt.php") === 0) {
                            // continue to challenge/response below
                        } else if (strcmp($script, "summary.php") === 0) {
                            // continue to challenge/response below
                        } else if (strcmp($script, "startattempt.php") === 0) {
                            // continue to challenge/response below
                        } else { // unexpected script
                            // continue to challenge/response below
                        }
                    } else { // sdk2015 session not active on server
                        $myerror = "<div style='font-size: 150%; color:red; text-align: center; padding: 30px'>" .
                          get_string('errldbsessionnotactive', 'block_lockdownbrowser') . "</div>";
                        $ok = false;
                    }
                } else if (lockdownbrowser_browser_standard_detected()) {

                    // manual launch session
                    $myerror = "<div style='font-size: 150%; color:red; text-align: center; padding: 30px'>" .
                      get_string('nomanuallaunch', 'block_lockdownbrowser') . "</div>";
                    $ok = false;

                } else { // LDB not detected

                    if (strcmp($script, "view.php") === 0) {
                        $myerror = lockdownbrowser_prevent_access_fragment_for_quiz($quiz, 1, $prevent_launch); // LDB-autolaunch fragment
                    } else if (strcmp($script, "attempt.php") === 0) {
                        $myerror = lockdownbrowser_prevent_access_fragment_for_quiz($quiz, 0, $prevent_launch); // LDB-required fragment
                    } else if (strcmp($script, "summary.php") === 0) {
                        $myerror = lockdownbrowser_prevent_access_fragment_for_quiz($quiz, 0, $prevent_launch); // LDB-required fragment
                    } else if (strcmp($script, "startattempt.php") === 0) {
                        $myerror = lockdownbrowser_prevent_access_fragment_for_quiz($quiz, 0, $prevent_launch); // LDB-required fragment
                    } else { // unexpected script
                        $myerror = lockdownbrowser_prevent_access_fragment_for_quiz($quiz, 0, $prevent_launch); // LDB-required fragment
                    }
                    $ok = false;
                }
            }

            if ($ok) {

                if (!lockdownbrowser_prior_challenge_response()) {

                    if (!lockdownbrowser_validate_client_response()) {
                        $errmsg = get_string('errchallengeresponse', 'block_lockdownbrowser');
                        $myerror = "<div style='font-size: 150%; color:red; text-align: center; padding: 30px'>$errmsg</div>";
                        $ok = false;
                    }
                }
            }

            if ($ok) {

                $_SESSION['LOCKDOWNBROWSER_CONTEXT'] = $script . "VALID" . $discriminator;
                return false;

            } else {

                $_SESSION['LOCKDOWNBROWSER_CONTEXT'] = $script . "INVALID" . $discriminator;
                return $myerror;
            }
        }
    }
}

function lockdownbrowser_prevent_access_fragment_for_quiz($quiz, $fragment_type, $prevent_launch) {

    // $fragment_type
    //   0 = LDB-required fragment
    //   1 = LDB-autolaunch fragment
    //   2 = LDB-exit fragment
    // $prevent_launch
    //   true = prevent quiz launch

    global $CFG;

    if ($fragment_type === 0    // LDB-required fragment
       || $fragment_type === 1    // LDB-autolaunch fragment
      ) {
        $ldbopt = lockdownbrowser_get_quiz_options($quiz->id);
        if (!$ldbopt) {
            return false;
        }
        if (isset($ldbopt->monitor)
          && !is_null($ldbopt->monitor)
          && strlen($ldbopt->monitor) > 0
          ) {
            /*** Trac #4594
            $ldb_message = get_string('monitor_required', 'block_lockdownbrowser');
            $ldb_quiz_type = lockdownbrowser_get_ldb_examtype_monitor();
            ***/
            $parts = explode("\n ", $ldbopt->monitor, 2);
            $nvpair = explode("\$%@%\$", $parts[0], 2);
            if (count($nvpair) == 2 && $nvpair[0] == "monitorEnabled" && $nvpair[1] == "false") {
                $ldb_message = get_string('ldb_required', 'block_lockdownbrowser');
                $ldb_quiz_type = lockdownbrowser_get_ldb_examtype_ldbonly();
            } else { // assume Monitor is enabled
                $ldb_message = get_string('monitor_required', 'block_lockdownbrowser');
                $ldb_quiz_type = lockdownbrowser_get_ldb_examtype_monitor();
            }
        } else { // assume Monitor is not enabled
            $ldb_message = get_string('ldb_required', 'block_lockdownbrowser');
            $ldb_quiz_type = lockdownbrowser_get_ldb_examtype_ldbonly();
        }
    }
    if ($fragment_type === 2) { // LDB-exit fragment
        $ldb_message = get_string('sessioninprogress', 'block_lockdownbrowser');
    }
    // CSS style section
    $style = "<link rel=\"stylesheet\" type=\"text/css\" href=\"//fonts.googleapis.com/css?family=Open+Sans\" />\r\n"
      . "<link rel=\"stylesheet\" type=\"text/css\" href=\"//fonts.googleapis.com/css?family=Raleway\" />\r\n"
      . "<style type=\"text/css\">\r\n"
      . ".ldb-message-text {\r\n"
      . "  font-family: \"Open Sans\", Helvetica, sans-serif;\r\n"
      . "  font-size: 18px;\r\n"
      . "  font-weight: normal;\r\n"
      . "  font-style: normal;\r\n"
      . "  font-stretch: normal;\r\n"
      . "  line-height: normal;\r\n"
      . "  letter-spacing: normal;\r\n"
      . "  text-align: center;\r\n"
      . "  color: #c10e24;\r\n"
      . "  margin-bottom: 25px;\r\n"
      . "}\r\n";

    if ($fragment_type === 0    // LDB-required fragment
       || $fragment_type === 1    // LDB-autolaunch fragment
      ) {
        $style .= ".ldb-links {\r\n"
          . "  font-family: \"Open Sans\", Helvetica, sans-serif;\r\n"
          . "  font-size: 14px;\r\n"
          . "  font-weight: normal;\r\n"
          . "  font-style: normal;\r\n"
          . "  font-stretch: normal;\r\n"
          . "  line-height: normal;\r\n"
          . "  letter-spacing: normal;\r\n"
          . "  text-align: center;\r\n"
          . "  color: #387da6;\r\n"
          . "  margin-bottom: 36px;\r\n"
          . "}\r\n"
          . "div.ldb-links a {\r\n"
          . "  color: #387da6;\r\n"
          . "}\r\n";
    }
    $style .= ".ldb-button {\r\n"
      . "  width: 222px;\r\n"
      . "  height: 40px;\r\n"
      . "  object-fit: contain;\r\n"
      . "  border-radius: 10px;\r\n"
      . "  background-color: #387da6;\r\n"
      . "  margin-left: auto;\r\n"
      . "  margin-right: auto;\r\n"
      . "  margin-bottom: 36px;\r\n"
      . "  cursor: pointer;\r\n"
      . "}\r\n"
      . ":hover.ldb-button {\r\n"
      . "  background-color: #569FCA;\r\n"
      . "}\r\n"
      . ".ldb-button-text {\r\n"
      . "  object-fit: contain;\r\n"
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
      . "</style>\r\n";
    $fragment = $style;

    // LDB message
    $fragment .= "<div class=\"ldb-message-text\">$ldb_message</div>\r\n";

    // LDB links
    /*** Trac #5122
    $is_ipad = (isset($_SERVER['HTTP_USER_AGENT'])
       && stripos($_SERVER['HTTP_USER_AGENT'], "iPad") !== false);
    ***/
    if ($fragment_type === 0     // LDB-required fragment
      || $fragment_type === 1    // LDB-autolaunch fragment
      ) {
        $ldb_links = "";
        $download_url = $CFG->block_lockdownbrowser_ldb_download;

        if ($fragment_type === 0) { // LDB-required fragment
            if (empty($download_url)) {
                $ldb_links .= get_string('ldb_download_disabled', 'block_lockdownbrowser');
            } else {
                $ldb_links .= "<a href=\"$download_url\" target=\"_blank\">"
                  . get_string('ldbdownlink', 'block_lockdownbrowser')
                  . "</a>\r\n";
            }
        }
        if ($fragment_type === 1) { // LDB-autolaunch fragment
            if (!empty($download_url)) {
                $ldb_links .= "<a href=\"$download_url\" target=\"_blank\">"
                  . get_string('ldbdownlink', 'block_lockdownbrowser')
                  . "</a>\r\n";
            }
            /*** Trac #5122
            if (!empty($download_url) && $is_ipad === false) {
                $ldb_links .= "&nbsp;&nbsp;|&nbsp;&nbsp;\r\n";
            }
            if ($is_ipad === false) {
                $ldb_check_url = lockdownbrowser_autolaunch_check_url($ldb_quiz_type);
                $ldb_links .= "<a href=\"$ldb_check_url\">"
                  . get_string('ldbchecklink', 'block_lockdownbrowser')
                  . "</a>\r\n";
            }
            ***/
            $ldb_links .= "<script>\r\n"
              . "if (navigator.platform == \"iPad\" || (navigator.platform == \"MacIntel\" && navigator.maxTouchPoints > 1)) {\r\n"
              . "} else {\r\n";
            if (!empty($download_url)) {
              $ldb_links .= "    document.write(\"&nbsp;&nbsp;|&nbsp;&nbsp;\\r\\n\");\r\n";
            }
            $ldb_check_url = lockdownbrowser_autolaunch_check_url($ldb_quiz_type);
            $ldb_links .= "    document.write(\"<a href=\\\"$ldb_check_url\\\">"
              . get_string('ldbchecklink', 'block_lockdownbrowser')
              . "</a>\\r\\n\");\r\n"
              . "}\r\n"
              . "</script>\r\n";
        }
        if (strlen($ldb_links) > 0) {
            $fragment .= "<div class=\"ldb-links\">$ldb_links</div>\r\n";
        }
    }

    // LDB button
    if (!$prevent_launch && $fragment_type === 1) { // LDB-autolaunch fragment
        $launch_url = lockdownbrowser_autolaunch_url_for_quiz($quiz, $ldb_quiz_type);
        $script = "<script>\r\n";
        /*** Trac #5122
        if ($is_ipad === true) {
            // avoid the servicenotavailable error
            $login_url = get_login_url();
            $session_cookie_name = $CFG->block_lockdownbrowser_ldb_session_cookie . $CFG->sessioncookie;
            $script .= "if (document.cookie.indexOf(\"$session_cookie_name=\") == -1) {\r\n"
              . "    document.location = \"$login_url\";\r\n"
              . "}\r\n";
        }
        ***/
        if ($CFG->cookiehttponly) { // eSupport LLL-340-19699
            // do nothing;
            // the Moodle session cookie will not be available to javascript,
            //   so our fix for the servicenotavailable error will not work;
            // sites that select "Only http cookies" under
            //   Site administration->Security->HTTP security will have to live
            //   with the error, since it only occurs after the exam
        } else {
            // avoid the servicenotavailable error
            $login_url = get_login_url();
            $session_cookie_name = $CFG->block_lockdownbrowser_ldb_session_cookie . $CFG->sessioncookie;
            $script .= "if (navigator.platform == \"iPad\" || (navigator.platform == \"MacIntel\" && navigator.maxTouchPoints > 1)) {\r\n"
              . "    if (document.cookie.indexOf(\"$session_cookie_name=\") == -1) {\r\n"
              . "        document.location = \"$login_url\";\r\n"
              . "    }\r\n"
              . "}\r\n";
        }
        $script .= "function ldbAutoLaunch() {\r\n"
          . "    document.location = \"$launch_url\";\r\n"
          . "}\r\n"
          . "</script>\r\n";
        $fragment .= $script;
        $launch_button_title = get_string('autolaunchbutton', 'block_lockdownbrowser');
        $fragment .= "<div class=\"ldb-button\" onclick=\"ldbAutoLaunch();\">\r\n"
          . "<div class=\"ldb-button-text\">$launch_button_title</div>\r\n"
          . "</div>\r\n";
    }
    if ($fragment_type === 2) { // LDB-exit fragment
        $sdk2015_command_scheme = lockdownbrowser_get_sdk2015_command_scheme();
        $sdk2015_command_exitb = lockdownbrowser_get_sdk2015_command_exitb();
        $sdk2015_exitb_value = "1";
        $exit_url = $sdk2015_command_scheme
          . ":" . urlencode($sdk2015_command_exitb)
          . "=" . urlencode($sdk2015_exitb_value);
        $script = "<script>\r\n"
          . "function exitBrowser() {\r\n"
          . "    document.location = '$exit_url';\r\n"
          . "}\r\n"
          . "</script>\r\n";
        $fragment .= $script;
        $exit_button_title = get_string('exitbrowserbutton', 'block_lockdownbrowser');
        $fragment .= "<div class=\"ldb-button\" onclick=\"exitBrowser();\">\r\n"
          . "<div class=\"ldb-button-text\">$exit_button_title</div>\r\n"
          . "</div>\r\n";
    }
    return $fragment;
}

function lockdownbrowser_get_ldb_examtype_ldbonly() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_examtype_ldbonly)
      || strlen($CFG->block_lockdownbrowser_ldb_examtype_ldbonly) == 0
      ) {
        print_error("errldbexamtypeldbonly", "block_lockdownbrowser");
    }
    $examtype_ldbonly = $CFG->block_lockdownbrowser_ldb_examtype_ldbonly;

    return $examtype_ldbonly;
}

function lockdownbrowser_get_ldb_examtype_monitor() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_examtype_monitor)
      || strlen($CFG->block_lockdownbrowser_ldb_examtype_monitor) == 0
      ) {
        print_error("errldbexamtypemonitor", "block_lockdownbrowser");
    }
    $examtype_monitor = $CFG->block_lockdownbrowser_ldb_examtype_monitor;

    return $examtype_monitor;
}

function lockdownbrowser_get_sdk2015_ldbonly_check() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_ldbonly_check)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_ldbonly_check) == 0
      ) {
        print_error("errsdk2015ldbonlycheck", "block_lockdownbrowser");
    }
    $ldbonly_check = $CFG->block_lockdownbrowser_ldb_sdk2015_ldbonly_check;

    return $ldbonly_check;
}

function lockdownbrowser_get_sdk2015_monitor_check() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_monitor_check)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_monitor_check) == 0
      ) {
        print_error("errsdk2015monitorcheck", "block_lockdownbrowser");
    }
    $monitor_check = $CFG->block_lockdownbrowser_ldb_sdk2015_monitor_check;

    return $monitor_check;
}

function lockdownbrowser_get_sdk2015_prestart_fn() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_prestart_fn)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_prestart_fn) == 0
      ) {
        print_error("errsdk2015prestartfn", "block_lockdownbrowser");
    }
    $prestart_fn = $CFG->block_lockdownbrowser_ldb_sdk2015_prestart_fn;

    return $prestart_fn;
}

function lockdownbrowser_get_sdk2015_ldbcheck_parm() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_ldbcheck_parm)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_ldbcheck_parm) == 0
      ) {
        print_error("errsdk2015ldbcheckparm", "block_lockdownbrowser");
    }
    $ldbcheck_parm = $CFG->block_lockdownbrowser_ldb_sdk2015_ldbcheck_parm;

    return $ldbcheck_parm;
}

function lockdownbrowser_get_sdk2015_session_parm() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_session_parm)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_session_parm) == 0
      ) {
        print_error("errsdk2015sessionparm", "block_lockdownbrowser");
    }
    $sdk2015_session_parm = $CFG->block_lockdownbrowser_ldb_sdk2015_session_parm;

    return $sdk2015_session_parm;
}

function lockdownbrowser_get_sdk2015_examid_parm() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_examid_parm)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_examid_parm) == 0
      ) {
        print_error("errsdk2015examidparm", "block_lockdownbrowser");
    }
    $sdk2015_examid_parm = $CFG->block_lockdownbrowser_ldb_sdk2015_examid_parm;

    return $sdk2015_examid_parm;
}

function lockdownbrowser_get_sdk2015_command_scheme() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_command_scheme)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_command_scheme) == 0
      ) {
        print_error("errsdk2015commandscheme", "block_lockdownbrowser");
    }
    $sdk2015_command_scheme = $CFG->block_lockdownbrowser_ldb_sdk2015_command_scheme;

    return $sdk2015_command_scheme;
}

function lockdownbrowser_get_sdk2015_command_exitb() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_command_exitb)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_command_exitb) == 0
      ) {
        print_error("errsdk2015commandexitb", "block_lockdownbrowser");
    }
    $sdk2015_command_exitb = $CFG->block_lockdownbrowser_ldb_sdk2015_command_exitb;

    return $sdk2015_command_exitb;
}

function lockdownbrowser_get_challenge_name() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_challenge_cookie)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_challenge_cookie) == 0
      ) {
        print_error("errchallengecookiename", "block_lockdownbrowser");
    }
    $challenge_name = $CFG->block_lockdownbrowser_ldb_sdk2015_challenge_cookie;

    return $challenge_name;
}

// Trac #5382
function lockdownbrowser_get_institution_id() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_serverid)
      || strlen($CFG->block_lockdownbrowser_ldb_serverid) == 0
      ) {
        print_error("errinstitutionid", "block_lockdownbrowser");
    }
    $inst_id = $CFG->block_lockdownbrowser_ldb_serverid;

    return $inst_id;
}

function lockdownbrowser_get_profile_secret() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_serversecret)
      || strlen($CFG->block_lockdownbrowser_ldb_serversecret) == 0
      ) {
        print_error("errprofilesecret", "block_lockdownbrowser");
    }
    $profile_secret = $CFG->block_lockdownbrowser_ldb_serversecret;

    return $profile_secret;
}

function lockdownbrowser_get_sdk2015_secret1() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_secret1)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_secret1) == 0
      ) {
        print_error("errsdk2015secret1", "block_lockdownbrowser");
    }
    $sdk2015_secret1 = $CFG->block_lockdownbrowser_ldb_sdk2015_secret1;

    return $sdk2015_secret1;
}

function lockdownbrowser_get_sdk2015_index() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_index)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_index) == 0
      ) {
        print_error("errsdk2015index", "block_lockdownbrowser");
    }
    $sdk2015_index = $CFG->block_lockdownbrowser_ldb_sdk2015_index;

    return $sdk2015_index;
}

function lockdownbrowser_get_sdk2015_secret2() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_secret2)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_secret2) == 0
      ) {
        print_error("errsdk2015secret2", "block_lockdownbrowser");
    }
    $sdk2015_secret2 = $CFG->block_lockdownbrowser_ldb_sdk2015_secret2;

    return $sdk2015_secret2;
}

function lockdownbrowser_create_server_challenge() {

    // session not yet available when invoked from autolaunch.php

    $profile_secret = lockdownbrowser_get_profile_secret();
    $random_key = random_string(15);
    $timestamp = time();

    $material = md5($profile_secret . $random_key . $timestamp);

    $sdk2015_secret1 = lockdownbrowser_get_sdk2015_secret1();
    $sdk2015_index = lockdownbrowser_get_sdk2015_index();

    $hash = md5($sdk2015_index . $material . $sdk2015_secret1);

    $challenge = "$material-$sdk2015_index$hash";

    return $challenge;
}

function lockdownbrowser_get_server_challenge() {

    if (!isset($_SESSION['lockdownbrowser_sdk2015_session']['challenge'])
      || strlen($_SESSION['lockdownbrowser_sdk2015_session']['challenge']) == 0
      ) {
        $challenge = lockdownbrowser_create_server_challenge();
        $_SESSION['lockdownbrowser_sdk2015_session']['challenge'] = $challenge;
        unset($_SESSION['lockdownbrowser_sdk2015_session']['response']);
    } else {
        $challenge = $_SESSION['lockdownbrowser_sdk2015_session']['challenge'];
    }
    return $challenge;
}

function lockdownbrowser_get_client_response() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_response_cookie)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_response_cookie) == 0
      ) {
        print_error("errresponsecookiename", "block_lockdownbrowser");
    }
    $response_cookie_name = $CFG->block_lockdownbrowser_ldb_sdk2015_response_cookie;
    if (!isset($_COOKIE[$response_cookie_name])) {
        return false;
    }
    $response_cookie_value = $_COOKIE[$response_cookie_name];

    return $response_cookie_value;
}

function lockdownbrowser_prior_challenge_response() {

    $response = lockdownbrowser_get_client_response();
    if ($response === false) {
        return false;
    }
    if (!isset($_SESSION['lockdownbrowser_sdk2015_session']['response'])
      || strlen($_SESSION['lockdownbrowser_sdk2015_session']['response']) == 0) {
        return false;
    }
    if (strcmp($response, $_SESSION['lockdownbrowser_sdk2015_session']['response']) !== 0) {
        return false;
    }
    return true;
}

function lockdownbrowser_validate_client_response() {

    unset($_SESSION['lockdownbrowser_sdk2015_session']['response']);

    $response = lockdownbrowser_get_client_response();
    if ($response === false) {
        return false;
    }
    $response_parts = explode("-", $response);
    if (count($response_parts) != 2){
        return false;
    }
    $challenge = lockdownbrowser_get_server_challenge();
    $challenge_parts = explode("-", $challenge);

    $response_material = $response_parts[0];
    $challenge_material = $challenge_parts[0];
    if (strcmp($response_material, $challenge_material) !== 0) {
        return false;
    }
    if (strlen($response_parts[1]) != 34) { // index(2) + hash(32)
        return false;
    }
    $response_index = substr($response_parts[1], 0, 2);
    $challenge_index = substr($challenge_parts[1], 0, 2);
    if (strcmp($response_index, $challenge_index) !== 0) {
        return false;
    }
    $profile_secret = lockdownbrowser_get_profile_secret();
    $sdk2015_secret2 = lockdownbrowser_get_sdk2015_secret2();

    $response_hash = substr($response_parts[1], 2);
    $validate_hash = md5($challenge_index . $sdk2015_secret2 . $challenge_material . $profile_secret);
    if (strcmp($response_hash, $validate_hash) !== 0) {
        return false;
    }
    $_SESSION['lockdownbrowser_sdk2015_session']['response'] = $response;

    return true;
}

function lockdownbrowser_autolaunch_check_url($ldb_quiz_type) {

    global $CFG;

    $examtype_ldbonly = lockdownbrowser_get_ldb_examtype_ldbonly();
    $examtype_monitor = lockdownbrowser_get_ldb_examtype_monitor();

    if (strcmp($ldb_quiz_type, $examtype_monitor) === 0) {
        $ldbcheck_value = $examtype_monitor;
    } else if (strcmp($ldb_quiz_type, $examtype_ldbonly) === 0) {
        $ldbcheck_value = $examtype_ldbonly;
    } else {
        print_error("errinvalidldbquiztype", "block_lockdownbrowser");
    }
    $ldbcheck_parm = lockdownbrowser_get_sdk2015_ldbcheck_parm();

    $restart_url = $CFG->wwwroot . "/blocks/lockdownbrowser/autolaunch.php"
      . "?" . urlencode($ldbcheck_parm) . "=" . urlencode($ldbcheck_value);

    $quiz_title = get_string('ldbchecklink', 'block_lockdownbrowser');

    // Trac #5382
    $inst_id = lockdownbrowser_get_institution_id();

    $xml_payload = "<z>"
      . "<u>" . htmlspecialchars($restart_url, ENT_XML1, 'UTF-8') . "</u>"
      . "<ci>1234</ci>"
      . "<xi>4321</xi>"
      . "<si>username</si>"
      . "<sf>Firstname</sf>"
      . "<sl>Lastname</sl>"
      . "<tl>" . htmlspecialchars($quiz_title, ENT_XML1, 'UTF-8') . "</tl>"
      // Trac #5382
      . "<inst>" . htmlspecialchars($inst_id, ENT_XML1, 'UTF-8') . "</inst>"
      . "</z>";

    $url = lockdownbrowser_autolaunch_url_for_payload($xml_payload);

    return $url;
}

function lockdownbrowser_get_session_cookie() {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_session_cookie)
      || strlen($CFG->block_lockdownbrowser_ldb_session_cookie) == 0
      ) {
        print_error("errsessioncookiename", "block_lockdownbrowser");
    }
    $session_cookie_name = $CFG->block_lockdownbrowser_ldb_session_cookie . $CFG->sessioncookie;

    if (!isset($_COOKIE[$session_cookie_name])) {
        return false;
    }
    $session_cookie_value = $_COOKIE[$session_cookie_name];

    return $session_cookie_value;
}

function lockdownbrowser_autolaunch_url_for_quiz($quiz, $ldb_quiz_type) {

    global $DB;
    global $CFG;
    global $USER;

    $session_parm = lockdownbrowser_get_sdk2015_session_parm();
    $session_cookie = lockdownbrowser_get_session_cookie();
    if ($session_cookie === false) {
        print_error("errinvalidsession", "block_lockdownbrowser");
    }
    // exam id is coursmodule id, not quiz id
    $examid_parm = lockdownbrowser_get_sdk2015_examid_parm();
    $cm = get_coursemodule_from_instance("quiz", $quiz->id, $quiz->course);
    if (!$cm) {
        print_error("errnocm", "block_lockdownbrowser", "", $quiz->id);
    }
    $exam_id = $cm->id;

    $restart_url = $CFG->wwwroot . "/blocks/lockdownbrowser/autolaunch.php"
      . "?" . urlencode($session_parm) . "=" . urlencode($session_cookie)
      . "&" . urlencode($examid_parm) . "=" . urlencode($exam_id);

    $course_id = $quiz->course;

    $user_name = $USER->username;
    $first_name = $USER->firstname;
    $last_name = $USER->lastname;

    $quiz_title = $quiz->name;

    // Trac #5382
    $inst_id = lockdownbrowser_get_institution_id();

    $xml_payload = "<z>"
      . "<u>" . htmlspecialchars($restart_url, ENT_XML1, 'UTF-8') . "</u>"
      . "<ci>" . htmlspecialchars($course_id, ENT_XML1, 'UTF-8') . "</ci>"
      . "<xi>" . htmlspecialchars($exam_id, ENT_XML1, 'UTF-8') . "</xi>"
      . "<si>" . htmlspecialchars($user_name, ENT_XML1, 'UTF-8') . "</si>"
      . "<sf>" . htmlspecialchars($first_name, ENT_XML1, 'UTF-8') . "</sf>"
      . "<sl>" . htmlspecialchars($last_name, ENT_XML1, 'UTF-8') . "</sl>"
      . "<tl>" . htmlspecialchars($quiz_title, ENT_XML1, 'UTF-8') . "</tl>";

    if (LOCKDOWNBROWSER_LOCKLIB_ENABLE_4851) {
        $examtype_monitor = lockdownbrowser_get_ldb_examtype_monitor();
        if (strcmp($ldb_quiz_type, $examtype_monitor) === 0) {
            $xml_payload .= "<rm>1</rm>";
        }
    }
    // Trac #5382
    $xml_payload .= "<inst>" . htmlspecialchars($inst_id, ENT_XML1, 'UTF-8') . "</inst>";

    $xml_payload .= "</z>";

    $url = lockdownbrowser_autolaunch_url_for_payload($xml_payload);

    return $url;
}

function lockdownbrowser_autolaunch_url_for_payload($payload) {

    global $CFG;

    $sdk2015_secret1 = lockdownbrowser_get_sdk2015_secret1();

    $encrypted_payload = Blowfish::encrypt($payload, $sdk2015_secret1,
      Blowfish::BLOWFISH_MODE_ECB, Blowfish::BLOWFISH_PADDING_ZERO);

    $base64_payload = base64_encode($encrypted_payload);

    if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_launch_scheme)
      || strlen($CFG->block_lockdownbrowser_ldb_sdk2015_launch_scheme) == 0
      ) {
        print_error("errsdk2015launchscheme", "block_lockdownbrowser");
    }
    $sdk2015_scheme = $CFG->block_lockdownbrowser_ldb_sdk2015_launch_scheme;
    $sdk2015_index = lockdownbrowser_get_sdk2015_index();

    $url = "$sdk2015_scheme:$sdk2015_index%7B$base64_payload%7D";

    return $url;
}

function lockdownbrowser_check_plugin_dependencies($resultstyle) {

    // return false if dependencies are valid, or:
    // resultstyle = 0, return an error string
    // resultstyle = 1, return an error object
    // resultstyle = 2, throw an exception
    global $CFG;

    $blockversion = lockdownbrowser_get_block_version();
    $ruleversion = lockdownbrowser_get_rule_version();

    if ($blockversion === false) {
        $identifier = "noblockversion";
    } else if ($ruleversion === false) {
        $identifier = "noruleversion";
    } else if (lockdownbrowser_compare_plugin_versions($ruleversion, $blockversion) === false) {
        $identifier = "invalidversion";
    } else {
        return false; // dependencies are valid
    }
    $component = "block_lockdownbrowser";

    if ($resultstyle == 1
      && $CFG->version >= 2012062500 // Moodle 2.3.0+.
      ) {
        return new lang_string($identifier, $component);
    } else if ($resultstyle == 2) {
        print_error($identifier, $component);
    } else {
        return get_string($identifier, $component);
    }
}

function lockdownbrowser_get_rule_version() {

    global $CFG;

    $plugin = new stdClass;
    $version_file = $CFG->dirroot . '/mod/quiz/accessrule/lockdownbrowser/version.php';
    if (is_readable($version_file)) {
        include($version_file);
    }
    $version = false;
    if (isset($plugin)) {
        if (!empty($plugin->version)) {
            $version = $plugin->version;
        }
    }
    return $version;
}

function lockdownbrowser_get_block_version() {

    global $CFG;

    $plugin = new stdClass;
    $version_file = $CFG->dirroot . '/blocks/lockdownbrowser/version.php';
    if (is_readable($version_file)) {
        include($version_file);
    }
    $version = false;
    if (isset($plugin)) {
        if (!empty($plugin->version)) {
            $version = $plugin->version;
        }
    }
    return $version;
}

function lockdownbrowser_compare_plugin_versions($ruleversion, $blockversion) {

    // return true if the specified plugin versions are considered equal for
    // the purposes of dependency checking, else return false
    $comparelength = 8; // yyyymmddxx, but only consider first 8 digits
    if (strlen($ruleversion) < $comparelength
      || strlen($blockversion) < $comparelength ) {
        return false;
    }
    if(substr($ruleversion, 0, $comparelength)
      == substr($blockversion, 0, $comparelength)) {
        return true;
    } else {
        return false;
    }
}

function lockdownbrowser_lockliblog($msg) {

    global $CFG;

    if (LOCKDOWNBROWSER_LOCKLIB_ENABLE_LOG) {
        $entry  = date("m-d-Y H:i:s") . " - " . $msg . "\r\n";
        if (isset($CFG->tempdir)) {
            $path = "$CFG->tempdir";
        } else {
            $path = "$CFG->dataroot/temp";
        }
        $path .= "/" . LOCKDOWNBROWSER_LOCKLIB_LOG;
        $handle = fopen($path, "ab");
        if ($handle !== false) {
            fwrite($handle, $entry, strlen($entry));
            fclose($handle);
        }
    }
}
