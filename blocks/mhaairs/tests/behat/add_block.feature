@block @block_mhaairs @block_mhaairs-add-block @javascript @_switch_window
Feature: Add block

    Background:
        Given the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C1        | 0        |

        And the following "users" exist:
            | username | firstname | lastname | email               |
            | teacher1 | Teacher   | One      | teacher1@example.com|
            | student1 | Student   | One      | student1@example.com|

        And the following "course enrolments" exist:
            | user      | course| role          |
            | teacher1  | C1    | editingteacher|
            | student1  | C1    | student       |

    ##/:
    ## Add block 001
    ## When site level customer number and secret are not configured
    ## Then the block in a course should display a warning message
    ##:/
    Scenario: Add block 001
        Given I log in as "admin"
        And I follow "Courses"
        And I follow "Course 1"

        When I turn editing mode on
        And I add the "McGraw-Hill AAIRS" block

        Then I should see "The site requires further configuration. Please contact your site admin."
    #:Scenario

    ##/:
    ## Add block 002
    ## When site level customer number and secret are configured
    ## And no services are enabled
    ## Then the block in a course should display a warning message
    ## And no services should be available for configuration in block
    ##:/
    Scenario: Add block 002
        Given the mhaairs customer number and shared secret are set

        Given I log in as "admin"
        And I follow "Courses"
        And I follow "Course 1"

        When I turn editing mode on
        And I add the "McGraw-Hill AAIRS" block

        Then I should see "The site requires further configuration. Please contact your site admin."

        When I configure the "McGraw-Hill AAIRS" block
        Then "id_config_MHCampus" "checkbox" should not exist
        And I should see "Available Services have not yet been configured for this site. Please contact your site admin."
    #:Scenario

    ##/:
    ## Add block 003
    ## When site level customer number and secret are configured
    ## And services are enabled
    ## Then the block in a course should display the enabled services
    ##:/
    Scenario: Add block 003
        Given the mhaairs customer number and shared secret are set

        Given I log in as "admin"
        And I navigate to "Settings" node in "Site administration > Plugins > Blocks > McGraw-Hill AAIRS"
        And I set the field "McGraw-Hill Campus" to "checked"
        And I press "Save changes"

        And I follow "Courses"
        And I follow "Course 1"

        When I turn editing mode on
        And I add the "McGraw-Hill AAIRS" block

        Then I should see "McGraw-Hill Campus" in the ".block.block_mhaairs div.servicelink" "css_element"
        And I follow "McGraw-Hill Campus"
        And I switch to "__mhaairs_service_window" window
        And I should see "C1"
        And I switch to the main window
        And I log out

    #:Scenario

    ##/:
    ## Add block 004
    ## When site level customer number and secret are configured
    ## And services are enabled
    ## And the block in a course is configured to display no services
    ## Then the block should display a warning message
    ##:/
    Scenario: Add block 004
        Given the mhaairs customer number and shared secret are set

        Given I log in as "admin"
        And I navigate to "Settings" node in "Site administration > Plugins > Blocks > McGraw-Hill AAIRS"
        And I set the field "McGraw-Hill Campus" to "checked"
        And I press "Save changes"

        And I follow "Courses"
        And I follow "Course 1"

        When I turn editing mode on
        And I add the "McGraw-Hill AAIRS" block
        And I configure the "McGraw-Hill AAIRS" block
        And I set the following fields to these values:
          | id_config_MHCampus | 0 |
        And I press "Save changes"

        Then I should see "The block requires further configuration. Please configure the block."
    #:Scenario

    ##/:
    ## Add block 005
    ## When site level customer number and secret are configured
    ## And services are enabled
    ## And help links are enabled
    ## Then admin can see all links
    ## And teacher can see only the teacher link
    ## And student cannot see the links
    ##:/
    Scenario: Add block 005
        Given the mhaairs customer number and shared secret are set

        Given I log in as "admin"
        And I navigate to "Settings" node in "Site administration > Plugins > Blocks > McGraw-Hill AAIRS"
        And I set the field "McGraw-Hill Campus" to "checked"
        And I press "Save changes"

        And I follow "Courses"
        And I follow "Course 1"

        When I turn editing mode on
        And I add the "McGraw-Hill AAIRS" block

        Then I should see "Admin documentation" in the ".block.block_mhaairs a[target=__mhaairs_adminhelp_window]" "css_element"
        And I should see "Instructor documentation" in the ".block.block_mhaairs a[target=__mhaairs_teacherhelp_window]" "css_element"

        And the mhaairs help page "adminhelp" is not broken
        And I log out

        When I log in as "teacher1"
        And I follow "Course 1"
        Then I should not see "Admin documentation" in the ".block.block_mhaairs" "css_element"
        And I should see "Instructor documentation" in the ".block.block_mhaairs a[target=__mhaairs_teacherhelp_window]" "css_element"

        And the mhaairs help page "instructorhelp" is not broken
        And I log out

        When I log in as "student1"
        And I follow "Course 1"
        Then I should not see "Admin documentation" in the ".block.block_mhaairs" "css_element"
        And I should not see "Instructor documentation" in the ".block.block_mhaairs" "css_element"

    #:Scenario

    ##/:
    ## Add block 006
    ## When site level customer number and secret are configured
    ## And services are enabled
    ## And help links are disabled
    ## Then the block doesn't display the help links
    ##:/
    Scenario: Add block 006
        Given the mhaairs customer number and shared secret are set

        Given I log in as "admin"
        And I navigate to "Settings" node in "Site administration > Plugins > Blocks > McGraw-Hill AAIRS"
        And I set the field "McGraw-Hill Campus" to "checked"
        And I set the field "Help links" to ""
        And I press "Save changes"

        And I follow "Courses"
        And I follow "Course 1"

        When I turn editing mode on
        And I add the "McGraw-Hill AAIRS" block

        Then I should not see "Admin documentation" in the ".block.block_mhaairs" "css_element"
        And I should not see "Instructor documentation" in the ".block.block_mhaairs" "css_element"
    #:Scenario
