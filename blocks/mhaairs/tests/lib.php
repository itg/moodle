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
 * PHPUnit Mhaairs advanced test case.
 *
 * @package     block_mhaairs
 * @category    phpunit
 * @copyright   2014 Itamar Tzadok <itamar@substantialmethods.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit mhaairs advanced test case base class.
 *
 * @package     block_mhaairs
 * @category    phpunit
 * @copyright   2015 Itamar Tzadok <itamar@substantialmethods.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class block_mhaairs_testcase extends advanced_testcase {
    protected $course;
    protected $roles;
    protected $bi;
    protected $guest;
    protected $teacher;
    protected $assistant;
    protected $student1;
    protected $student2;

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp() {
        global $DB, $PAGE;

        $this->resetAfterTest(true);

        // Create a course we are going to add the block to.
        // This is Test course 1 | tc_1.
        // Add idnumber tc1, so that we can test identity type.
        $record = array('idnumber' => 'tc1');
        $this->course = $this->getDataGenerator()->create_course($record);
        $courseid = $this->course->id;

        // Set the page.
        $PAGE->set_course($this->course);
        $contextid = $PAGE->context->id;

        // Create an instance of the block in the course.
        $generator = $this->getDataGenerator()->get_plugin_generator('block_mhaairs');
        $record = array('parentcontextid' => $contextid, 'pagetypepattern' => '*');
        $this->bi = $generator->create_instance($record);

        // Create users and enroll them in the course.
        $roles = $DB->get_records_menu('role', array(), '', 'shortname,id');
        $this->roles = $roles;

        // Teacher.
        $user = $this->getDataGenerator()->create_user(array('username' => 'teacher'));
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $roles['editingteacher']);
        $this->teacher = $user;

        // Assistant.
        $user = $this->getDataGenerator()->create_user(array('username' => 'assistant'));
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $roles['teacher']);
        $this->assistant = $user;

        // Student1.
        $user = $this->getDataGenerator()->create_user(array('username' => 'student1'));
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $roles['student']);
        $this->student1 = $user;

        // Student2.
        $user = $this->getDataGenerator()->create_user(array('username' => 'student2'));
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $roles['student']);
        $this->student2 = $user;

        // Guest.
        $user = $DB->get_record('user', array('username' => 'guest'));
        $this->guest = $user;
    }

    /**
     * Sets the user.
     *
     * @return void
     */
    protected function set_user($username) {
        if ($username == 'admin') {
            $this->setAdminUser();
        } else if ($username == 'guest') {
            $this->setGuestUser();
        } else {
            $this->setUser($this->$username);
        }
    }

    /**
     * Adds a grade item via the update grade service.
     *
     * @param string courseid
     * @param string iteminstance
     * @param array options
     * @return void
     */
    protected function add_grade_item_by_service($courseid, $iteminstance, $options = array()) {
        $callback = 'block_mhaairs_gradebookservice_external::update_grade';

        // Service params.
        $serviceparams = array(
            'source' => 'mhaairs',
            'courseid' => $courseid,
            'itemtype' => 'manual',
            'itemmodule' => 'mhaairs',
            'iteminstance' => $iteminstance,
            'itemnumber' => '0',
            'grades' => null,
            'itemdetails' => null,
        );

        $category = !empty($options['category']) ? $options['category'] : '';
        $deleted = !empty($options['deleted']) ? $options['deleted'] : '';
        $identitytype = !empty($options['identitytype']) ? $options['identitytype'] : '';
        $useexisting = !empty($options['useexisting']) ? $options['useexisting'] : '';

        // Item details.
        $itemdetails = array(
            'categoryid' => $category,
            'itemname' => $iteminstance,
            'idnumber' => 0,
            'gradetype' => GRADE_TYPE_VALUE,
            'grademax' => 100,
            'hidden' => '',
            'deleted' => $deleted,
            'identity_type' => $identitytype,
            'needsupdate' => '',
            'useexisting' => $useexisting,
        );

        // Create first grade item.
        $itemdetailsjson = urlencode(json_encode($itemdetails));
        $serviceparams['itemdetails'] = $itemdetailsjson;

        $result = call_user_func_array($callback, $serviceparams);
        return $result;
    }
}
