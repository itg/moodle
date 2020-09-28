<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

// production flags
// - should all default to false or 0
// - change only for exceptional environments
define("LOCKDOWNBROWSER_IGNORE_HTTPS_LOGIN", false); // set true to ignore $CFG->loginhttps
define("LOCKDOWNBROWSER_SCRIPT_TIME_LIMIT", 0); // set > 0 to override default set in php.ini
define("LOCKDOWNBROWSER_MONITOR_ENABLE_4851", true); // set true to enable changes for Trac #4851

// debug-only flags
// - should always be false for production environments
$GLOBALS["lockdownbrowser_disable_callbacks"] = false; // set true to always skip login security callbacks
define("LOCKDOWNBROWSER_OPTIONAL_CALLBACKS", false); // set true to skip callbacks only if no token is provided
define("LOCKDOWNBROWSER_MONITOR_ENABLE_LOG", false); // set true to enable logging to temp file
define("LOCKDOWNBROWSER_MONITOR_ENABLE_PHPINFO", false); // set true to enable PHPInfo reporting

// local options
define("LOCKDOWNBROWSER_MONITOR_REDEEMURL",
    "https://smc-service-cloud.respondus2.com/MONServer/lms/redeemtoken.do");
define("LOCKDOWNBROWSER_MONITOR_LOG", "ldb_monitor.log");

// Moodle options
define("NO_DEBUG_DISPLAY", true);

$lockdownbrowser_moodlecfg_file =
    dirname(dirname(dirname(__FILE__))) . "/config.php";
if (is_readable($lockdownbrowser_moodlecfg_file)) {
    require_once($lockdownbrowser_moodlecfg_file);
} else {
    lockdownbrowser_monitorserviceerror(2001, "Moodle config.php not found");
}

$lockdownbrowser_gradelib_file = "$CFG->libdir/gradelib.php";
if (is_readable($lockdownbrowser_gradelib_file)) {
    require_once($lockdownbrowser_gradelib_file);
} else {
    lockdownbrowser_monitorserviceerror(2030, "Moodle gradelib.php not found");
}

// Trac #4179
$lockdownbrowser_grouplib_file = "$CFG->libdir/grouplib.php";
if (is_readable($lockdownbrowser_grouplib_file)) {
    require_once($lockdownbrowser_grouplib_file);
} else {
    lockdownbrowser_monitorserviceerror(2057, "Moodle grouplib.php not found");
}

// Trac #3884
$lockdownbrowser_blowfish_file =
    "$CFG->dirroot/blocks/lockdownbrowser/blowfish.php";
if (is_readable($lockdownbrowser_blowfish_file)) {
    require_once($lockdownbrowser_blowfish_file);
} else {
    lockdownbrowser_monitorserviceerror(2056, "blowfish.php not found");
}

$lockdownbrowser_locklib_file =
    "$CFG->dirroot/blocks/lockdownbrowser/locklib.php";
if (is_readable($lockdownbrowser_locklib_file)) {
    require_once($lockdownbrowser_locklib_file);
} else {
    lockdownbrowser_monitorserviceerror(2033, "locklib.php not found");
}

if (!empty($CFG->maintenance_enabled)
    || file_exists($CFG->dataroot . "/" . SITEID . "/maintenance.html")
) {
    lockdownbrowser_monitorserviceerror(2002, "The Moodle site is currently undergoing maintenance");
}

raise_memory_limit(MEMORY_EXTRA);

if (LOCKDOWNBROWSER_SCRIPT_TIME_LIMIT > 0) {
    if ($CFG->version >= 2014051200) {
        // Moodle 2.7.0+.
        core_php_time_limit::raise(LOCKDOWNBROWSER_SCRIPT_TIME_LIMIT);
    } else {
        // Prior to Moodle 2.7.0.
        set_time_limit(LOCKDOWNBROWSER_SCRIPT_TIME_LIMIT);
    }
}

set_exception_handler("lockdownbrowser_monitorexceptionhandler");

lockdownbrowser_monitorservicerequest();

exit;

// Trac #3740
function lockdownbrowser_utf8encode($input, $encoding = "") {

    if (strlen($input) == 0) {
        return $input; // nothing to convert
    } else if ($encoding == 'UTF-8') {
        return $input; // assume no need to convert (might not be true if incorrect data)
    } else if (lockdownbrowser_isvalidutf8($input)) {
        return $input; // assume no need to convert (might not be true for some encodings)
    } else if (strlen($encoding) == 0) {
        if (function_exists('mb_detect_encoding')) {
            // mb_detect_encoding mostly fails, or is easily fooled, but apparently no better solution exists
            $detected = mb_detect_encoding($input, mb_detect_order(), true);
            if ($detected === false) {
                if (function_exists('mb_check_encoding')) {
                    if (mb_check_encoding($input, 'ISO-8859-1')) {
                        return utf8_encode($input); // assume ISO-8859-1 (might not be true for some encodings)
                    } else {
                        return $input; // can't convert
                    }
                } else {
                    return utf8_encode($input); // fallback, assume ISO-8859-1
                }
            } else {
                return mb_convert_encoding($input, 'UTF-8', $detected);
            }
        } else {
            return utf8_encode($input); // fallback, assume ISO-8859-1
        }
    } else if ($encoding == 'ISO-8859-1') {
        return utf8_encode($input);
    } else if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($input, 'UTF-8', $encoding);
    } else {
        return $input; // can't convert
    }
}

// Trac #3740
function lockdownbrowser_isvalidutf8($string) {

    $len = strlen($string);

     if($len == 0) {
        return true;
    } else if (function_exists('mb_check_encoding')) {
        return  mb_check_encoding($string, 'UTF-8');
    } else {
        // fall back to using local test
    }
    $i = 0;

    while ($i < $len) {

        // max sequence is 4 bytes
        $c0 = ord($string[$i]);
        if ($i + 1 < $len) {
            $c1 = ord($string[$i + 1]);
        }
        if ($i + 2 < $len) {
            $c2 = ord($string[$i + 2]);
        }
        if ($i + 3 < $len) {
            $c3 = ord($string[$i + 3]);
        }
        // ASCII
        if ($c0 >= 0x00 && $c0 <= 0x7e) {
            // Non-overlong 2-byte.
            $i++;
        } else if ($i + 1 < $len
            && $c0 >= 0xc2 && $c0 <= 0xdf
            && $c1 >= 0x80 && $c1 <= 0xbf
        ) {
            // Excluding overlongs.
            $i += 2;
        } else if ($i + 2 < $len
            && $c0 == 0xe0
            && $c1 >= 0xa0 && $c1 <= 0xbf
            && $c2 >= 0x80 && $c2 <= 0xbf
        ) {
            // Straight 3-byte.
            $i += 3;
        } else if ($i + 2 < $len
            && (($c0 >= 0xe1 && $c0 <= 0xec) || $c0 == 0xee || $c0 == 0xef)
            && $c1 >= 0x80 && $c1 <= 0xbf
            && $c2 >= 0x80 && $c2 <= 0xbf
        ) {
            // Excluding surrogates.
            $i += 3;
        } else if ($i + 2 < $len
            && $c0 == 0xed
            && $c1 >= 0x80 && $c1 <= 0x9f
            && $c2 >= 0x80 && $c2 <= 0xbf
        ) {
            // Planes 1-3.
            $i += 3;
        } else if ($i + 3 < $len
            && $c0 == 0xf0
            && $c1 >= 0x90 && $c1 <= 0xbf
            && $c2 >= 0x80 && $c2 <= 0xbf
            && $c3 >= 0x80 && $c3 <= 0xbf
        ) {
            // Planes 4-15.
            $i += 4;
        } else if ($i + 3 < $len
            && $c0 >= 0xf1 && $c0 <= 0xf3
            && $c1 >= 0x80 && $c1 <= 0xbf
            && $c2 >= 0x80 && $c2 <= 0xbf
            && $c3 >= 0x80 && $c3 <= 0xbf
        ) {
            // Plane 16.
            $i += 4;
        } else if ($i + 3 < $len
            && $c0 == 0xf4
            && $c1 >= 0x80 && $c1 <= 0x8f
            && $c2 >= 0x80 && $c2 <= 0xbf
            && $c3 >= 0x80 && $c3 <= 0xbf
        ) {
            // Invalid utf-8.
            $i += 4;
        } else {
            return false;
        }
    }
    return true;
}

