<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

// ----- never edit these
define('LDB_SERVERNAME', 'MID+-+Moodle');
define('LDB_SERVERID', '328107539');
define('LDB_SERVERSECRET', '7j0yitOii4wtNZjV');
define('LDB_SERVERTYPE', '0');
define('LDB_DOWNLOAD', 'https://download.respondus.com/lockdown/download.php?id=328107539');
// to remove link: define('LDB_DOWNLOAD', '');

// ----- edit these only if your server is nonstandard
// editing these will break the plugin!
define('LDB_SDK2015_LAUNCH_SCHEME','rldb');
define('LDB_SDK2015_INDEX', 'co');
define('LDB_SDK2015_SECRET1', 'PMnAyok2Sa5kgNKS');
define('LDB_SDK2015_SECRET2', 'gIGHU9zh8vDwUYB9');
define('LDB_SDK2015_CHALLENGE_COOKIE', 'rldbcv');
define('LDB_SDK2015_RESPONSE_COOKIE', 'rldbrv');
define('LDB_SDK2015_CLIENTID_COOKIE', 'rldbci');
define('LDB_SDK2015_SERVERID_COOKIE', 'rldbsi');
define('LDB_SDK2015_SECURITY_VHIGH', 'rldbsv');
define('LDB_SDK2015_SECURITY_MEDIUM', 'rldbsm');
define('LDB_SDK2015_COMMAND_SCHEME','sbxcmd');
define('LDB_SDK2015_COMMAND_EXITB', 'rldbxb');
define('LDB_SDK2015_SESSION_PARM', 'session');
define('LDB_SDK2015_EXAMID_PARM', 'examid');
define('LDB_SDK2015_LDBCHECK_PARM', 'ldbcheck');
define('LDB_SDK2015_LDBONLY_CHECK', 'https://www.respondus.com/services/ldbcheck/ldbcheck.htm');
define('LDB_SDK2015_MONITOR_CHECK', 'https://www.respondus.com/webcamtest');
define('LDB_SDK2015_PRESTART_FN', 'rldb_prestart_finished');
define('LDB_ID_COOKIE', 'mldbz');
define('LDB_SESSION_COOKIE', 'MoodleSession');
define('LDB_EXAMTYPE_LDBONLY', 'ldbonly');
define('LDB_EXAMTYPE_MONITOR', 'monitor');

