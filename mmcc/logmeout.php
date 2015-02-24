<?php

require_once('../config.php');
global $CFG;

$logout = $CFG->wwwroot.'/login/logout.php?sesskey='.sesskey();

#echo $logout;
header("Location: ".$logout);
die();
?>