function lockdownbrowser_monitorserviceerror($code = "", $message = "", $encrypt = true) {

    if (empty($code)) {
        $code    = "2000";
        $message = "Unspecified error";
    }

    $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";
    $body .= "<service_error>\r\n";

    $body .= "\t<code>";
    $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($code)));
    $body .= "</code>\r\n";

    if (empty($message)) {
        $body .= "\t<message />\r\n";
    } else {
        $body .= "\t<message>";
        $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($message)));
        $body .= "</message>\r\n";
    }

    $body .= "</service_error>\r\n";

    lockdownbrowser_monitorserviceresponse("text/xml", $body, $encrypt);
}

function lockdownbrowser_monitorservicestatus($code = "", $message = "") {

    if (empty($code)) {
        $code    = "1000";
        $message = "Unspecified status";
    }

    $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";
    $body .= "<service_status>\r\n";

    $body .= "\t<code>";
    $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($code)));
    $body .= "</code>\r\n";

    if (empty($message)) {
        $body .= "\t<message />\r\n";
    } else {
        $body .= "\t<message>";
        $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($message)));
        $body .= "</message>\r\n";
    }

    $body .= "</service_status>\r\n";

    lockdownbrowser_monitorserviceresponse("text/xml", $body, true);
}

function lockdownbrowser_monitorserviceresponse($content_type, $body, $encrypt, $log = true) {

    if ($log) {
        lockdownbrowser_monitorlog("service response: " . $body);
    }

    header("Cache-Control: private, must-revalidate");
    header("Expires: -1");
    header("Pragma: no-cache");

    if ($encrypt === true) {
        $encrypted = lockdownbrowser_monitorbase64encrypt($body, true);
        if (is_null($encrypted)) {
            header("Content-Type: $content_type");
            // may need this instead for Unicode support
            //header("Content-Type: $content_type; charset=utf-8");
            echo $body;
        } else {
            // actually text/plain should be used, since the response is
            // base64-encoded, but for some reason text/html is needed for
            // IE-based clients
            header("Content-Type: text/html");
            $url_encoded = urlencode($encrypted);
            echo $url_encoded;
        }
    } else {
        header("Content-Type: $content_type");
        // may need this instead for Unicode support
        //header("Content-Type: $content_type; charset=utf-8");
        echo $body;
    }

    exit;
}

function lockdownbrowser_monitorcourselistresponse($courses) {

    $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";

    if (empty($courses)) {
        $body .= "<courseList />\r\n";
        lockdownbrowser_monitorserviceresponse("text/xml", $body, true);
    }

    $body .= "<courseList>\r\n";

    foreach ($courses as $c) {
        $body .= "\t<course>\r\n";

        $body .= "\t\t<courseRefId>";
        $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($c->id)));
        $body .= "</courseRefId>\r\n";

        $body .= "\t\t<courseId>";
        $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($c->shortname)));
        $body .= "</courseId>\r\n";

        $body .= "\t\t<courseDescription>";
        $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($c->fullname)));
        $body .= "</courseDescription>\r\n";

        $body .= "\t</course>\r\n";
    }

    $body .= "</courseList>\r\n";

    lockdownbrowser_monitorserviceresponse("text/xml", $body, true);
}

function lockdownbrowser_monitorfloatcompare($f1, $f2, $precision) {

    if (function_exists("bccomp")) {
        return bccomp($f1, $f2, $precision);
    }

    if ($precision < 0) {
        $precision = 0;
    }

    $epsilon = 1 / pow(10, $precision);
    $diff    = ($f1 - $f2);

    if (abs($diff) < $epsilon) {
        return 0;
    } else if ($diff < 0) {
        return -1;
    } else {
        return 1;
    }
}

function lockdownbrowser_monitortemppath() {

    global $CFG;

    if (lockdownbrowser_monitorfloatcompare(
            $CFG->version, 2011120500.00, 2) >= 0
    ) {
        // Moodle 2.2.0+
        if (isset($CFG->tempdir)) {
            $path = "$CFG->tempdir";
        } else {
            $path = "$CFG->dataroot/temp";
        }
    } else {
        // Moodle 2.0.x - 2.1.x
        $path = "$CFG->dataroot/temp";
    }

    return $path;
}

function lockdownbrowser_monitorlog($msg) {

    if (LOCKDOWNBROWSER_MONITOR_ENABLE_LOG) {
        $entry  = date("m-d-Y H:i:s") . " - " . $msg . "\r\n";
        $path   = lockdownbrowser_monitortemppath()
            . "/" . LOCKDOWNBROWSER_MONITOR_LOG;
        $handle = fopen($path, "ab");
        if ($handle !== false) {
            fwrite($handle, $entry, strlen($entry));
            fclose($handle);
        }
    }
}

function lockdownbrowser_monitorexceptionhandler($ex) {

    abort_all_db_transactions();

    $info = get_exception_info($ex);

    $msg = "\r\n-- Exception occurred --"
        . "\r\nmessage: $info->message"
        . "\r\nerrorcode: $info->errorcode"
        . "\r\nfile: " . $ex->getFile()
        . "\r\nline: " . $ex->getLine()
        . "\r\nlink: $info->link"
        . "\r\nmoreinfourl: $info->moreinfourl"
        . "\r\na: $info->a"
        . "\r\ndebuginfo: $info->debuginfo\r\n";

    lockdownbrowser_monitorlog($msg);
    lockdownbrowser_monitorlog("\r\nstacktrace: " . $ex->getTraceAsString());

    lockdownbrowser_monitorserviceerror(2003, "A Moodle or PHP server exception occurred: $info->errorcode");
}

