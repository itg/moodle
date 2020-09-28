<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

// English (en) translations

$string['pluginname']      = 'Respondus LockDown Browser';
$string['lockdownbrowser'] = 'Respondus LockDown Browser';
$string['lockdownbrowser:addinstance'] = 'Add a new lockdownbrowser block';

// admin settings page
$string["blockdescheader"]     = "Description";
$string["blockdescription"]    = "Respondus LockDown Browser Extension for Moodle";
$string["blockversionheader"]  = "Current version";
$string["adminsettingsheader"] = "Admin settings";

$string['adminsettingsheaderinfo'] =
    'The values for these settings are provided by Respondus. If you download the
    block from the Respondus Campus Portal, the settings are already included in the
    block. Please review the Administrator Guide for additional details.';

$string["adminstatus"] = "Block Status";

$string["authenticationsettingsheader"]     = "Authentication Settings";

$string["authenticationsettingsheaderinfo"] =
    "These are the credentials for the user under which the Respondus LockDown Browser
    and Respondus Monitor web services run. The user entered must be a Moodle site
    administrator listed in Site administration->Users->Permissions->Site administrators.
    This information is never transmitted outside of this Moodle server and all Respondus
    Monitor web service    requests are authenticated using Hash-based Message Authentication
    Codes. If the option \"Use HTTPS for logins\" in the Security->HTTP Security settings
    is selected, all Respondus Monitor web service requests enforce the use of HTTPS.";

$string["password"]                         = "Password";
$string["passwordinfo"]                     = "Password for the Respondus Monitor user.";
$string["username"]                         = "User name";
$string["usernameinfo"]                     = "Respondus Monitor user name.";

$string['servername'] = 'Server Name';

$string['servernameinfo'] =
    'This setting must match the name entered in the Respondus Campus Portal profile for this Moodle Server.';

$string['serverid']     = 'Server Id';
$string['serveridinfo'] = 'Institution ID for this Moodle server. Assigned by Respondus.';
$string['serversecret'] = 'Shared Secret';

$string['serversecretinfo'] =
    'This setting must match the secret entered in the Respondus Campus Portal profile for this Moodle Server.';

$string['servertype']     = 'License Type';
$string['servertypeinfo'] = 'Campus-wide = 0, Lab Pack = 1.';
$string['downloadurl']    = 'Download URL';

$string['downloadinfo'] =
    'Link for students to download browser client.  Leave blank to not display a link on attempts page.';

$string['dashboard']         = 'Dashboard';
$string['quizzes']           = 'Quizzes';
$string['lockdown_settings'] = 'LockDown Browser Settings';
$string['quiz']              = 'Quiz';
$string['disable']           = 'Disable';
$string['enable']            = 'Enable';
$string['ldb_required']      = 'Respondus LockDown Browser is required for this exam.';
$string['monitor_required']  = 'Respondus LockDown Browser with Respondus Monitor (webcam) is required for this exam.';

$string['block_status_ok'] = 'Block status is OK.';

$string['module_installed_error']  =
    "Error: /mod/lockdown module has not been uninstalled. Please see the Administrator Guide for LockDown Browser - Moodle.";

$string['session_cookie_not_set'] = 'Warning: Moodle session cookie check failed.';

$string['ldb_quiz_count'] =
    'Warning: {$a} rows in quiz settings table. This may result in slower performance for the LDB dashboard.';

$string['ldb_download_disabled'] = 'The LockDown Browser download is not enabled on this site.';
$string['iframe_error']          = 'This page requires iframes support';

$string['errcmid'] = 'There is no coursemodule with id {$a}';
$string['errcourse'] = 'Course is misconfigured';
$string['errquizid'] = 'There is no quiz with id {$a}';
$string['errnocourse'] = 'The course with id {$a->course} that the quiz with id {$a->id} belongs to is missing';
$string['errnocm'] = 'The course module for the quiz with id {$a} is missing';
$string['errattempt'] = 'No such attempt ID exists';
$string['errnoquiz1'] = 'The quiz with id {$a->instance} corresponding to this coursemodule {$a->id} is missing';
$string['errnoquiz2'] = 'The quiz with id {$a->quiz} belonging to attempt {$a->id} is missing';

$string['noblockversion'] = 'The Respondus LockDown Browser Extension for Moodle is not properly installed. The block plugin is either missing or the version cannot be determined.';
$string['noruleversion'] = 'The Respondus LockDown Browser Extension for Moodle is not properly installed. The quiz access rule plugin is either missing or the version cannot be determined.';
$string['invalidversion'] = 'The Respondus LockDown Browser Extension for Moodle is not properly installed. The block plugin version does not match the quiz access rule plugin version.';

