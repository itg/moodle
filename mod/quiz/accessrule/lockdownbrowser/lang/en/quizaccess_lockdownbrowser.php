<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

defined('MOODLE_INTERNAL') || die();

// English (en) translations

$string['pluginname'] = 'Respondus LockDown Browser quiz access rule';
// Trac #3521
//$string['requirelockdownbrowser'] = 'Require the use of Respondus LockDown Browser';
$string['requirelockdownbrowser'] = 'Browser security should be set through the LockDown Browser Dashboard';
$string['lockdownbrowsernotice'] = 'This quiz has been configured so that students may only attempt it using the Respondus LockDown Browser.';
$string['noblockversion'] = 'The Respondus LockDown Browser Extension for Moodle is not properly installed. The block plugin is either missing or the version cannot be determined.';
$string['noruleversion'] = 'The Respondus LockDown Browser Extension for Moodle is not properly installed. The quiz access rule plugin is either missing or the version cannot be determined.';
$string['invalidversion'] = 'The Respondus LockDown Browser Extension for Moodle is not properly installed. The block plugin version does not match the quiz access rule plugin version.';
$string['errnofunc'] = 'Missing function {$a}';
$string['errdepend'] = 'Dependency error: {$a}';
$string['onesessionenabled'] = 'The Block concurrent sessions quiz access rule is enabled for this quiz.  This rule is incompatible with the Respondus LockDown Browser Extension for Moodle.  Block concurrent connections must be unchecked in the quiz settings.';
// Trac #4402
$string['privacy:metadata'] = 'The Respondus LockDown Browser Extension for Moodle quiz access rule plugin does not store any personal data.';

// don't translate anything below this line

// quiz browser security setting option key; must match same string in LDB block
$string['browsersecuritychoicekey'] = 'lockdownbrowser';