function lockdownbrowser_monitorrequestparameters() {

    $parameters     = array();
    $request_method = $_SERVER["REQUEST_METHOD"];

    if ($request_method == "GET") {

        if (!isset($_GET["rp"])) { // direct access only for existence check
            lockdownbrowser_monitorserviceerror(2012, "No request parameters found");
        }

        $cleaned = optional_param("rp", false, PARAM_ALPHANUMEXT);
        if ($cleaned == "ping") { // unencrypted presence check
            lockdownbrowser_monitorserviceresponse("text/plain", "OK", false, false);
        }
        if ($cleaned == "log") { // get debug log contents
            if (LOCKDOWNBROWSER_MONITOR_ENABLE_LOG) {
                $path = lockdownbrowser_monitortemppath() . "/" . LOCKDOWNBROWSER_MONITOR_LOG;
                $log = file_get_contents($path);
                if ($log === false) {
                    $log = "Cannot read log file: $path";
                }
                lockdownbrowser_monitorserviceresponse("text/plain", $log, false, false);
            }
        }
        if ($cleaned == "phpinfo") { // get PHP info
            if (LOCKDOWNBROWSER_MONITOR_ENABLE_PHPINFO) {
                phpinfo();
                exit;
            }
        }

        $cleaned = optional_param("rp", false, PARAM_NOTAGS); // cannot use PARAM_BASE64
        if ($cleaned === false) {
            lockdownbrowser_monitorserviceerror(2012, "No request parameters found");
        }
    } else if ($request_method == "POST") {

        if (isset($_POST["rp"])) { // direct access only for existence check

            $cleaned = optional_param("rp", false, PARAM_ALPHANUMEXT);
            if ($cleaned == "ping") { // unencrypted presence check
                lockdownbrowser_monitorserviceresponse("text/plain", "OK", false, false);
            }
            if ($cleaned == "log") { // get debug log contents
                if (LOCKDOWNBROWSER_MONITOR_ENABLE_LOG) {
                    $path = lockdownbrowser_monitortemppath() . "/" . LOCKDOWNBROWSER_MONITOR_LOG;
                    $log = file_get_contents($path);
                    if ($log === false) {
                        $log = "Cannot read log file: $path";
                    }
                    lockdownbrowser_monitorserviceresponse("text/plain", $log, false, false);
                }
            }

            $cleaned = optional_param("rp", false, PARAM_NOTAGS); // cannot use PARAM_BASE64
            if ($cleaned === false) {
                lockdownbrowser_monitorserviceerror(2012, "No request parameters found");
            }
        } else { // direct access only for length check and url-decoding

            $body = file_get_contents("php://input");
            if (strlen($body) == 0) {
                lockdownbrowser_monitorserviceerror(2012, "No request parameters found");
            }

            $decoded = urldecode($body);

            $cleaned = clean_param($decoded, false, PARAM_ALPHANUMEXT);
            if ($cleaned == "ping") { // unencrypted presence check
                lockdownbrowser_monitorserviceresponse("text/plain", "OK", false, false);
            }
            if ($cleaned == "log") { // get debug log contents
                if (LOCKDOWNBROWSER_MONITOR_ENABLE_LOG) {
                    $path = lockdownbrowser_monitortemppath() . "/" . LOCKDOWNBROWSER_MONITOR_LOG;
                    $log = file_get_contents($path);
                    if ($log === false) {
                        $log = "Cannot read log file: $path";
                    }
                    lockdownbrowser_monitorserviceresponse("text/plain", $log, false, false);
                }
            }

            $cleaned = clean_param($decoded, PARAM_NOTAGS); // cannot use PARAM_BASE64
            if ($cleaned === false) {
                lockdownbrowser_monitorserviceerror(2012, "No request parameters found");
            }
        }
    } else {
        lockdownbrowser_monitorserviceerror(2017, "Unsupported request method: $request_method");
    }

    // parse encrypted parameters
    $decrypted = lockdownbrowser_monitorbase64decrypt($cleaned, false);
    lockdownbrowser_monitorlog("service request: " . $decrypted);
    $nvpairs = explode("&", $decrypted);
    foreach ($nvpairs as $pair) {
        $parts = explode("=", $pair);
        $name  = urldecode($parts[0]);
        if (count($parts) == 2) {
            $value = urldecode($parts[1]);
        } else {
            $value = "";
        }
        $parameters[$name] = $value;
    }

    // check for mac (assumed to succeed other parameters)
    $pos2 = strpos($decrypted, "&mac2=");
    $pos = strpos($decrypted, "&mac=");
    if ($pos2 !== false
        && isset($parameters["mac2"])
        && strlen($parameters["mac2"]) > 0
    ) {
        // new-style mac;
        // variations needed for compatibility with various client versions
        if ($pos !== false && $pos < $pos2) {
            $sign1 = substr($decrypted, 0, $pos);
            $sign2 = substr($decrypted, 0, $pos2);
            $mac11 = lockdownbrowser_monitorgeneratemac2($sign1, 1);
            $mac12 = lockdownbrowser_monitorgeneratemac2($sign1, 2);
            $mac21 = lockdownbrowser_monitorgeneratemac2($sign2, 1);
            $mac22 = lockdownbrowser_monitorgeneratemac2($sign2, 2);
            if (strcmp($mac11, $parameters["mac2"]) != 0
                && strcmp($mac12, $parameters["mac2"]) != 0
                && strcmp($mac21, $parameters["mac2"]) != 0
                && strcmp($mac22, $parameters["mac2"]) != 0
                ) {
                lockdownbrowser_monitorserviceerror(2010, "Invalid MAC in request");
            }
        } else {
            $sign = substr($decrypted, 0, $pos2);
            $mac21 = lockdownbrowser_monitorgeneratemac2($sign, 1);
            $mac22 = lockdownbrowser_monitorgeneratemac2($sign, 2);
            if (strcmp($mac21, $parameters["mac2"]) != 0
                && strcmp($mac22, $parameters["mac2"]) != 0
                ) {
                lockdownbrowser_monitorserviceerror(2010, "Invalid MAC in request");
            }
        }
    } else if ($pos !== false
        && isset($parameters["mac"])
        && strlen($parameters["mac"]) > 0
    ) {
        // old-style mac
        $sign = substr($decrypted, 0, $pos);
        $mac = lockdownbrowser_monitorgeneratemac($sign);
        if (strcmp($mac, $parameters["mac"]) != 0) {
            lockdownbrowser_monitorserviceerror(2010, "Invalid MAC in request");
        }
    } else {
       lockdownbrowser_monitorserviceerror(2011, "MAC not found in request");
     }

    return $parameters;
}

function lockdownbrowser_monitorgeneratemac($input) {

    // old-style mac;
    // broken for characters > 127 between Respondus servers and clients
    $secret = lockdownbrowser_monitorsharedsecret(false);

    $chararray = preg_split('//', $input, -1, PREG_SPLIT_NO_EMPTY);

    $strdatavalue = 0;
    foreach ($chararray as $char) {
        $strdatavalue += ord($char);
    }

    return md5($strdatavalue . $secret);
}

function lockdownbrowser_monitorgeneratemac2($input, $style) {

    // new-style mac
    $secret = lockdownbrowser_monitorsharedsecret(false);
    if ($style == 1) {
        // need leading underscore so server can differentiate from old-style mac
        $mac = "_" . md5($input . $secret);
    } else if ($style == 2) {
        $mac = md5($input . $secret);
    }
    return $mac;
}

function lockdownbrowser_monitorbase64encrypt($input, $silent) {

    $secret = lockdownbrowser_monitorsharedsecret($silent);
    if (is_null($secret)) {
        return null;
    }

    $encrypted = Blowfish::encrypt($input, $secret, Blowfish::BLOWFISH_MODE_ECB, Blowfish::BLOWFISH_PADDING_ZERO);

    $b64_encoded = base64_encode($encrypted);

    return $b64_encoded;
}

function lockdownbrowser_monitorbase64decrypt($input, $silent) {

    $b64_decoded = base64_decode($input, true);

    if ($b64_decoded === false) {
        if ($silent === false) {
            lockdownbrowser_monitorserviceerror(2007, "Invalid base64 encoding of input data");
        } else {
            return null;
        }
    }

    $secret = lockdownbrowser_monitorsharedsecret($silent);
    if (is_null($secret)) {
        return null;
    }

    $decrypted = Blowfish::decrypt($b64_decoded, $secret, Blowfish::BLOWFISH_MODE_ECB, Blowfish::BLOWFISH_PADDING_ZERO);

    return trim($decrypted);
}

