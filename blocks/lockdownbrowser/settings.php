<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

if (!isset($CFG)) {
    require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");
}

require_once("$CFG->dirroot/blocks/lockdownbrowser/locklib.php");

// existence check necessary due to Moodle handling of this file
if (!function_exists("lockdownbrowser_getsettingsstring")) {
    function lockdownbrowser_getsettingsstring($identifier, $a = null) {
        global $CFG;
        $component = "block_lockdownbrowser";
        if (isset($CFG) && $CFG->version >= 2012062500) {
            // Moodle 2.3.0+.
            return new lang_string($identifier, $component, $a);
        } else {
            // Prior to Moodle 2.3.0.
            return get_string($identifier, $component, $a);
        }
    }
}

$settings->add(
    new admin_setting_heading(
        "lockdown_blockdescheader",
        lockdownbrowser_getsettingsstring("blockdescheader"),
        lockdownbrowser_getsettingsstring("blockdescription")
    )
);

$lockdownbrowser_version_file = "$CFG->dirroot/blocks/lockdownbrowser/version.php";
$lockdownbrowser_version      = "(error: version not found)";
if (is_readable($lockdownbrowser_version_file)) {
    $lockdownbrowser_contents = file_get_contents($lockdownbrowser_version_file);
    if ($lockdownbrowser_contents !== false) {
        $lockdownbrowser_parts = explode("=", $lockdownbrowser_contents);
        if (count($lockdownbrowser_parts) > 0) {
            $lockdownbrowser_parts   = explode(";", $lockdownbrowser_parts[1]);
            $lockdownbrowser_version = trim($lockdownbrowser_parts[0]);
        }
    }
}
$settings->add(
    new admin_setting_heading(
        "lockdown_blockversionheader",
        lockdownbrowser_getsettingsstring("blockversionheader"),
        $lockdownbrowser_version // . " (internal release for Q/A)"
    )
);

$settings->add(
    new admin_setting_heading(
        "lockdown_adminsettingsheader",
        lockdownbrowser_getsettingsstring("adminsettingsheader"),
        lockdownbrowser_getsettingsstring("adminsettingsheaderinfo")
    )
);

$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_servername",
        lockdownbrowser_getsettingsstring("servername"),
        lockdownbrowser_getsettingsstring("servernameinfo"),
        $CFG->block_lockdownbrowser_ldb_servername,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_serverid",
        lockdownbrowser_getsettingsstring("serverid"),
        lockdownbrowser_getsettingsstring("serveridinfo"),
        $CFG->block_lockdownbrowser_ldb_serverid,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_serversecret",
        lockdownbrowser_getsettingsstring("serversecret"),
        lockdownbrowser_getsettingsstring("serversecretinfo"),
        $CFG->block_lockdownbrowser_ldb_serversecret,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_servertype",
        lockdownbrowser_getsettingsstring("servertype"),
        lockdownbrowser_getsettingsstring("servertypeinfo"),
        $CFG->block_lockdownbrowser_ldb_servertype,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_download",
        lockdownbrowser_getsettingsstring("downloadurl"),
        lockdownbrowser_getsettingsstring("downloadinfo"),
        $CFG->block_lockdownbrowser_ldb_download,
        PARAM_TEXT
    )
);

$settings->add(
    new admin_setting_heading(
        "lockdown_authenticationsettingsheader",
        lockdownbrowser_getsettingsstring("authenticationsettingsheader", "block_lockdownbrowser"),
        lockdownbrowser_getsettingsstring("authenticationsettingsheaderinfo", "block_lockdownbrowser")
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_monitor_username",
        lockdownbrowser_getsettingsstring("username", "block_lockdownbrowser"),
        lockdownbrowser_getsettingsstring("usernameinfo", "block_lockdownbrowser"),
        $CFG->block_lockdownbrowser_monitor_username,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configpasswordunmask(
        "block_lockdownbrowser_monitor_password",
        lockdownbrowser_getsettingsstring("password", "block_lockdownbrowser"),
        lockdownbrowser_getsettingsstring("passwordinfo", "block_lockdownbrowser"),
        $CFG->block_lockdownbrowser_monitor_password,
        PARAM_TEXT
    )
);

// status string
$lockdownbrowser_ist = "";
$result = lockdownbrowser_check_plugin_dependencies(1);
if ($result !== false){
    $lockdownbrowser_ist .= "<div style='font-size: 125%; color:red; text-align: left; padding: 30px'>";
    $lockdownbrowser_ist .= $result;
    $lockdownbrowser_ist .= "</div>";
}
$lockdownbrowser_quiz_count = $DB->count_records("block_lockdownbrowser_sett");
if ($lockdownbrowser_quiz_count >= 50000) { // Trac #2315
    $lockdownbrowser_ist .= "<div style='font-size: 125%; color:red; text-align: left; padding: 30px'>";
    $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('ldb_quiz_count', $lockdownbrowser_quiz_count);
    $lockdownbrowser_ist .= "</div>";
}
$lockdownbrowser_session = lockdownbrowser_get_session_cookie();
if ($lockdownbrowser_session === false) {
    $lockdownbrowser_ist .= "<div style='font-size: 125%; color:red; text-align: left; padding: 30px'>";
    $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('session_cookie_not_set');
    $lockdownbrowser_ist .= "</div>";
}
if (!during_initial_install() && empty($CFG->upgraderunning)) {
    $lockdownbrowser_mod_installed = $DB->get_manager()->table_exists(new xmldb_table("lockdown_settings"));
    if (!$lockdownbrowser_mod_installed) {
        clearstatcache();
        $lockdownbrowser_mod_folder = "$CFG->dirroot/mod/lockdown";
        $lockdownbrowser_mod_installed = (file_exists($lockdownbrowser_mod_folder)
          && is_dir($lockdownbrowser_mod_folder));
    }
    if ($lockdownbrowser_mod_installed) {
        $lockdownbrowser_ist .= "<div style='font-size: 125%; color:red; text-align: left; padding: 30px'>";
        $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('module_installed_error');
        $lockdownbrowser_ist .= "</div>";
    }
}
if (strlen($lockdownbrowser_ist) == 0) {
    $lockdownbrowser_ist .= "<div style='font-size: 125%; color:blue; text-align: left; padding: 30px'>";
    $lockdownbrowser_ist .= lockdownbrowser_getsettingsstring('block_status_ok');
    $lockdownbrowser_ist .= "</div>";
}
$settings->add(
    new admin_setting_heading(
        "lockdown_adminstatus",
        lockdownbrowser_getsettingsstring("adminstatus"),
        $lockdownbrowser_ist
    )
);

