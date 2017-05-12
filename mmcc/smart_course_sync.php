<?php

require("constants.php");

define('LOG_LEVEL_NONE', 0);
define('LOG_LEVEL_INFO', 1);
define('LOG_LEVEL_DEBUG', 2);

// Borrowed from Moodle
// https://github.com/itg/moodle/blob/MMCC_28/lib/accesslib.php#L141
/** System context level - only one instance in every system */
define('CONTEXT_LEVEL_SYSTEM', 10);

/** Course category context level - one instance for each category */
define('CONTEXT_LEVEL_CATEGORY', 40);

/** Course context level - one instance for each course */
define('CONTEXT_LEVEL_COURSE', 50);

function get_commandline_arguments( $cli_args, &$dry_run, &$verbosity) {
    $dry_run = false;
    $verbosity = LOG_LEVEL_NONE;

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
                if (LOG_LEVEL_INFO >= $verbosity) {
                    $verbosity = LOG_LEVEL_INFO;
                }
                break;
            case "-vv":
            case "--vverbose":
                $verbosity = LOG_LEVEL_DEBUG;
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

function insert_or_update_course( $moodle_handle=NULL, $course=[], $dry_run=true, $insert_stmt, $update_stmt, $select_stmt, &$fields ) {
    $update_fields = ["shortname", "fullname", "summary", "startdate"];
    $insert_fields = array_merge($update_fields, ["idnumber", "category", "format", "visible", "timecreated", "timemodified", "newsitems", "maxbytes", "summaryformat"]);

    $fields = array();
    // find course
    $select_params = array( "idnumber" => $course["idnumber"] );
    $select_stmt->execute($select_params);
    if ($row = $select_stmt->fetch()) {
        // compare fields, update only if necessary
        foreach($update_fields as $field) {
            if ($course[$field] != $row[$field]) {
                $fields[$field] = $row[$field] . ' --> ' . $course[$field];
            }
        }
        if (0 == count($fields)) {
            // Nothing to update
            $inserted = -1;
        }
        else {
            if (false == $dry_run) {
                $course_params = array(
                    "idnumber"      => $course["idnumber"],
                    "fullname"      => $course["fullname"],
                    "shortname"     => $course["shortname"],
                    "summary"       => $course["summary"],
                    "startdate"     => $course["startdate"],
                );
                $update_stmt->execute($course_params);

            }
            $inserted = 0;
        }
    }
    else {
        //Add default fields
        $course["format"]           = "weeks";
        $course["visible"]          = 0;
        $course["timecreated"]      = time();
        $course["timemodified"]     = time();
        $course["newsitems"]        = 5;
        $course["summaryformat"]    = 1;            // HTML
        $course["maxbytes"]         = 5242880;      // 5M

        $course_params = array();

        // insert (mark all fields as updated)
        foreach($insert_fields as $field) {
            $fields[$field] = $course[$field];
            $course_params[$field] = $course[$field];
        }

        if (false == $dry_run) {
            $insert_stmt->execute($course_params);
            $course_id = $moodle_handle->lastInsertId();

            // Create mdl_context record for course
            create_context( $moodle_handle, CONTEXT_LEVEL_COURSE, $course_id, $course["category_context_path"]);

            //We also need to insert 3 enrollment methods: 'manual', 'guest', and 'self'
            $moodle_handle->exec("INSERT INTO mdl_enrol (courseid, enrol, status, sortorder) VALUES ($course_id, 'manual', 0, 0)");
            $moodle_handle->exec("INSERT INTO mdl_enrol (courseid, enrol, status, sortorder) VALUES ($course_id, 'guest',  1, 1)");
            $moodle_handle->exec("INSERT INTO mdl_enrol (courseid, enrol, status, sortorder) VALUES ($course_id, 'self',   1, 2)");
        }

        $inserted = 1;
    }

    return $inserted;
}

// Adapted from Moodle's own insert_context_record()
// https://github.com/itg/moodle/blob/MMCC_28/lib/accesslib.php#L5567
function create_context( $moodle_handle=NULL, $contextlevel=-1, $instanceid=-1, $parentpath="") {
    $context = new stdClass();
    if (0 > $contextlevel || 0 > $instanceid) {
        throw new Exception("Incorrect parameters: expected contextlevel > 0: got $contextlevel; instanceid > 0: got $instanceid");
    }

    $context->contextlevel  = $contextlevel;
    $context->instanceid    = $instanceid;

    $create_stmt = $moodle_handle->prepare("INSERT INTO mdl_context (contextlevel, instanceid, depth) VALUES (:contextlevel, :instanceid, 0)");
    $create_stmt->execute(array(
        ":contextlevel" => $context->contextlevel,
        ":instanceid"   => $context->instanceid,
    ));
    // Get the id of the newly inserted context
    $context->id = $moodle_handle->lastInsertId();

    // update with path (not known before insert) and depth
    $context->path  = $parentpath . '/' . $context->id;
    $context->depth = substr_count($context->path,'/');

    $update_stmt = $moodle_handle->prepare("UPDATE mdl_context SET path = :path, depth = :depth WHERE id = :id");
    $update_stmt->execute(array(
        ":id"    => $context->id,
        ":path"  => $context->path,
        ":depth" => $context->depth,
    ));

    return $context;
}

function find_or_create_course_category( $moodle_handle=NULL, $category_name="", $category_description="", $dry_run=true, $verbosity=LOG_LEVEL_NONE ) {
    $category = new stdClass();
    $category->id = -1;
    $category->path = "";

    $category_sql = "SELECT id FROM mdl_course_categories WHERE `name` = :category_name";
    $find_stmt = $moodle_handle->prepare($category_sql);
    $find_stmt->execute(array(":category_name" => $category_name));
    if ($row = $find_stmt->fetch()) {
        $category->id = $row["id"];
    }

    if (-1 < $category->id) {
        $context_sql = "SELECT path FROM mdl_context WHERE instanceid = :categoryid AND contextlevel = :contextlevel";
        $context_params = array(
            ":categoryid" => $category->id,
            ":contextlevel" => CONTEXT_LEVEL_CATEGORY
        );
        $find_stmt = $moodle_handle->prepare($context_sql);
        $find_stmt->execute($context_params);
        if ($row = $find_stmt->fetch()) {
            $category->context_path = $row["path"];
        }

        if (LOG_LEVEL_DEBUG <= $verbosity) {
            echo "Found category with name: ". $category_name . ": id: " . $category->id . "; path: " . $category->path . "\n";
        }
    }
    else {
        if (LOG_LEVEL_INFO <= $verbosity) {
            echo "Creating category with name: " . $category_name . "; description: " . $category_description . "\n";
        }

        // Determine sortorder for new record, so it will automatically sort to the bottom
        $sortorder = 10000;
        $sortorder_stmt = $moodle_handle->prepare("SELECT 10000 + MAX(sortorder) FROM mdl_course_categories");
        $sortorder_stmt->execute();
        if ($row = $sortorder_stmt->fetch()) {
            $sortorder = $row[0];
        }

        // Create mdl_course_categories record
        $category_insert_sql = "INSERT INTO mdl_course_categories (name, description, visible, timemodified, sortorder) VALUES (:name, :description, :visible, :timemodified, :sortorder)";
        $category_params = array(
            ":name"         => $category_name,
            ":description"  => $category_description,
            ":visible"      => 0,
            ":timemodified" => time(),
            ":sortorder"    => $sortorder,
        );
        $create_stmt = $moodle_handle->prepare($category_insert_sql);
        if (false == $dry_run) {
            $create_stmt->execute($category_params);

            // Get the id of the newly inserted category
            // http://php.net/manual/en/pdo.lastinsertid.php
            $category->id = $moodle_handle->lastInsertId();
            $category->path = '/' . $category->id;

            //Update path (only possible after we know the category id)
            $update_stmt = $moodle_handle->prepare("UPDATE mdl_course_categories SET path = :path, depth = :depth WHERE id = :id");
            $update_stmt->execute(array(
                ":id"    => $category->id,
                ":path"  => $category->path,
                ":depth" => 1,
            ));

            // Find the system context level to find the correct path
            $system_context_path = "/1";

            $stmt = $moodle_handle->prepare("SELECT path FROM mdl_context WHERE 1 = depth AND :context_level = contextlevel");
            $stmt->execute(array(
                ":context_level" => CONTEXT_LEVEL_SYSTEM,
            ));
            if ($row = $stmt->fetch()) {
                $system_context_path = $row["path"];
            }
            $stmt = NULL;

            // Create this category at the top level (e.g. directly under the system context)
            $category_context = create_context( $moodle_handle, CONTEXT_LEVEL_CATEGORY, $category->id, $system_context_path);
            $category->context_path = $category_context->path;

            if (LOG_LEVEL_INFO <= $verbosity) {
                echo "Created category with id: " . $category->id . "; name: " . $category_name . "; description: " . $category_description . "; path: " . $category->path . "\n";
            }
        }
    }

    // Sanity check - do we have both an id and a path?
    if ((0 > $category->id) && ("" == $category->path)) {
        if (true == $dry_run) {
            $category->id = 1;
            $category->path = "/1/1234";
            $category->context_path = "/1/9876";
        }
        else {
            throw new Exception("Unable to find or create course category");
        }
    }

    return $category;
}

function get_courses_from_smart( $handle=NULL ) {
    $courses = array();

    $course_sql =<<<EOD
SELECT cs.id, cs.synonym AS idnumber, cs.`subject`, cs.course, cs.section, cs.term AS category_name, t.`description` AS category_description, cs.synonym, cs.title, cs.start_date, cs.end_date, cs.meeting_info, cs.description AS summary
FROM course_sections cs
INNER JOIN terms t ON cs.term = t.`name`
INNER JOIN moodle_shells ms ON ms.idnumber = cs.synonym

WHERE (0 < COALESCE(ms.pilot_mdl_course_id, 0) AND '' <> ms.idnumber)
OR category_name IN ('2017SP', '2017MT', '2018MT')
EOD;

    # Fetch instructors
    $stmt = $handle->prepare($course_sql);
    $stmt->execute();
    while($row = $stmt->fetch()) {
        $courses[$row["id"]] = extract_course($row);
    }

    echo "Found " . count($courses) . " courses\n";
    return $courses;
}

function extract_course( $row=NULL) {
    $course = array();
    if (!is_null($row)) {
        $summary = "<p>". utf8_decode($row["summary"]) . "</p>";
        //Split meeting info on start_date-end_date
        $meeting_info = preg_replace("/([0-9]{2,2}\/[0-9]{2,2}\/[0-9]{4,4}-[0-9]{2,2}\/[0-9]{2,2}\/[0-9]{4,4})/", "\n\\1", $row["meeting_info"]);
        $meeting_info = str_replace("\n", "</li><li>", $meeting_info);
        $summary .= "<p>Meeting information:</p><ul><li>" . $meeting_info . "</li></ul>";
        $summary = str_replace("<li></li>", "", $summary);

        $shortname = $row["subject"] . '.' . $row["course"] . '.' . $row["section"] . '.' . $row["category_name"] . ' (' . $row["idnumber"] . ')';
        $course = array(
            "id"                    => $row["id"],
            "idnumber"              => $row["idnumber"],
            "shortname"             => $shortname,
            "fullname"              => $shortname . " " . $row["title"],
            "category_name"         => $row["category_name"],
            "category_description"  => $row["category_description"],
            "summary"               => $summary,
            "startdate"             => strtotime($row["start_date"]),
            "enddate"               => strtotime($row["end_date"]),
        );
    }

    return $course;
}

// Get commandline options
$dry_run = true;
$verbosity = LOG_LEVEL_NONE;

get_commandline_arguments( $argv, $dry_run, $verbosity);
if ($dry_run) {
    echo "--dry_run given; simulating actions only!\n";
}

$smart_handle = new PDO("mysql:host=" . SMART_DB_SERVER . ";dbname=" . SMART_DB_NAME, SMART_DB_USERNAME, SMART_DB_PASSWORD);
if (LOG_LEVEL_DEBUG == $verbosity) {
    echo "connected to database `" . SMART_DB_NAME . "` on '" . SMART_DB_SERVER . "'\n";
}

$moodle_handle = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
if (LOG_LEVEL_DEBUG == $verbosity) {
    echo "connected to database `" . DB_NAME . "` on '" . DB_SERVER . "'\n";
}
$moodle_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$courses = get_courses_from_smart($smart_handle);

$insert_sql = "INSERT INTO mdl_course (category, fullname, shortname, idnumber, summary, summaryformat, format, startdate, visible, timecreated, timemodified, maxbytes, newsitems) VALUES (:category, :fullname, :shortname, :idnumber, :summary, :summaryformat, :format, :startdate, :visible, :timecreated, :timemodified, :maxbytes, :newsitems)";
$update_sql = "UPDATE mdl_course SET fullname = :fullname, shortname = :shortname, summary = :summary, startdate = :startdate WHERE idnumber = :idnumber";
$select_sql = "SELECT idnumber, fullname, shortname, summary, startdate FROM mdl_course WHERE idnumber = :idnumber";

$update_category_count_sql = "UPDATE mdl_course_categories SET coursecount = coursecount + 1 WHERE id = :id";

$insert_stmt = $moodle_handle->prepare($insert_sql);
$update_stmt = $moodle_handle->prepare($update_sql);
$select_stmt = $moodle_handle->prepare($select_sql);
$update_category_count_stmt = $moodle_handle->prepare($update_category_count_sql);

$count = -1;
$inserted_count = 0;
$updated_count = 0;
$unmodified_count = 0;
foreach($courses as $course) {
    $count++;

    $category = find_or_create_course_category( $moodle_handle, $course["category_name"], $course["category_description"], $dry_run );
    $course["category"] = $category->id;
    $course["category_context_path"] = $category->context_path;

    $inserted = insert_or_update_course( $moodle_handle, $course, $dry_run, $insert_stmt, $update_stmt, $select_stmt, $fields_updated);

    if (-1 == $inserted) {
        $unmodified_count++;

        if (LOG_LEVEL_DEBUG <= $verbosity) {
            echo "[$count] course[" . $course["idnumber"] . "]: Up to date\n";
        }
        continue;
    }
    else if (1 == $inserted) {
        $inserted_count++;
        if (LOG_LEVEL_INFO <= $verbosity) {
            echo "[$count] course[" . $course["idnumber"] . "]: Inserted new record: ";
        }

        if (false == $dry_run) {
            $update_category_count_stmt->execute(array(":id" => $category->id));
        }
    }
    else if(0 == $inserted) {
        $updated_count++;
        if (LOG_LEVEL_INFO <= $verbosity) {
            echo "[$count] course[" . $course["idnumber"] . "]: Updated existing record: ";
        }
    }

    if (LOG_LEVEL_INFO <= $verbosity) {
        echo print_r($fields_updated, true);
    }
    echo "\n";
}

echo "\n----------\n";
if ($dry_run) {
    echo "--dry_run given; simulating actions only!\n";
}
echo ($count + 1) . " courses; $inserted_count inserted, $updated_count updated, $unmodified_count up to date\n";