function lockdownbrowser_monitorsharedsecret($silent) {

    global $CFG;

    if (!isset($CFG->block_lockdownbrowser_ldb_serversecret)
        || strlen($CFG->block_lockdownbrowser_ldb_serversecret) == 0
    ) {
        if ($silent === false) {
            lockdownbrowser_monitorserviceerror(2009, "Shared secret not found in settings", false);
        } else {
            return null;
        }
    }

    $secret = $CFG->block_lockdownbrowser_ldb_serversecret;

    return $secret;
}

function lockdownbrowser_monitorredeemtoken($parameters) {

    global $CFG;

    if (!isset($parameters["token"]) || strlen($parameters["token"]) == 0) {
        lockdownbrowser_monitorserviceerror(2018, "Login token not found in request");
    }
    $token = $parameters["token"];

    if (!isset($CFG->block_lockdownbrowser_ldb_serverid)
        || strlen($CFG->block_lockdownbrowser_ldb_serverid) == 0
    ) {
        lockdownbrowser_monitorserviceerror(2019, "Institution ID not found in settings");
    }
    $institution_id = $CFG->block_lockdownbrowser_ldb_serverid;

    if (!isset($CFG->block_lockdownbrowser_ldb_servername)
        || strlen($CFG->block_lockdownbrowser_ldb_servername) == 0
    ) {
        lockdownbrowser_monitorserviceerror(2037, "Server name not found in settings");
    }
    $server_name = $CFG->block_lockdownbrowser_ldb_servername;

    $redeem_time = time();

    $redeem_mac  = lockdownbrowser_monitorgeneratemac2(
        urldecode($institution_id) . urldecode($server_name) . $token . $redeem_time,
        1 // server request hash needs leading underscore
    );

    // we assume https, so no additional encryption is used

    $url = LOCKDOWNBROWSER_MONITOR_REDEEMURL
        . "?institutionId=" . $institution_id // assume url-encoded
        . "&serverName=" . $server_name // assume url-encoded
        . "&token=" . urlencode($token)
        . "&time=" . urlencode($redeem_time)
        . "&mac=" . urlencode($redeem_mac);

    if (!extension_loaded("curl")) {
        lockdownbrowser_monitorserviceerror(2020, "The curl library is not loaded");
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $info   = curl_getinfo($ch);
    $error     = curl_error($ch); // Trac #4977
    curl_close($ch);

    if ($result == false) { // Trac #4977
        lockdownbrowser_monitorserviceerror(2059, $error);
    }
    if ($info["http_code"] != 200) {
        lockdownbrowser_monitorserviceerror(2021, "Could not redeem login token");
    }

    $receipt_mac = lockdownbrowser_monitorgeneratemac2(
        $token . urldecode($server_name) . urldecode($institution_id) . $redeem_time,
        2 // server response hash doesn't include leading underscore
    );

    if (strcmp($result, $receipt_mac) != 0) {
        lockdownbrowser_monitorserviceerror(2022, "Received invalid token receipt");
    }
}

function lockdownbrowser_monitoractionlogin($parameters) {

    global $CFG;
    global $lockdownbrowser_disable_callbacks;

    if (isloggedin()) {
        lockdownbrowser_monitorserviceerror(2015, "Session is already logged in");
    }

    if (LOCKDOWNBROWSER_OPTIONAL_CALLBACKS) {
        if (!isset($parameters["token"]) || strlen($parameters["token"]) == 0) {
            $lockdownbrowser_disable_callbacks = true;
        }
    }
    if (!$lockdownbrowser_disable_callbacks) {
        lockdownbrowser_monitorredeemtoken($parameters);
    }

    if (!LOCKDOWNBROWSER_IGNORE_HTTPS_LOGIN) {
        if ($CFG->loginhttps && !$CFG->sslproxy) {
            if (!isset($_SERVER["HTTPS"])
                || empty($_SERVER["HTTPS"])
                || strcasecmp($_SERVER["HTTPS"], "off") == 0
            ) {
                lockdownbrowser_monitorserviceerror(2016, "HTTPS is required");
            }
        }
    }

    if (!isset($CFG->block_lockdownbrowser_monitor_username)
        || strlen($CFG->block_lockdownbrowser_monitor_username) == 0
        || !isset($CFG->block_lockdownbrowser_monitor_password)
        || strlen($CFG->block_lockdownbrowser_monitor_password) == 0
    ) {
        lockdownbrowser_monitorserviceerror(2014, "Login info not found in settings");
    }

    $user = authenticate_user_login(
        $CFG->block_lockdownbrowser_monitor_username,
        $CFG->block_lockdownbrowser_monitor_password
    );
    if ($user) {
        complete_user_login($user);
    }

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2013, "Login attempt failed");
    }

    lockdownbrowser_monitorservicestatus(1002, "Login succeeded");
}

function lockdownbrowser_monitoractionuserlogin($parameters) {

    global $CFG;

    if (isloggedin()) {
        lockdownbrowser_monitorserviceerror(2015, "Session is already logged in");
    }
    if (!isset($parameters["username"]) || strlen($parameters["username"]) == 0) {
        lockdownbrowser_monitorserviceerror(2031, "No username was specified");
    }
    if (!isset($parameters["password"]) || strlen($parameters["password"]) == 0) {
        lockdownbrowser_monitorserviceerror(2032, "No password was specified");
    }

    $username = $parameters["username"];
    $password = $parameters["password"];

    if (!LOCKDOWNBROWSER_IGNORE_HTTPS_LOGIN) {
        if ($CFG->loginhttps && !$CFG->sslproxy) {
            if (!isset($_SERVER["HTTPS"])
                || empty($_SERVER["HTTPS"])
                || strcasecmp($_SERVER["HTTPS"], "off") == 0
            ) {
                lockdownbrowser_monitorserviceerror(2016, "HTTPS is required");
            }
        }
    }

    $user = authenticate_user_login($username, $password);
    if ($user) {
        complete_user_login($user);
    }

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2013, "Login attempt failed");
    }

    lockdownbrowser_monitorservicestatus(1002, "Login succeeded");
}

function lockdownbrowser_monitoractionlogout($parameters) {

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }

    require_logout();

    lockdownbrowser_monitorservicestatus(1001, "Logout succeeded");
}