$string['nosessionparm'] = 'No session parameter specified.';
$string['noexamidparm'] = 'No exam id parameter specified.';
$string['nochallengeparm'] = 'No challenge parameter specified.';
$string['nolmsrooturl'] = 'No LMS root URL found in Moodle configuration.';
$string['errsetsession'] = 'Failed to set session cookie.';
$string['prestartexambutton'] = 'Start Quiz';
$string['exitbrowserbutton'] = 'Exit Browser';
$string['autolaunchpagetitle'] = 'Respondus LockDown Browser';
$string['errsessionmatch'] = 'Session does not match launching browser.';
$string['errsessionkey'] = 'Failed to retrieve session key.';
$string['prestartpagetitle'] = 'Respondus LockDown Browser';
$string['prestartpagetext'] = 'Loading...';

$string['nomanuallaunch'] = "LockDown Browser shouldn't be started manually. Use a standard browser (eg. Chrome, IE, Firefox, etc.) to navigate to the exam and LockDown Browser will launch automatically when it's required.";

$string['autolaunchbutton'] = 'Launch LockDown Browser';
$string['sessioninprogress'] = 'Respondus LockDown Browser session in progress';
$string['ldbdownlink'] = 'Download LockDown Browser';
$string['ldbchecklink'] = 'Check your LockDown Browser Setup';
$string['errinvalidldbquiztype'] = 'Invalid LDB quiz type specified.';
$string['errinvalidsession'] = 'Invalid session error';

$string['errchallengeresponse'] = "You are using a version of LockDown Browser that is not supported. Please update to the most recent version and try again.";

$string['errunknown'] = 'Unknown error';
$string['errsessioncookiename'] = 'Session cookie name not configured';
$string['errchallengecookiename'] = 'Challenge cookie name not configured';
$string['errresponsecookiename'] = 'Response cookie name not configured';
$string['errprofilesecret'] = 'Server profile secret not configured';
$string['errinstitutionid'] = 'Institution ID not configured';
$string['errsdk2015secret1'] = 'Auto-launch secret 1 not configured';
$string['errsdk2015index'] = 'Auto-launch index not configured';
$string['errsdk2015secret2'] = 'Auto-launch secret 2 not configured';
$string['errsdk2015monitorcheck'] = 'Respondus Monitor check URL not configured';
$string['errsdk2015ldbonlycheck'] = 'Lockdown Browser check URL not configured';
$string['errsdk2015launchscheme'] = 'Auto-launch scheme not configured';
$string['errstandardclientidname'] = 'Standard client id cookie name not configured';
$string['errsdk2015clientidname'] = 'SDK client id cookie name not configured';
$string['errsdk2015serveridname'] = 'SDK server id cookie name not configured';
$string['errsdk2015commandscheme'] = 'SDK command scheme not configured';
$string['errsdk2015commandexitb'] = 'SDK exit browser command not configured';
$string['errsdk2015securityvhigh'] = 'SDK security command not configured';
$string['errsdk2015sessionparm'] = 'Session URL parameter not configured';
$string['errsdk2015examidparm'] = 'Exam id URL parameter not configured';
$string['errsdk2015ldbcheckparm'] = 'LDB check URL parameter not configured';
$string['errinvalidldbcheckparm'] = 'LDB check URL parameter value not recognized';
$string['errldbexamtypeldbonly'] = 'Exam type LDB-ONLY not configured';
$string['errldbexamtypemonitor'] = 'Exam type MONITOR not configured';
$string['errldbsessionnotactive'] = 'LockDown Browser session not active';
$string['errsdk2015prestartfn'] = 'SDK prestart function name not configured';
$string['errldbmonitorexitreopen'] = 'LDB Monitor exit and reopen URL not configured';

// Trac #4402
$string['privacy:metadata'] = 'The Respondus LockDown Browser Extension for Moodle block plugin does not store any personal data.';

// don't translate anything below this line

// quiz title decorations; must match what clients expect
$string['requires_ldb']      = '- Requires Respondus LockDown Browser';
$string['requires_webcam']   = '- Requires Respondus LockDown Browser + Webcam';

// quiz browser security setting option key; must match same string in LDB quiz access rule
$string['browsersecuritychoicekey'] = 'lockdownbrowser';
