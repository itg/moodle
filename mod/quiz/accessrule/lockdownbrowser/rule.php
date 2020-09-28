<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

$lockdownbrowser_locklib_file =
  $CFG->dirroot . '/blocks/lockdownbrowser/locklib.php';
if (is_readable($lockdownbrowser_locklib_file)) {
    require_once($lockdownbrowser_locklib_file);
}

// our rule class
class quizaccess_lockdownbrowser extends quiz_access_rule_base {

    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {
        // $quizobj is an object of class quiz declared in /mod/quiz/attemptlib.php
        if ($quizobj->get_quiz()->browsersecurity !==
          get_string('browsersecuritychoicekey', 'quizaccess_lockdownbrowser')) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    public function prevent_access() {
        $extfunc = "lockdownbrowser_check_for_lock";
        if (!function_exists($extfunc)) {
            print_error("errnofunc", "quizaccess_lockdownbrowser", "", $extfunc);
        }
        $result = self::check_plugin_dependencies();
        if ($result === false) {
            $result = $this->check_incompatible_rules();
        }
        if ($result === false) {
            $prevent_launch = $this->check_quiz_launch_dependencies();
            $result = lockdownbrowser_check_for_lock($this->quizobj, $prevent_launch);
        }
        return $result;
    }

    public function description() {
        return get_string('lockdownbrowsernotice', 'quizaccess_lockdownbrowser');
    }

    // Trac #5056
    public function setup_attempt_page($page) {
        $page->set_popup_notification_allowed(false); // Prevent message notifications.
        $page->set_cacheable(false);
        $page->set_pagelayout('secure');
        $page->add_body_class('quiz-secure-window');
        // Trac #5456
        //$page->requires->js_init_call('M.mod_quiz.secure_window.init',
        //  null, false, quiz_get_js_module());
        $page->requires->js_init_call('M.quizaccess_lockdownbrowser.secure_window.init',
          null, false, $this->get_js_module());
    }

    // Trac #5456
    // this function is based on quiz_get_js_module
    function get_js_module() {
        //global $PAGE;
        return array(
            //'name' => 'mod_quiz',
            'name' => 'quizaccess_lockdownbrowser',
            //'fullpath' => '/mod/quiz/module.js',
            'fullpath' => '/mod/quiz/accessrule/lockdownbrowser/module.js',
            'requires' => array(
                'base',
                'dom',
                'event-delegate',
                'event-key',
                'core_question_engine',
                'moodle-core-formchangechecker'
            ),
            'strings' => array(
                array('cancel', 'moodle'),
                array('flagged', 'question'),
                array('functiondisabledbysecuremode', 'quiz'),
                array('startattempt', 'quiz'),
                array('timesup', 'quiz'),
                array('changesmadereallygoaway', 'moodle'),
            ),
        );
    }

    public static function get_browser_security_choices() {
        return array(get_string('browsersecuritychoicekey', 'quizaccess_lockdownbrowser') =>
          get_string('requirelockdownbrowser', 'quizaccess_lockdownbrowser'));
    }

    public static function delete_settings($quiz) {
        try {
            lockdownbrowser_delete_options($quiz->id);
        } catch (Exception $ex) {
            // ignore possible exceptions
        }
    }

    /**
     * Whether the quiz launch dependencies are valid or not.
     * @return boolean false if dependencies are valid, true if not
     */
    protected function check_quiz_launch_dependencies() {
        if ($this->check_quiz_timing()) {
            return true;
        }
        return false;
    }

    /**
     * Whether the quiz timing settings indicate the quiz is available or not.
     * @return boolean false if quiz is available, true if not
     */
    protected function check_quiz_timing() {
        // these checks are the same as those in quizaccess_openclosedate::prevent_access
        if ($this->timenow < $this->quiz->timeopen) {
            return true;
        }
        if (!$this->quiz->timeclose) {
            return false;
        }
        if ($this->timenow <= $this->quiz->timeclose) {
            return false;
        }
        if ($this->quiz->overduehandling != 'graceperiod') {
            return true;
        }
        if ($this->timenow <= $this->quiz->timeclose + $this->quiz->graceperiod) {
            return false;
        }
        return true;
    }

    /**
     * Whether any incompatible rules are enabled or not.
     * @return string false if no incompatible rules are detected, a message
     *      explaining which one(s) if there are.
     */
    protected function check_incompatible_rules() {
        $component = 'quizaccess_lockdownbrowser';
        // https://moodle.org/plugins/quizaccess_onesession
        if (!empty($this->quizobj->get_quiz()->onesessionenabled)) {
            return get_string('onesessionenabled', $component);
        }
        return false;
    }

    /**
     * Whether the plugin dependencies are valid or not.
     * @return string false if dependencies are valid, a message explaining the
     *      reason if they are not.
     */
    protected static function check_plugin_dependencies() {
        $component = 'quizaccess_lockdownbrowser';
        $blockversion = self::get_block_version();
        if ($blockversion === false) {
            return get_string('noblockversion', $component);
        }
        $ruleversion = self::get_rule_version();
        if ($ruleversion === false) {
            return get_string('noruleversion', $component);
        }
        if (self::compare_plugin_versions($ruleversion, $blockversion) === false) {
            return get_string('invalidversion', $component);
        }
        return false;
    }

    /**
     * Retrieve the plugin version for this rule.
     * @return string the version string, or false if the version cannot be retrieved.
     */
    protected static function get_rule_version() {
        global $CFG;
        $plugin = new stdClass;
        $version_file = $CFG->dirroot . '/mod/quiz/accessrule/lockdownbrowser/version.php';
        if (is_readable($version_file)) {
            include($version_file);
        }
        $version = false;
        if (isset($plugin)) {
            if (!empty($plugin->version)) {
                $version = $plugin->version;
            }
        }
        return $version;
    }

    /**
     * Retrieve the plugin version for the LDB block.
     * @return string the version string, or false if the version cannot be retrieved.
     */
    protected static function get_block_version() {
        global $CFG;
        $plugin = new stdClass;
        $version_file = $CFG->dirroot . '/blocks/lockdownbrowser/version.php';
        if (is_readable($version_file)) {
            include($version_file);
        }
        $version = false;
        if (isset($plugin)) {
            if (!empty($plugin->version)) {
                $version = $plugin->version;
            }
        }
        return $version;
    }

    /**
     * Whether the plugin versions are considered equal or not, for the purpose
     * of dependency checking.
     * @return boolean true if versions are considered equal, false if they are not.
     */
    protected static function compare_plugin_versions($ruleversion, $blockversion) {
        $comparelength = 8; // yyyymmddxx, but only consider first 8 digits
        if (strlen($ruleversion) < $comparelength
          || strlen($blockversion) < $comparelength ) {
            return false;
        }
        if(substr($ruleversion, 0, $comparelength)
          == substr($blockversion, 0, $comparelength)) {
            return true;
        } else {
            return false;
        }
    }

}