function lockdownbrowser_monitoractionchangesettings($parameters) {

    global $DB;

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }
    if (!is_siteadmin()) {
        lockdownbrowser_monitorserviceerror(2024, "Must be logged in as admin to perform the requested action");
    }
    if (!isset($parameters["courseRefId"]) || strlen($parameters["courseRefId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2025, "No courseRefId parameter was specified");
    }
    if (!isset($parameters["examId"]) || strlen($parameters["examId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2026, "No examId parameter was specified");
    }
    if (!isset($parameters["enableLDB"]) || strlen($parameters["enableLDB"]) == 0) {
        lockdownbrowser_monitorserviceerror(2040, "No enableLDB parameter was specified");
    }
    if (!isset($parameters["enableMonitor"]) || strlen($parameters["enableMonitor"]) == 0) {
        lockdownbrowser_monitorserviceerror(2041, "No enableMonitor parameter was specified");
    }
    if (!isset($parameters["exitPassword"])) {
        lockdownbrowser_monitorserviceerror(2042, "No exitPassword parameter was specified");
    }
    if (!isset($parameters["xdata"])) {
        lockdownbrowser_monitorserviceerror(2043, "No xdata parameter was specified");
    }

    $course_id = intval($parameters["courseRefId"]);
    $exam_id   = intval($parameters["examId"]);

    $enable_ldb = $parameters["enableLDB"];
    if ($enable_ldb == "0" || strcasecmp($enable_ldb, "false") == 0) {
        $enable_ldb = false;
    } else {
        $enable_ldb = true;
    }

    $enable_monitor = $parameters["enableMonitor"];
    if ($enable_monitor == "0" || strcasecmp($enable_monitor, "false") == 0) {
        $enable_monitor = false;
    } else {
        $enable_monitor = true;
    }

    $exit_password = $parameters["exitPassword"];
    $xdata         = $parameters["xdata"];

    /*** Trac #4594
    if ($enable_monitor) {
        $monitor = $xdata;
    } else {
        $monitor = "";
    }
    ***/
    if ($enable_monitor) {
        $monitor = "monitorEnabled\$%@%\$true\n " . $xdata;
    } else {
        $monitor = "monitorEnabled\$%@%\$false\n " . $xdata;
    }

    $course_module = $DB->get_record("course_modules", array("id" => $exam_id));
    if ($course_module === false) {
        lockdownbrowser_monitorserviceerror(2027, "The specified examId is invalid: $exam_id");
    }

    $modrec = $DB->get_record("modules", array("id" => $course_module->module));
    if ($modrec === false) {
        lockdownbrowser_monitorserviceerror(2034, "Could not find the specified quiz (module error)");
    }

    $quiz = $DB->get_record($modrec->name, array("id" => $course_module->instance));
    if ($quiz === false) {
        lockdownbrowser_monitorserviceerror(2035, "Could not find the specified quiz (instance error)");
    }

    // Moodle browser security
    //   popup (0=none, 1=full screen pop-up with some JavaScript security)
    // Moodle 2.2.0+ (quiz module 2011100600+)
    //   browsersecurity ('-', 'securewindow', 'safebrowser')
    // if these settings are not managed, it will interfere with the LDB integration
    if ($enable_ldb) {
        $quiz->popup = 0;
        $quiz->browsersecurity = get_string("browsersecuritychoicekey", "block_lockdownbrowser");
    }
    else {
        $quiz->browsersecurity = "-";
    }

    $ldb_decoration     = get_string("requires_ldb", "block_lockdownbrowser");
    $monitor_decoration = get_string("requires_webcam", "block_lockdownbrowser");

    // must be in this order, since the first decoration usually contains the second
    $quiz->name = str_replace($monitor_decoration, "", $quiz->name);
    $quiz->name = str_replace($ldb_decoration, "", $quiz->name);

    if (!LOCKDOWNBROWSER_MONITOR_ENABLE_4851) {
        if ($enable_ldb) {
            if ($enable_monitor) {
                $quiz->name .= $monitor_decoration;
            } else {
                $quiz->name .= $ldb_decoration;
            }
        }
    }

    $settings = lockdownbrowser_get_quiz_options($quiz->id);

    if ($settings === false) {

        if ($enable_ldb) {
            $ok = lockdownbrowser_set_settings($quiz->id, 0, 0, $exit_password, $monitor);
            if (!$ok) {
                lockdownbrowser_monitorserviceerror(2036, "Quiz settings changes failed (block error)");
            }
        }
    } else { // settings found

        if ($enable_ldb) {
            $settings->password = $exit_password;
            $settings->monitor  = $monitor;
            $ok                 = lockdownbrowser_set_quiz_options($quiz->id, $settings);
            if (!$ok) {
                lockdownbrowser_monitorserviceerror(2036, "Quiz settings changes failed (block error)");
            }
        } else {
            lockdownbrowser_delete_options($quiz->id);
        }
    }

    $ok = $DB->update_record($modrec->name, $quiz);
    if (!$ok) {
        lockdownbrowser_monitorserviceerror(2036, "Quiz settings changes failed (module error)");
    }

    rebuild_course_cache($course_id);
    lockdownbrowser_monitorservicestatus(1003, "Quiz settings changes succeeded");
}

function lockdownbrowser_monitoractionexamroster($parameters) {

    global $CFG;
    global $DB;

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }
    if (!is_siteadmin()) {
        lockdownbrowser_monitorserviceerror(2024, "Must be logged in as admin to perform the requested action");
    }
    if (!isset($parameters["courseRefId"]) || strlen($parameters["courseRefId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2025, "No courseRefId parameter was specified");
    }
    if (!isset($parameters["examId"]) || strlen($parameters["examId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2026, "No examId parameter was specified");
    }
    // Trac #4179
    $username = "";
    if (isset($parameters["userId"]) && strlen($parameters["userId"]) > 0) {
        $username = $parameters["userId"]; // actually user login name
        lockdownbrowser_monitorlog("examroster username: " . $username);
    }

    $course_id = intval($parameters["courseRefId"]);
    $exam_id   = intval($parameters["examId"]);

    $course_module = $DB->get_record("course_modules", array("id" => $exam_id));
    if ($course_module === false) {
        lockdownbrowser_monitorserviceerror(2027, "The specified examId is invalid: $exam_id");
    }
    $quiz_id = $course_module->instance;

    if (lockdownbrowser_monitorfloatcompare($CFG->version, 2013111800, 2) >= 0) {
        // Moodle 2.6.0+.
        $context = context_course::instance($course_id);
    } else {
        // Prior to Moodle 2.6.0.
        $context = get_context_instance(CONTEXT_COURSE, $course_id);
    }
    if ($context === false) {
        lockdownbrowser_monitorserviceerror(2028, "The specified courseRefId is invalid: $course_id");
    }

    $roles = $DB->get_records("role", array("archetype" => "student"));
    if ($roles === false || count($roles) == 0) {
        lockdownbrowser_monitorserviceerror(2029, "The role archetype 'student' was not found");
    }
    $students = array();
    foreach ($roles as $role) {
        $users = get_role_users($role->id, $context);
        if ($users !== false && count($users) > 0) {
            $students = array_merge($students, $users);
        }
    }
    // Trac #4179
    if (!empty($username)) {
        // check for non-editing teacher
        $teacher_id = 0;
        $teachers = array();
        $roles = $DB->get_records("role", array("archetype" => "teacher")); // non-editing
        if ($roles === false || count($roles) == 0) {
            lockdownbrowser_monitorserviceerror(2048, "The role archetype 'teacher' was not found");
        }
        foreach ($roles as $role) {
            $users = get_role_users($role->id, $context);
            if ($users !== false && count($users) > 0) {
                $teachers = array_merge($teachers, $users);
            }
        }
        if (count($teachers) > 0) {
            foreach ($teachers as $t) {
                if (strcasecmp($username, $t->username) == 0) {
                    $teacher_id = $t->id;
                    lockdownbrowser_monitorlog("examroster teacher id: " . $teacher_id);
                    break;
                }
            }
        }
        // get teacher groups
        $ok = ($teacher_id != 0);
        $teacher_groups = array();
        if ($ok) {
            $teacher_groups = groups_get_all_groups($course_id, $teacher_id);
            $ok = ($teacher_groups !== false && count($teacher_groups) > 0);
        } else {
            // roster will not be filtered
            lockdownbrowser_monitorlog("examroster username is not a non-editing teacher");
        }
        // get students sharing any group with teacher
        $filtered_students = array();
        if ($ok) {
            foreach ($teacher_groups as $group) {
                $group_students = array();
                foreach ($students as $student) {
                    if(groups_is_member($group->id, $student->id)){
                        $group_students[] = $student;
                    }
                }
                $filtered_students = array_merge($filtered_students, $group_students);
            }
            $ok = ($filtered_students !== false && count($filtered_students) > 0);
        } else {
            // roster will not be filtered
            lockdownbrowser_monitorlog("examroster no groups found in course for teacher id");
        }
        if ($ok) {
            // at least one student shares membership with teacher in at least one group
            $students = $filtered_students;
            lockdownbrowser_monitorlog("examroster will be filtered based on teacher/student group memberships");
        } else {
            // roster will not be filtered
            lockdownbrowser_monitorlog("examroster no students in course share any group membership with teacher id");
        }
    }

    $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";

    if ($students === false || count($students) == 0) {
        $body .= "<studentList />\r\n";
        lockdownbrowser_monitorserviceresponse("text/xml", $body, true);
    }

    $body .= "<studentList>\r\n";

    foreach ($students as $s) {
        $body .= "\t<student>\r\n";

        $body .= "\t\t<userName>";
        $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($s->username)));
        $body .= "</userName>\r\n";

        $body .= "\t\t<firstName>";
        $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($s->firstname)));
        $body .= "</firstName>\r\n";

        $body .= "\t\t<lastName>";
        $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($s->lastname)));
        $body .= "</lastName>\r\n";

        $grade_info = grade_get_grades(
            $course_id, "mod", "quiz", $quiz_id, $s->id
        );
        if (!empty($grade_info)
            && !empty($grade_info->items)
            && !empty($grade_info->items[0]->grades)
            && !empty($grade_info->items[0]->grades[$s->id])
            && !empty($grade_info->items[0]->grades[$s->id]->grade)
        ) {
            $grade = $grade_info->items[0]->grades[$s->id]->str_grade;
            $body .= "\t\t<grade>";
            $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($grade)));
            $body .= "</grade>\r\n";
        }

        $body .= "\t</student>\r\n";
    }

    $body .= "</studentList>\r\n";

    lockdownbrowser_monitorserviceresponse("text/xml", $body, true);
}

