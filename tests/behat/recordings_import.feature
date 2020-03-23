@mod @mod_bigbluebuttonbn @core_form @course
Feature: Manage and list recordings
  As a user I am able to import existing recording into another bigbluebutton activity

  Background:  Make sure that import recording is enabled and course, activities and recording exists
    Given the following config values are set as admin:
      | bigbluebuttonbn_importrecordings_enabled | 1 |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Test Course 1 | C1        | 0        |
      | Test Course 2 | C2        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | 1        | user1@example.com |
    And the following "activities" exist:
      | activity        | name            | intro                              | course | idnumber         | type | recordings_imported |
      | bigbluebuttonbn | RoomRecordings  | Test Room Recording description    | C1     | bigbluebuttonbn1 | 0    | 0                   |
      | bigbluebuttonbn | RoomOnly        | Test Recordings only description   | C1     | bigbluebuttonbn2 | 1    | 0                   |
      | bigbluebuttonbn | RecordingsOnly1 | Test Recordings only description 1 | C2     | bigbluebuttonbn3 | 2    | 1                   |
      | bigbluebuttonbn | RecordingsOnly2 | Test Recordings only description 2 | C2     | bigbluebuttonbn4 | 2    | 1                   |
    And the following "mod_bigbluebuttonbn > recordings" exist:
      | bigbluebuttonbn | meta_bbb-recording-name |
      | RoomRecordings  | Recording 1             |
      | RoomRecordings  | Recording 2             |

  @javascript
  Scenario: I check that I can import recordings into the Recording Only activity from other activities
  the imported recordings are only visible in one activity (CONTRIB-7961)
    When I log in as "admin"

    Then I go to the courses management page
    And I follow "Test Course 2"
    Then I follow "View"
    Then I follow "RecordingsOnly1"
    Then I click on "Import recording links" "button"
    Then I select "Test Course 1" from the "import_recording_links_select" singleselect
      # add the first recording
    And I click on "td.lastcol a" "css_element"
    Then I wait until the page is ready
    Then I click on "Yes" "button"
    Then I wait until the page is ready
      # add the second recording
    And I click on "td.lastcol a" "css_element"
    Then I wait until the page is ready
    Then I click on "Yes" "button"
    Then I wait until the page is ready
    And I click on "Go back" "button"
    Then I wait until the page is ready
    Then I go to the courses management page
    And I follow "Test Course 2"
    Then I follow "View"
    Then I follow "RecordingsOnly1"
    And I should see "Recording 1"
    And I should see "Recording 2"

  @javascript
  Scenario: I check that I can import recordings into the Recording Only activity and that the list of
    recording is displays the right information (Recording Name as name and Description)
    When I log in as "admin"
    Then I go to the courses management page
    And I follow "Test Course 2"
    Then I follow "View"
    Then I follow "RecordingsOnly1"
    Then I click on "Import recording links" "button"
    Then I select "Test Course 1" from the "import_recording_links_select" singleselect
    Then I wait until the page is ready
    # We check column names regarding changes made in CONTRIB-7703.
    And I should not see "Recording" in the "table.generaltable > thead > tr" "css_element"
    And I should not see "Meeting" in the "table.generaltable > thead > tr" "css_element"
    And I should see "Name" in the "table.generaltable > thead > tr" "css_element"
    Then I select "Test Course 1" from the "import_recording_links_select" singleselect
    # We check that columns are in the right order, see CONTRIB-7703.
    Then I should see "Recording 1" in the "table.generaltable tr td.cell.c1" "css_element"
    # add the first recording
    And I click on "td.lastcol a" "css_element"
    Then I wait until the page is ready
    Then I click on "Yes" "button"
    Then I wait until the page is ready
    And I click on "Go back" "button"
    Then I wait until the page is ready
    And I should not see "Recording" in the "table > thead > tr" "css_element"
    And I should not see "Meeting" in the "table > thead > tr" "css_element"
    And I should see "Name" in the "table > thead > tr" "css_element"
    # This should be refactored with the right classes for the table element
    # We use javascript here to create the table so we don't get the same structure.
    Then I should see "Recording 1" in the "#bigbluebuttonbn_recordings_table table.yui3-datatable-table tbody.yui3-datatable-data tr td:nth-child(2)" "css_element"
    # Here we would need to test if there is no regression in the html by default view. This will have to be refactored
    # alongside with the view
    Then I wait until the page is ready
    Then I go to the courses management page
    And I follow "Test Course 2"
    Then I follow "View"
    Then I follow "RecordingsOnly2"
    And I should not see "Recording 1"
    And I should not see "Recording 2"
