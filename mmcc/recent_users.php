<?php

//recent_users.php
//
//author:	Matt Rice
//date:		2013-08-01
//
//Modified version of /var/moodle/recent_users.php from mapp3, written by Brandon Kish

require_once('constants.php');

// Connect to database
$conn = mysql_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD) or die("Database connection failed");
$db = mysql_select_db(DB_NAME) or die("Database selection failed");

$sql = "SELECT COUNT(*) AS recent_users FROM mdl_user WHERE lastaccess >= " . (time() - (60 * 5));
$rs = mysql_query($sql) or die ("Query failed: $sql");
$row = mysql_fetch_array($rs, MYSQL_ASSOC);
echo "<p>" . $row['recent_users'] . " users have accessed Moodle within the last 5 minutes</p>";

$sql = "SELECT COUNT(*) AS recent_users FROM mdl_user WHERE lastaccess >= " . (time() - (60 * 60 * 3));
$rs = mysql_query($sql) or die ("Query failed: $sql");
$row = mysql_fetch_array($rs, MYSQL_ASSOC);
echo "<p>" . $row['recent_users'] . " users have accessed Moodle within the last 3 hours</p>";

$sql = "SELECT COUNT(*) AS recent_users FROM mdl_user WHERE lastaccess >= " . mktime(0, 0, 0, date('n'), date('d'), date('Y'));
$rs = mysql_query($sql) or die ("Query failed: $sql");
$row = mysql_fetch_array($rs, MYSQL_ASSOC);
echo "<p>" . $row['recent_users'] . " users have accessed Moodle since midnight</p>";


?>