function lockdownbrowser_monitoractionuserinfo2($parameters) {

    global $USER;

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }

    $body = $USER->username . "\$%\$"
        . $USER->lastname . "\$%\$"
        . $USER->firstname;

    lockdownbrowser_monitorserviceresponse("text/plain", $body, true);
}

function lockdownbrowser_monitoractionusercourselist($parameters) {

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }

    $courses = enrol_get_my_courses();
    if ($courses === false) {
        $courses = array();
    }

    $c2 = array();
    foreach ($courses as $c) {
        if ($c->id != SITEID) {
            $c2[] = $c;
        }
    }
    $courses = $c2;

    lockdownbrowser_monitorcourselistresponse($courses);
}

function lockdownbrowser_monitoractionexaminfo2($parameters) {

    global $DB;

    // login not required

    if (!isset($parameters["courseRefId"]) || strlen($parameters["courseRefId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2025, "No courseRefId parameter was specified");
    }
    if (!isset($parameters["examId"]) || strlen($parameters["examId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2026, "No examId parameter was specified");
    }

    $course_id = intval($parameters["courseRefId"]);
    $exam_id   = intval($parameters["examId"]);

    $course_module = $DB->get_record("course_modules", array("id" => $exam_id));
    if ($course_module === false) {
        lockdownbrowser_monitorserviceerror(2027, "The specified examId is invalid: $exam_id");
    }

    $modrec = $DB->get_record("modules", array("id" => $course_module->module));
    if ($modrec === false) {
        lockdownbrowser_monitorserviceerror(2034, "Could not find the specified quiz (module error)");
    }

    $quiz = $DB->get_record($modrec->name, array("id" => $course_module->instance));
    if ($quiz === false) {
        lockdownbrowser_monitorserviceerror(2035, "Could not find the specified quiz (instance error)");
    }

    $settings = lockdownbrowser_get_quiz_options($quiz->id);

    if ($settings === false
        || !isset($settings->password)
        || is_null($settings->password)
        || strlen($settings->password) == 0
    ) {
        $exit_pass_exists = "N";
        $exit_password    = "";
    } else {
        $exit_pass_exists = "Y";
        $exit_password    = $settings->password;
    }

    $body = "NONE\$:\$N\$:\$"
        . $exit_pass_exists
        . "\$:\$"
        . $exit_password
        . "\$:\$N\$:\$\$:\$"
        . $quiz->name;

    lockdownbrowser_monitorserviceresponse("text/plain", $body, true);
}

function lockdownbrowser_monitoractionexamsync($parameters) {

    global $DB;

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }
    if (!is_siteadmin()) {
        lockdownbrowser_monitorserviceerror(2024, "Must be logged in as admin to perform the requested action");
    }
    if (!isset($parameters["courseRefId"]) || strlen($parameters["courseRefId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2025, "No courseRefId parameter was specified");
    }

    $course_id = intval($parameters["courseRefId"]);

    $coursemodules = get_coursemodules_in_course("quiz", $course_id);
    if ($coursemodules === false) {
        $coursemodules = array();
    }

    $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";

    if (empty($coursemodules)) {
        $body .= "<assessmentList />\r\n";
        lockdownbrowser_monitorserviceresponse("text/xml", $body, true);
    }

    $body .= "<assessmentList>\r\n";

    foreach ($coursemodules as $cm) {

        $modrec = $DB->get_record("modules", array("id" => $cm->module));
        if ($modrec === false) {
            continue;
        }

        $quiz = $DB->get_record($modrec->name, array("id" => $cm->instance));
        if ($quiz === false) {
            continue;
        }

        $body .= "\t<assessment>\r\n";

        $body .= "\t\t<id>";
        $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($cm->id)));
        $body .= "</id>\r\n";

        $body .= "\t\t<title>";
        $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($cm->name)));
        $body .= "</title>\r\n";

        $settings = lockdownbrowser_get_quiz_options($cm->instance);

        if ($settings !== false) {
            $body .= "\t\t<ldbEnabled>true</ldbEnabled>\r\n";
        } else {
            $body .= "\t\t<ldbEnabled>false</ldbEnabled>\r\n";
        }

        if ($settings !== false
            && isset($settings->password)
            && !is_null($settings->password)
            && strlen($settings->password) > 0
        ) {
            $body .= "\t\t<exitPassword>";
            $body .= lockdownbrowser_utf8encode(htmlspecialchars($settings->password));
            $body .= "</exitPassword>\r\n";
        }

        if ($settings !== false
            && isset($settings->monitor)
            && !is_null($settings->monitor)
            && strlen($settings->monitor) > 0
        ) {
            /*** Trac #4594
            $body .= "\t\t<monitorEnabled>true</monitorEnabled>\r\n";
            $body .= "\t\t<extendedData>";
            $body .= lockdownbrowser_utf8encode(htmlspecialchars($settings->monitor));
            $body .= "</extendedData>\r\n";
            ***/
            $parts = explode("\n ", $settings->monitor, 2);
            $nvpair = explode("\$%@%\$", $parts[0], 2);
            if ($nvpair[0] == "monitorEnabled") {
                $enable_monitor = $nvpair[1];
                $xdata = $parts[1];
            } else { // assume Monitor is enabled
                $enable_monitor = "true";
                $xdata = $settings->monitor;
            }
            $body .= "\t\t<monitorEnabled>";
            $body .= lockdownbrowser_utf8encode(htmlspecialchars($enable_monitor));
            $body .= "</monitorEnabled>\r\n";
            $body .= "\t\t<extendedData>";
            $body .= lockdownbrowser_utf8encode(htmlspecialchars($xdata));
            $body .= "</extendedData>\r\n";
        } else {
            $body .= "\t\t<monitorEnabled>false</monitorEnabled>\r\n";
        }

        // Moodle browser security;
        // see lockdownbrowser_monitoractionchangesettings
        if (isset($quiz->browsersecurity)) {
            $body .= "\t\t<browserSecurity>";
            $body .= lockdownbrowser_utf8encode(htmlspecialchars($quiz->browsersecurity));
            $body .=  "</browserSecurity>\r\n";
        } else {
            $body .= "\t\t<browserSecurity>-</browserSecurity>\r\n";
        }

        if ($quiz->popup != 0) {
            $launch_in_new_window = true;
        } else {
            $launch_in_new_window = false;
        }

        if ($launch_in_new_window) {
            $body .= "\t\t<launchInNewWindow>true</launchInNewWindow>\r\n";
        } else {
            $body .= "\t\t<launchInNewWindow>false</launchInNewWindow>\r\n";
        }

        if ($settings !== false && $launch_in_new_window) {
            $body .= "\t\t<ok>false</ok>\r\n";
        } else {
            $body .= "\t\t<ok>true</ok>\r\n";
        }

        $body .= "\t</assessment>\r\n";
    }

    $body .= "</assessmentList>\r\n";

    lockdownbrowser_monitorserviceresponse("text/xml", $body, true);
}

function lockdownbrowser_monitoractionversioninfo($parameters) {

    global $CFG;

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }

    $moodle_release = $CFG->release;
    $moodle_version = $CFG->version;

    $version_file = "$CFG->dirroot/blocks/lockdownbrowser/version.php";
    if (is_readable($version_file)) {
        include($version_file);
    } else {
        lockdownbrowser_monitorserviceerror(2038, "Block version file not found");
    }

    if (!isset($plugin->version)) {
        lockdownbrowser_monitorserviceerror(2039, "Block version info missing");
    }

    $block_version = $plugin->version;

    $body = $moodle_release . "\$%\$" . $moodle_version . "\$%\$" . $block_version;

    if (LOCKDOWNBROWSER_MONITOR_ENABLE_4851) {
        $body .= "\$%\$true";
    } else {
        $body .= "\$%\$false";
    }

    lockdownbrowser_monitorserviceresponse("text/plain", $body, true);
}

