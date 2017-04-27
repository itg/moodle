<?php

//NOT using constants.php here because we need to connect to so many
//  different databases, and I did not want to clutter up that file

// read-only access is sufficient
define("MOODLE_SERVER", '');
define("MOODLE_USERNAME", '');
define("MOODLE_PASSWORD", '');
define("MOODLE_NAME", '');

// read-only access is sufficient
define("PILOT_SERVER", '');
define("PILOT_USERNAME", '');
define("PILOT_PASSWORD", '');
define("PILOT_NAME", '');

// read-write access is necessary
define("CLUSTER_SMART_SERVER", '');
define("CLUSTER_SMART_USERNAME", '');
define("CLUSTER_SMART_PASSWORD", '');
define("CLUSTER_SMART_NAME", '');

// read-write access is necessary
define("MDB_SMART_SERVER", '');
define("MDB_SMART_USERNAME", '');
define("MDB_SMART_PASSWORD", '');
define("MDB_SMART_NAME", '');

define('LOG_LEVEL_NONE', 0);
define('LOG_LEVEL_INFO', 1);
define('LOG_LEVEL_DEBUG', 2);

function post_to_slack($message) {
    $ch = NULL;
    try {
        $payload = "payload=" . json_encode([
            "channel"       => "#moodle",
            "icon_emoji"    => ":shell:",
            "username"      => "Create Moodle Shell Overrides",
            "text"          => $message,
        ]);

        $ch = curl_init('https://hooks.slack.com/services/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
    }
    catch (Exception $e) {
        puts("[ERROR] Failed to post to Slack: " . $e-getMessage() . "\n");
    }
    finally {
        if (!is_null($ch)) {
            curl_close($ch);
        }
    }
}

function puts($message = "", $log_level = LOG_LEVEL_NONE) {
    if ($log_level <= $GLOBALS['verbosity']) {
        echo $message;
    }
}

function get_commandline_arguments( $cli_args, &$dry_run, &$verbosity) {
    $dry_run = false;
    $GLOBALS['verbosity'] = LOG_LEVEL_NONE;

    if ($cli_args) {
        // $argv[0] is the name of the calling script
        // http://php.net/manual/en/reserved.variables.argv.php
        array_shift($cli_args);

        foreach($cli_args as $arg) {
            switch ($arg) {
            case "-d":
            case "--dry-run":
                $dry_run = true;
                break;
            case "-v":
            case "--verbose":
                if (LOG_LEVEL_INFO >= $GLOBALS['verbosity']) {
                    $GLOBALS['verbosity'] = LOG_LEVEL_INFO;
                }
                break;
            case "-vv":
            case "--vverbose":
                $GLOBALS['verbosity'] = LOG_LEVEL_DEBUG;
                break;

            case "--":
                // ignore empty arg
                // may be present iff invoked via php -f /path/to/file -- --arg0 --arg1 ... --argN
                break;
            default:
                die("Unknown argument: '$arg' - exiting.\n");
            }
        }
    }
}

// global - yuck
$GLOBALS['verbosity'] = LOG_LEVEL_NONE;

puts("Starting\n", LOG_LEVEL_NONE);

$created_count = 0;
$warn_count = 0;
$error_count = 0;

get_commandline_arguments( $argv, $dry_run, $verbosity);

$GLOBALS['verbosity'] = $verbosity;
if ($dry_run) {
    puts("--dry_run given; simulating actions only!\n", LOG_LEVEL_NONE);
}

$db_handle      = new PDO("mysql:host=" . MOODLE_SERVER        . ";dbname=" . MOODLE_NAME,        MOODLE_USERNAME,        MOODLE_PASSWORD);
$pilot_handle   = new PDO("mysql:host=" . PILOT_SERVER         . ";dbname=" . PILOT_NAME,         PILOT_USERNAME,         PILOT_PASSWORD);
$cluster_handle = new PDO("mysql:host=" . CLUSTER_SMART_SERVER . ";dbname=" . CLUSTER_SMART_NAME, CLUSTER_SMART_USERNAME, CLUSTER_SMART_PASSWORD);
$mdb_handle =     new PDO("mysql:host=" . MDB_SMART_SERVER     . ";dbname=" . MDB_SMART_NAME,     MDB_SMART_USERNAME,     MDB_SMART_PASSWORD);

// Build up list of courses
$courses = [];

// Get synonyms from (cluster.smart)
// foreach synonym
$sql = "SELECT * FROM course_sections WHERE current_status IN ('A', 'P', 'S') AND term IN ('2017SP')";
$stmt = $cluster_handle->prepare($sql);
$stmt->execute();
while($row = $stmt->fetch()) {
    $course = new stdClass();
    $course->synonym  = $row["synonym"];
    $course->db_id    = NULL;
    $course->pilot_id = NULL;

    $courses[] = $course;
}
$stmt = NULL;

puts("Found " . count($courses) . " courses\n", LOG_LEVEL_INFO);

$stmt = $db_handle->prepare("SELECT id FROM mdl_course WHERE :synonym = idnumber");
for($i = 0; count($courses) > $i; $i++) {
    $course = $courses[$i];

    $stmt->execute(array(":synonym" => $course->synonym));
    if ($row = $stmt->fetch()) {
        puts("Got Moodle id number for synonym " . $course->synonym . ": " . $row["id"] . "\n", LOG_LEVEL_DEBUG);

        $courses[$i]->db_id = $row["id"];
    }
    else {
        $warn_count++;
        puts("[WARN] No Moodle course for " . $course->synonym . "!\n", LOG_LEVEL_INFO);
    }
}
$stmt = NULL;

// test to ensure the number of $courses with db_id = count($courses) - $warn_count
$db_id_count = 0;
for($i = 0; count($courses) > $i; $i++) {
    if (!is_null($courses[$i]->db_id)) {
        $db_id_count++;
    }
}

puts("courses with Moodle db id present: " . $db_id_count . "; warning count: " . $warn_count . "; total course count: " . count($courses) . "\n", LOG_LEVEL_NONE);

// Get Pilot IDs
$stmt = $pilot_handle->prepare("SELECT id FROM mdl_course WHERE :synonym = idnumber");
for($i = 0; count($courses) > $i; $i++) {
    $course = $courses[$i];

    $stmt->execute(array(":synonym" => $course->synonym));
    if ($row = $stmt->fetch()) {
        puts("Got Pilot id number for synonym " . $course->synonym . ": " . $row["id"] . "\n", LOG_LEVEL_DEBUG);

        $courses[$i]->pilot_id = $row["id"];
    }
    else {
        $warn_count++;
        puts("[WARN] No Pilot course for " . $course->synonym . "!\n", LOG_LEVEL_INFO);
    }
}
$stmt = NULL;

$cluster_obj = new stdClass();
$cluster_obj->count = 0;
$cluster_obj->errors = [];
$cluster_obj->handle = $cluster_handle;

$mdb_obj = new stdClass();
$mdb_obj->count = 0;
$mdb_obj->errors = [];
$mdb_obj->handle = $mdb_handle;

$database_handles = array(
    "cluster" => $cluster_obj,
    "mdb"     => $mdb_obj,
);

$select_sql = "SELECT * FROM moodle_shell_overrides WHERE :db_id = mdl_course_id";
$insert_sql =<<<EOD
INSERT INTO moodle_shell_overrides
    (active, created_at, updated_at, mdl_course_id, pilot_mdl_course_id,notes)
VALUES
    (1, NOW(), NOW(), :db_id, :pilot_id, 'automatically created by create_moodle_shell_overrides.php')
EOD;

foreach ($database_handles as $key => $db_obj) {
    $handle = $db_obj->handle;

     // create moodle_shell_overrides as necessary
    puts("Connected to $key successfully " . print_r($handle, true) . "\n", LOG_LEVEL_DEBUG);

    $select_stmt = $handle->prepare($select_sql);
    $insert_stmt = $handle->prepare($insert_sql);
    for($i = 0; count($courses) > $i; $i++) {
        $course = $courses[$i];

        try {
            // Do we have a Moodle course id to work with? If not, warn and skip to next course
            if (is_null($course->db_id)) {
                puts("[WARN] course with synonym " . $course->synonym . " has no Moodle course id - skipping!\n", LOG_LEVEL_INFO);
                continue;
            }

            // Do we already have a moodle_course_overrides record? If so, skip to next course
            // We could check to ensure the pilot_mdl_course_id is present/correct here and update if necessary
            $select_stmt->execute(array(
                ":db_id" => $course->db_id,
            ));
            if ($select_stmt->fetch()) {
                puts("Found a moodle_shell_override for Moodle course id " . $course->db_id . " (synonym " . $course->synonym . ") - skipping\n", LOG_LEVEL_DEBUG);
                continue;
            }

            // We have a Moodle course id and no moodle_shell_overrides record - create one
            puts("Creating moodle_shell_overrides record for synonym: " . $course->synonym . " with Moodle course id " . $course->db_id . " and Pilot course id " . $course->pilot_id . "\n", LOG_LEVEL_DEBUG);
            $db_obj->count++;
            $created_count++;
            if (false == $dry_run) {
                $insert_stmt->execute(array(
                    ":db_id"    => $course->db_id,
                    ":pilot_id" => $course->pilot_id,
                ));
            }
        }
        catch (Exception $e) {
            $error_count++;
            puts("[ERROR] Working on synonym " . $course->synonym . ": " . $e->getMessage() . "\n", LOG_LEVEL_DEBUG);
            $db_obj->errors[] = $e;
        }
    }
}

foreach ($database_handles as $key => $db_obj) {
    puts("Inserted " . $db_obj->count . " records into " . $key . "\n", LOG_LEVEL_INFO);
}

if ((false == $dry_run) && (0 < $created_count)) {
    post_to_slack("Created $created_count shell overrides");
}

puts("Finished with $warn_count warnings, $error_count errors\n", LOG_LEVEL_NONE);
