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
    Then I wait until the page is ready
    Then I go to the courses management page
    And I follow "Test Course 2"
    Then I follow "View"
    Then I follow "RecordingsOnly2"
    And I should not see "Recording 1"
    And I should not see "Recording 2"