function lockdownbrowser_monitoractionusercourserole($parameters) {

    global $CFG;
    global $DB;

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }
    if (!is_siteadmin()) {
        lockdownbrowser_monitorserviceerror(2024, "Must be logged in as admin to perform the requested action");
    }
    if (!isset($parameters["courseRefId"]) || strlen($parameters["courseRefId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2025, "No courseRefId parameter was specified");
    }
    if (!isset($parameters["userId"]) || strlen($parameters["userId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2044, "No userId parameter was specified");
    }

    $course_id = intval($parameters["courseRefId"]);
    $username  = $parameters["userId"]; // actually user login name

    if (lockdownbrowser_monitorfloatcompare($CFG->version, 2013111800, 2) >= 0) {
        // Moodle 2.6.0+.
        $context = context_course::instance($course_id);
    } else {
        // Prior to Moodle 2.6.0.
        $context = get_context_instance(CONTEXT_COURSE, $course_id);
    }
    if ($context === false) {
        lockdownbrowser_monitorserviceerror(2028, "The specified courseRefId is invalid: $course_id");
    }

    $body = "";

    if (strlen($body) == 0) { // check managers
        $managers = array();
        $roles    = $DB->get_records("role", array("archetype" => "manager"));
        if ($roles === false || count($roles) == 0) {
            lockdownbrowser_monitorserviceerror(2045, "The role archetype 'manager' was not found");
        }
        foreach ($roles as $role) {
            $users = get_role_users($role->id, $context);
            if ($users !== false && count($users) > 0) {
                $managers = array_merge($managers, $users);
            }
        }
        if (count($managers) > 0) {
            foreach ($managers as $m) {
                if (strcasecmp($username, $m->username) == 0) {
                    $body = "ADMIN";
                    break;
                }
            }
        }
    }

    if (strlen($body) == 0) { // check editing teachers
        $editingteachers = array();
        $roles           = $DB->get_records("role", array("archetype" => "editingteacher"));
        if ($roles === false || count($roles) == 0) {
            lockdownbrowser_monitorserviceerror(2047, "The role archetype 'editingteacher' was not found");
        }
        foreach ($roles as $role) {
            $users = get_role_users($role->id, $context);
            if ($users !== false && count($users) > 0) {
                $editingteachers = array_merge($editingteachers, $users);
            }
        }
        if (count($editingteachers) > 0) {
            foreach ($editingteachers as $et) {
                if (strcasecmp($username, $et->username) == 0) {
                    $body = "INSTRUCTOR";
                    break;
                }
            }
        }
    }

    if (strlen($body) == 0) { // check non-editing teachers
        $teachers = array();
        $roles    = $DB->get_records("role", array("archetype" => "teacher"));
        if ($roles === false || count($roles) == 0) {
            lockdownbrowser_monitorserviceerror(2048, "The role archetype 'teacher' was not found");
        }
        foreach ($roles as $role) {
            $users = get_role_users($role->id, $context);
            if ($users !== false && count($users) > 0) {
                $teachers = array_merge($teachers, $users);
            }
        }
        if (count($teachers) > 0) {
            foreach ($teachers as $t) {
                if (strcasecmp($username, $t->username) == 0) {
                    $body = "STUDENT";
                    break;
                }
            }
        }
    }

    if (strlen($body) == 0) { // check students
        $students = array();
        $roles    = $DB->get_records("role", array("archetype" => "student"));
        if ($roles === false || count($roles) == 0) {
            lockdownbrowser_monitorserviceerror(2029, "The role archetype 'student' was not found");
        }
        foreach ($roles as $role) {
            $users = get_role_users($role->id, $context);
            if ($users !== false && count($users) > 0) {
                $students = array_merge($students, $users);
            }
        }
        if (count($students) > 0) {
            foreach ($students as $s) {
                if (strcasecmp($username, $s->username) == 0) {
                    $body = "STUDENT";
                    break;
                }
            }
        }
    }

    if (strlen($body) == 0) {
        lockdownbrowser_monitorserviceerror(2049,
          "The specified userId does not have at least STUDENT access to the specified course.");
    }

    lockdownbrowser_monitorserviceresponse("text/plain", $body, true);
}

function lockdownbrowser_monitoractionretrievecourse($parameters) {

    global $DB;

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }
    if (!is_siteadmin()) {
        lockdownbrowser_monitorserviceerror(2024, "Must be logged in as admin to perform the requested action");
    }
    if (!isset($parameters["courseRefId"]) || strlen($parameters["courseRefId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2025, "No courseRefId parameter was specified");
    }

    $course_id = intval($parameters["courseRefId"]);

    $record = $DB->get_record("course", array("id" => $course_id));
    if ($record === false) {
        lockdownbrowser_monitorserviceerror(2028, "The specified courseRefId is invalid: $course_id");
    }

    $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";

    $body .= "\t<course>\r\n";

    $body .= "\t\t<courseRefId>";
    $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($record->id)));
    $body .= "</courseRefId>\r\n";

    $body .= "\t\t<courseId>";
    $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($record->shortname)));
    $body .= "</courseId>\r\n";

    $body .= "\t\t<courseDescription>";
    $body .= lockdownbrowser_utf8encode(htmlspecialchars(trim($record->fullname)));
    $body .= "</courseDescription>\r\n";

    $body .= "\t</course>\r\n";

    lockdownbrowser_monitorserviceresponse("text/xml", $body, true);
}

