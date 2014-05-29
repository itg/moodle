<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Script to batch process 2.2 assignments to 2.5 via cron.
 *
 * @package    tool_assignmentupgrade
 * @copyright  2012 NetSpot
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/datalib.php');
require_once($CFG->libdir . '/sessionlib.php');
require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/assignmentupgrade/locallib.php');

raise_memory_limit(MEMORY_EXTRA);
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = true;

// setup admin user for cron (admin user required for assignment upgrade) 
cron_setup_user();

mtrace('Start ' . get_string('batchupgrade', 'tool_assignmentupgrade') . ' at ' . date('r', time()));

$current 		= 0;
$assignmentids 	= tool_assignmentupgrade_load_all_upgradable_assignmentids();
$total 			= count($assignmentids);

mtrace('Upgrading ' . $total . ' assignment(s)');

foreach ($assignmentids as $assignmentid) {
	try {
		list($summary, $success, $log) = tool_assignmentupgrade_upgrade_assignment($assignmentid);
		$current += 1;
		$params = array('current'=>$current, 'total'=>$total);
		mtrace(get_string('upgradeprogress', 'tool_assignmentupgrade', $params) . ' (Assignment ID: ' . $assignmentid . ')');
		if($success){
		  mtrace(get_string('upgradeassignmentsuccess', 'tool_assignmentupgrade'));
		}
	}
	catch (Exception $e) {
		mtrace(get_string('conversionfailed', 'tool_assignmentupgrade', $log));
		continue;
	}
}

mtrace('Finish ' . get_string('batchupgrade', 'tool_assignmentupgrade'));