if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_launch_scheme)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_launch_scheme = LDB_SDK2015_LAUNCH_SCHEME;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_index)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_index = LDB_SDK2015_INDEX;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_secret1)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_secret1 = LDB_SDK2015_SECRET1;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_secret2)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_secret2 = LDB_SDK2015_SECRET2;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_challenge_cookie)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_challenge_cookie = LDB_SDK2015_CHALLENGE_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_response_cookie)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_response_cookie = LDB_SDK2015_RESPONSE_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_clientid_cookie)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_clientid_cookie = LDB_SDK2015_CLIENTID_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_serverid_cookie)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_serverid_cookie = LDB_SDK2015_SERVERID_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_security_vhigh)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_security_vhigh = LDB_SDK2015_SECURITY_VHIGH;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_security_medium)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_security_medium = LDB_SDK2015_SECURITY_MEDIUM;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_command_scheme)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_command_scheme = LDB_SDK2015_COMMAND_SCHEME;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_command_exitb)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_command_exitb = LDB_SDK2015_COMMAND_EXITB;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_session_parm)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_session_parm = LDB_SDK2015_SESSION_PARM;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_examid_parm)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_examid_parm = LDB_SDK2015_EXAMID_PARM;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_ldbcheck_parm)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_ldbcheck_parm = LDB_SDK2015_LDBCHECK_PARM;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_ldbonly_check)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_ldbonly_check = LDB_SDK2015_LDBONLY_CHECK;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_monitor_check)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_monitor_check = LDB_SDK2015_MONITOR_CHECK;
}
if (!isset($CFG->block_lockdownbrowser_ldb_sdk2015_prestart_fn)) {
    $CFG->block_lockdownbrowser_ldb_sdk2015_prestart_fn = LDB_SDK2015_PRESTART_FN;
}
if (!isset($CFG->block_lockdownbrowser_ldb_id_cookie)) {
    $CFG->block_lockdownbrowser_ldb_id_cookie = LDB_ID_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_session_cookie)) {
    $CFG->block_lockdownbrowser_ldb_session_cookie = LDB_SESSION_COOKIE;
}
if (!isset($CFG->block_lockdownbrowser_ldb_examtype_ldbonly)) {
    $CFG->block_lockdownbrowser_ldb_examtype_ldbonly = LDB_EXAMTYPE_LDBONLY;
}
if (!isset($CFG->block_lockdownbrowser_ldb_examtype_monitor)) {
    $CFG->block_lockdownbrowser_ldb_examtype_monitor = LDB_EXAMTYPE_MONITOR;
}
if (!isset($CFG->block_lockdownbrowser_ldb_servername)) {
    if (isset($CFG->block_lockdownbrowser_LDB_SERVERNAME)) {
        $CFG->block_lockdownbrowser_ldb_servername = $CFG->block_lockdownbrowser_LDB_SERVERNAME;
    } else {
        $CFG->block_lockdownbrowser_ldb_servername = LDB_SERVERNAME;
    }
}
if (!isset($CFG->block_lockdownbrowser_ldb_serverid)) {
    if (isset($CFG->block_lockdownbrowser_LDB_SERVERID)) {
        $CFG->block_lockdownbrowser_ldb_serverid = $CFG->block_lockdownbrowser_LDB_SERVERID;
    } else {
        $CFG->block_lockdownbrowser_ldb_serverid = LDB_SERVERID;
    }
}
if (!isset($CFG->block_lockdownbrowser_ldb_serversecret)) {
    if (isset($CFG->block_lockdownbrowser_LDB_SERVERSECRET)) {
        $CFG->block_lockdownbrowser_ldb_serversecret = $CFG->block_lockdownbrowser_LDB_SERVERSECRET;
    } else {
        $CFG->block_lockdownbrowser_ldb_serversecret = LDB_SERVERSECRET;
    }
}
if (!isset($CFG->block_lockdownbrowser_ldb_servertype)) {
    if (isset($CFG->block_lockdownbrowser_LDB_SERVERTYPE)) {
        $CFG->block_lockdownbrowser_ldb_servertype = $CFG->block_lockdownbrowser_LDB_SERVERTYPE;
    } else {
        $CFG->block_lockdownbrowser_ldb_servertype = LDB_SERVERTYPE;
    }
}
if (!isset($CFG->block_lockdownbrowser_ldb_download)) {
    if (isset($CFG->block_lockdownbrowser_LDB_DOWNLOAD)) {
        $CFG->block_lockdownbrowser_ldb_download = $CFG->block_lockdownbrowser_LDB_DOWNLOAD;
    } else {
        $CFG->block_lockdownbrowser_ldb_download = LDB_DOWNLOAD;
    }
}
if (!isset($CFG->block_lockdownbrowser_monitor_username)) {
    if (isset($CFG->block_lockdownbrowser_MONITOR_USERNAME)) {
        $CFG->block_lockdownbrowser_monitor_username = $CFG->block_lockdownbrowser_MONITOR_USERNAME;
    } else {
        $CFG->block_lockdownbrowser_monitor_username = '';
    }
}
if (!isset($CFG->block_lockdownbrowser_monitor_password)) {
    if (isset($CFG->block_lockdownbrowser_MONITOR_PASSWORD)) {
        $CFG->block_lockdownbrowser_monitor_password = $CFG->block_lockdownbrowser_MONITOR_PASSWORD;
    } else {
        $CFG->block_lockdownbrowser_monitor_password = '';
    }
}

