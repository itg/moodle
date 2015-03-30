<?php

// List, set, clear cookies for HAProxy
// Examples:
//      (no params)                         : Check/print cookie status
//      (?set_cookie=1&cookie_value=mapp3)  : Set cookie value (to mapp3)
//      (?clear_cookie=1)                   : Remove cookie

// Constants
define("COOKIE_NAME", 'MAPPSERVER');
define("COOKIE_VALUE_MAPP1", 'mapp1');
define("COOKIE_VALUE_MAPP2", 'mapp2');
define("COOKIE_VALUE_MAPP3", 'mapp3');

// Tries to set a cookie
//      -valid for everywhere on this domain
//      -NOT an HTTPS/"secure" cookie
//      -is available HTTP only
// Returns TRUE if the set request was sent
//      (not necessarily if the cookie was set - that is up to the user's browser),
//      or FALSE on failure
function set_domain_cookie($name='',$value='',$expiry=3600) {
    // localhost cookies need to be treated special
    $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
    $result = setcookie($name, $value, $expiry, "/", $domain, false, true);

    return $result;
}

$status = 200;
$message = "Success";

// Test for cookie existence
$cookie_exists = isset($_COOKIE[COOKIE_NAME]);
$cookie_value = 'Not set';
if ($cookie_exists) {
    $cookie_value = $_COOKIE[COOKIE_NAME];
}

// Does the user want to set the cookie (e.g. ?set_cookie=1&cookie_value=mapp3)
$do_set_cookie = isset($_GET['set_cookie']) && $_GET['set_cookie'] && isset($_GET['cookie_value']);
$cookie_new_value = '';
if ($do_set_cookie) {
    $cookie_new_value = $_GET['cookie_value'];

    // Validate new cookie value
    switch ($cookie_new_value) {
        case COOKIE_VALUE_MAPP1:
        case COOKIE_VALUE_MAPP2:
        case COOKIE_VALUE_MAPP3:
            // New cookie value is valid
            // Set the cookie to expire in 30 days
            $expiry = time()+60*60*24*30;
            $result = set_domain_cookie(COOKIE_NAME, $cookie_new_value, $expiry);
            $output = "<p>Setting cookie " . COOKIE_NAME . "=" . $cookie_new_value . "...</p>";
            break;
        default:
            $status = 400;      // 400 -> Bad Request
            $message = "Unknown value";
            $output = "<p>New cookie value '$cookie_new_value' not valid: not setting cookie!</p>";
            break;
    }
}

// Does the user want to clear the cookie? (e.g. ?clear_cookie=1)
$do_clear_cookie = isset($_GET['clear_cookie']) && $_GET['clear_cookie'];
if ($do_clear_cookie) {
    // Expire existing cookie - i.e. set to expire in the past
    $expiry = 1;
    $result = set_domain_cookie(COOKIE_NAME, '', $expiry);
    $output = "<p>Removing cookie " . COOKIE_NAME . "...</p>";
}

header("HTTP/1.0 $status $message");
echo '<html><body>';

if ($do_set_cookie || $do_clear_cookie) {
    echo $output;
    echo "<p>Cookie set request " . ($result ? "sent successfully" : "failed") . "</p>";
    echo '<p><a href="manage_lb_cookie.php">Return</a><p>';

}
else {
    if ($cookie_exists) {
        echo '<p>Cookie \'' . COOKIE_NAME . '\' currently exists with value \'' . $cookie_value . '\'</p>';
    }
    else {
        echo '<p>Cookie \'' . COOKIE_NAME . '\' not currently set.</p>';
    }

    echo '<p>Set cookie to:<br />';
    echo '<a href="manage_lb_cookie.php?set_cookie=1&cookie_value=' . COOKIE_VALUE_MAPP1 . '">mapp1</a> | ';
    echo '<a href="manage_lb_cookie.php?set_cookie=1&cookie_value=' . COOKIE_VALUE_MAPP2 . '">mapp2</a> | ';
    echo '<a href="manage_lb_cookie.php?set_cookie=1&cookie_value=' . COOKIE_VALUE_MAPP3 . '">mapp3</a>';
    echo '</p>';

    echo '<p><a href="manage_lb_cookie.php?clear_cookie=1">Clear cookie</a></p>';
}

echo '</body></html>';