function lockdownbrowser_monitoractiontestintegration($parameters) {

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }
    if (!is_siteadmin()) {
        lockdownbrowser_monitorserviceerror(2024, "Must be logged in as admin to perform the requested action");
    }

    // currently no parameters are required; note that this call needs to remain context-free

    $result = lockdownbrowser_check_plugin_dependencies(0);
    if ($result !== false){
        lockdownbrowser_monitorserviceerror(2055, $result);
    }

    // currently no other testing is performed

    lockdownbrowser_monitorserviceresponse("text/plain", "OK", true);
}

function lockdownbrowser_monitoractionvalidateuserrole($parameters) {

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action", false);
    }

    global $USER;
       global $CFG;
    global $DB;

    if (!isset($parameters["courseRefId"]) || strlen($parameters["courseRefId"]) == 0) {
        lockdownbrowser_monitorserviceerror(2025, "No courseRefId parameter was specified");
    }
    if (!isset($parameters["key"]) || strlen($parameters["key"]) == 0) {
        lockdownbrowser_monitorserviceerror(2050, "No key parameter was specified");
    }
    if (!isset($parameters["callback"]) || strlen($parameters["callback"]) == 0) {
        lockdownbrowser_monitorserviceerror(2051, "No callback parameter was specified");
    }

    $course_id = intval($parameters["courseRefId"]);
    $username  = $USER->username;
    $key  = $parameters["key"];
    $callback  = $parameters["callback"];

    if (lockdownbrowser_monitorfloatcompare($CFG->version, 2013111800, 2) >= 0) {
        // Moodle 2.6.0+.
        $context = context_course::instance($course_id);
    } else {
        // Prior to Moodle 2.6.0.
        $context = get_context_instance(CONTEXT_COURSE, $course_id);
    }
    if ($context === false) {
        lockdownbrowser_monitorserviceerror(2028, "The specified courseRefId is invalid: $course_id");
    }

    $body = "NOT_AUTHORIZED";

    if ( has_capability('moodle/course:manageactivities', $context)
       || has_capability('moodle/course:viewhiddenactivities', $context) // Trac #3595
      ) {
        $body = "AUTHORIZED";
    }
    $encrypted = lockdownbrowser_monitorbase64encrypt($body, true);

    header("Location: " . $callback . "?key=" . $key . "&r=" . $encrypted );
    die();
}

function lockdownbrowser_monitoractionquestionmapping($parameters) {

    global $DB;

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }
    if (!is_siteadmin()) {
        lockdownbrowser_monitorserviceerror(2024, "Must be logged in as admin to perform the requested action");
    }
    if (!isset($parameters["data"])) {
        lockdownbrowser_monitorserviceerror(2052, "No data parameter was specified");
    }
    if (!isset($parameters["attemptid"])) {
        lockdownbrowser_monitorserviceerror(2058, "No attemptid parameter was specified");
    }

    $body = "";
    $quiz_attempt = $DB->get_record("quiz_attempts", array("id" => $parameters["attemptid"]), "uniqueid");

    $data_elements = explode(",", $parameters["data"]);
    foreach ($data_elements as $data_element) {

        $tokens = explode( ":", $data_element);
        if ( count($tokens) != 2 )
            lockdownbrowser_monitorserviceerror(2053, "Invalid format for question mapping data element");

        $slot = $tokens[1];
        $question_attempt = $DB->get_record("question_attempts", array("questionusageid" => $quiz_attempt->uniqueid, "slot" => $slot ), "questionid");

        if ( $question_attempt ) {

            $question = $DB->get_record("question", array("id" => $question_attempt->questionid), "questiontext");
            if ( $question ) {

                if (strlen($body) > 0)
                    $body .= ",";

                $body .= urlencode( $data_element );
                $body .= ":";
                $body .= $question_attempt->questionid;
                $body .= ":";
                $body .= urlencode( $question->questiontext );
            }
        }
    }

       lockdownbrowser_monitorserviceresponse("text/plain", $body, true);
}

function lockdownbrowser_monitoractionquestiondata($parameters) {

    global $DB;

    if (!isloggedin()) {
        lockdownbrowser_monitorserviceerror(2004, "Must be logged in to perform the requested action");
    }
    if (!is_siteadmin()) {
        lockdownbrowser_monitorserviceerror(2024, "Must be logged in as admin to perform the requested action");
    }
    if (!isset($parameters["qid"])) {
        lockdownbrowser_monitorserviceerror(2054, "No qid parameter was specified");
    }

    $qid = $parameters["qid"];
    $body = "";

    $question = $DB->get_record("question", array("id" => $qid), "name,questiontext,qtype");
    if ( $question ) {

        $question_answers = $DB->get_records("question_answers", array("question" => $qid));

        $body = json_encode( array(
            "name" => $question->name,
            "questiontext" => $question->questiontext,
            "qtype" => $question->qtype,
            "answers" => $question_answers
        ));
    }

       lockdownbrowser_monitorserviceresponse("application/json", $body, true);
}

function lockdownbrowser_monitorservicerequest() {

    $parameters = lockdownbrowser_monitorrequestparameters();

    if (!isset($parameters["action"]) || strlen($parameters["action"]) == 0) {
        lockdownbrowser_monitorserviceerror(2005, "No service action was specified");
    }
    $action = $parameters["action"];

    if ($action == "login") {
        lockdownbrowser_monitoractionlogin($parameters);
    } else if ($action == "userlogin") {
        lockdownbrowser_monitoractionuserlogin($parameters);
    } else if ($action == "logout") {
        lockdownbrowser_monitoractionlogout($parameters);
    } else if ($action == "changesettings") {
        lockdownbrowser_monitoractionchangesettings($parameters);
    } else if ($action == "examroster") {
        lockdownbrowser_monitoractionexamroster($parameters);
    } else if ($action == "userinfo2") {
        lockdownbrowser_monitoractionuserinfo2($parameters);
    } else if ($action == "usercourselist") {
        lockdownbrowser_monitoractionusercourselist($parameters);
    } else if ($action == "examinfo2") {
        lockdownbrowser_monitoractionexaminfo2($parameters);
    } else if ($action == "examsync") {
        lockdownbrowser_monitoractionexamsync($parameters);
    } else if ($action == "versioninfo") {
        lockdownbrowser_monitoractionversioninfo($parameters);
    } else if ($action == "usercourserole") {
        lockdownbrowser_monitoractionusercourserole($parameters);
    } else if ($action == "retrievecourse") {
        lockdownbrowser_monitoractionretrievecourse($parameters);
    } else if ($action == "testintegration") {
        lockdownbrowser_monitoractiontestintegration($parameters);
    } else if ($action == "validateuserrole") {
        lockdownbrowser_monitoractionvalidateuserrole($parameters);
    } else if ($action == "questionmapping") {
        lockdownbrowser_monitoractionquestionmapping($parameters);
    } else if ($action == "questiondata") {
        lockdownbrowser_monitoractionquestiondata($parameters);
    } else {
        lockdownbrowser_monitorserviceerror(2006, "Unrecognized service action: $action");
    }
}
