<?php

require("constants.php");

function insert_or_update_user( $user=[], $dry_run=true, $insert_stmt, $update_stmt, $select_stmt, &$fields ) {
    $update_fields = ["username", "firstname", "lastname", "email"];
    $insert_fields = array_merge($update_fields, ["idnumber", "auth", "password", "maildigest", "trackforums", "lang"]);

    $fields = array();
    // find user
    $select_params = array( "idnumber" => $user["idnumber"] );
    $select_stmt->execute($select_params);
    if ($row = $select_stmt->fetch()) {
        // compare fields, update only if necessary
        foreach($update_fields as $field) {
            if ($user[$field] != $row[$field]) {
                $fields[$field] = $row[$field] . ' --> ' . $user[$field];
            }
        }
        if (0 == count($fields)) {
            // Nothing to update
            $inserted = -1;
        }
        else {
            if (false == $dry_run) {
                $update_stmt->execute($user);
            }
            $inserted = 0;
        }
    }
    else {
        // Add in default values
        $user["auth"] = "cas";
        $user["password"] = "not_cached";
        $user["maildigest"] = "1";
        $user["trackforums"] = "1";
        $user["lang"] = "en_us";

        // insert (mark all fields as updated)
        foreach($insert_fields as $field) {
            $fields[$field] = $user[$field];
        }

        if (false == $dry_run) {
            $insert_stmt->execute($user);
        }
        $inserted = 1;
    }

    return $inserted;
}

function get_users_from_smart( $handle=NULL ) {
    $users = array();

    $student_sql =<<<EOD
    SELECT DISTINCT(u.id_string) AS idnumber, u.username, u.firstname, u.nickname, u.lastname, CONCAT(u.username, '@midmich.edu') AS email
    FROM moodle_shells ms
    INNER JOIN course_sections cs ON cs.synonym = ms.idnumber
    INNER JOIN student_memberships sm ON sm.course_section_id = cs.id
    INNER JOIN users u ON u.id = sm.user_id

    WHERE ms.pilot_mdl_course_id IS NOT NULL
EOD;

    $instructor_sql =<<<EOD
    SELECT DISTINCT(u.id_string) AS idnumber, u.username, u.firstname, u.nickname, u.lastname, CONCAT(u.username, '@midmich.edu') AS email
    FROM moodle_shells ms
    INNER JOIN course_sections cs ON cs.synonym = ms.idnumber
    INNER JOIN instructor_memberships im ON im.course_section_id = cs.id
    INNER JOIN users u ON u.id = im.user_id

    WHERE ms.pilot_mdl_course_id IS NOT NULL
EOD;

    # Fetch students
    $stmt = $handle->prepare($student_sql);
    $stmt->execute();
    while($row = $stmt->fetch()) {
        $users[$row["idnumber"]] = extract_user($row);
    }

    # Fetch instructors
    $stmt = $handle->prepare($instructor_sql);
    $stmt->execute();
    while($row = $stmt->fetch()) {
        $users[$row["idnumber"]] = extract_user($row);
    }

    echo "Found " . count($users) . " users\n";
    return $users;
}

function extract_user( $row=NULL) {
    $user = array();
    if (!is_null($row)) {
        $user = array(
            "idnumber"  => $row["idnumber"],
            "username"  => $row["username"],
            "firstname" => ("" == $row["nickname"] ? $row["firstname"] : $row["nickname"]),
            "lastname"  => $row["lastname"],
            "email"     => $row["email"],
        );
    }

    return $user;
}

// Get commandline options
$dry_run = false;
if ($dry_run) {
    echo "--dry_run given; simulating actions only!\n";
}

$smart_handle = new PDO("mysql:host=" . SMART_DB_SERVER . ";dbname=" . SMART_DB_NAME, SMART_DB_USERNAME, SMART_DB_PASSWORD);
echo "connected to database `" . SMART_DB_NAME . "` on '" . SMART_DB_SERVER . "'\n";
$moodle_handle = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
echo "connected to database `" . DB_NAME . "` on '" . DB_SERVER . "'\n";
$users = get_users_from_smart($smart_handle);


$insert_sql = "INSERT INTO mdl_user (username, firstname, lastname, email, idnumber, auth, password, maildigest, trackforums, lang) VALUES (:username, :firstname, :lastname, :email, :idnumber, :auth, :password, :maildigest, :trackforums, :lang)";
$update_sql = "UPDATE mdl_user SET username = :username, firstname = :firstname, lastname = :lastname, email = :email WHERE idnumber = :idnumber";
$select_sql = "SELECT idnumber, username, firstname, lastname, email FROM mdl_user WHERE idnumber = :idnumber";

$insert_stmt = $moodle_handle->prepare($insert_sql);
$update_stmt = $moodle_handle->prepare($update_sql);
$select_stmt = $moodle_handle->prepare($select_sql);

$count = -1;
$inserted_count = 0;
$updated_count = 0;
$unmodified_count = 0;
foreach($users as $user) {
    $count++;

    $inserted = insert_or_update_user( $user, $dry_run, $insert_stmt, $update_stmt, $select_stmt, $fields_updated);

    if (-1 == $inserted) {
        $unmodified_count++;
        echo "[$count] user[" . $user["idnumber"] . "]: Up to date\n";
        continue;
    }
    else if (1 == $inserted) {
        $inserted_count++;
        echo "[$count] user[" . $user["idnumber"] . "]: Inserted new record: ";
    }
    else if(0 == $inserted) {
        $updated_count++;
        echo "[$count] user[" . $user["idnumber"] . "]: Updated existing record: ";
    }
    echo print_r($fields_updated, true) . "\n";
}

echo "\n----------\n";
if ($dry_run) {
    echo "--dry_run given; simulating actions only!\n";
}
echo ($count + 1) . " users; $inserted_count inserted, $updated_count updated, $unmodified_count up to date\n";
