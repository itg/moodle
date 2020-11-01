@block @block_mhaairs @block_mhaairs-site_administration @javascript
Feature: Configuration settings

    ##/:
    ## Default settings
    ##:/
    Scenario: Default settings
        Given I log in as "admin"
        When I navigate to "Settings" node in "Site administration > Plugins > Blocks > McGraw-Hill AAIRS"
        Then "input[name=s__block_mhaairs_sslonly]:not([checked=checked])" "css_element" should exist
        And "input[name=s__block_mhaairs_customer_number]:empty" "css_element" should exist
        And "input[name=s__block_mhaairs_shared_secret]:empty" "css_element" should exist
        And "input[id=id_s__block_mhaairs_display_services_MHCampus]" "css_element" should not exist
        And "input[name=s__block_mhaairs_display_helplinks][checked=checked]" "css_element" should exist
        And "input[name=s__block_mhaairs_sync_gradebook][checked=checked]" "css_element" should exist
        And "input[name=s__block_mhaairs_locktype]" "css_element" should not exist
        And "input[name=s__block_mhaairs_gradelog]:not([checked=checked])" "css_element" should exist
    #:Scenario

    ##/:
    ## Settings after adding customer number
    ##:/
    Scenario: Settings after adding customer number and shared secret
        Given I log in as "admin"
        And the mhaairs customer number and shared secret are set
        When I navigate to "Settings" node in "Site administration > Plugins > Blocks > McGraw-Hill AAIRS"
        Then "input[name=s__block_mhaairs_sslonly]:not([checked=checked])" "css_element" should exist
        And "input[name=s__block_mhaairs_customer_number]:not(empty)" "css_element" should exist
        And "input[name=s__block_mhaairs_shared_secret]:not(empty)" "css_element" should exist
        And "input[id=id_s__block_mhaairs_display_services_MHCampus]:not([checked=checked])" "css_element" should exist
        And "input[name=s__block_mhaairs_display_helplinks][checked=checked]" "css_element" should exist
        And "input[name=s__block_mhaairs_sync_gradebook][checked=checked]" "css_element" should exist
        And "input[name=s__block_mhaairs_locktype]" "css_element" should not exist
        And "input[name=s__block_mhaairs_gradelog]:not([checked=checked])" "css_element" should exist
    #:Scenario
