<?php

//error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once('constants.php');

function smart_active_moodle_categories() {
    $list = array();

    try {
        // connect to SMART
        $smart_dbh = new PDO('mysql:host=' . SMART_DB_SERVER . ';dbname=' . SMART_DB_NAME, SMART_DB_USERNAME, SMART_DB_PASSWORD);

        // Fetch "active" terms from SMART
        // We define "active" terms as those that
        //  have (or will) start within the next 7 days
        //  AND have (or did) end within the last 7 days
        $smart_sql = <<<EOD
SELECT name
FROM terms
WHERE 30 >= DATEDIFF(start, NOW())
AND -7 <= DATEDIFF(end, NOW())
ORDER BY start
EOD;

        $stmt = $smart_dbh->prepare($smart_sql);
        $stmt->execute();
        $i = 0;
        $terms = array();
        while($row = $stmt->fetch()) {
            $terms[':id'.$i] = $row['name'];
            $i++;
        }
        $stmt = NULL;

        if (0 < count($terms)) {
            // lookup Moodle category IDs from SMART terms
            $moodle_dbh = new PDO('mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME, DB_USERNAME, DB_PASSWORD);

            $moodle_sql = "SELECT id FROM mdl_course_categories WHERE TRIM(name) IN (";
            for ($i = 0; $i < count($terms); $i++) {
                $moodle_sql = $moodle_sql . ":id$i, ";
            }

            $moodle_sql = substr($moodle_sql, 0, -2) . ") ";
            $stmt = $moodle_dbh->prepare($moodle_sql);
            $stmt->execute($terms);

            while($row = $stmt->fetch()) {
                $list[] = $row['id'];
            }
            $stmt = NULL;
        }
    }
    catch (PDOException $e) {
        echo "Connection error: " . $e->getMessage() . "\n";
        unset($list);
        $list = array();
    }
    catch (Exception $e) {
        //Some non-PDO exception occurred (probably Memcache-related)
        echo "Error: " . $e->getMessage() . "\n";
        unset($list);
        $list = array();
    }
    return $list;
}

?>
