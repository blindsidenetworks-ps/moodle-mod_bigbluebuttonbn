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
      | bigbluebuttonbn | RecordingsOnly1 | Test Recordings only description 1 | C2     | bigbluebuttonbn3 | 2    | 1                   |
    And the following "mod_bigbluebuttonbn > recordings" exist:
      | bigbluebuttonbn | meta_bbb-recording-name |
      | RoomRecordings  | Recording 1             |
      | RoomRecordings  | Recording 2             |

  @javascript
  Scenario: I check we display the right information (Recording Name as name and Description)
    When I log in as "admin"
    And I am on "Test Course 2" course homepage
    Then I follow "RecordingsOnly1"
    # We check column names regarding changes made in CONTRIB-7703.
    And I should not see "Recording" in the ".mod_bigbluebuttonbn_recordings_table thead" "css_element"
    And I should not see "Meeting" in the ".mod_bigbluebuttonbn_recordings_table thead" "css_element"
    And I should see "Name" in the ".mod_bigbluebuttonbn_recordings_table thead" "css_element"

  @javascript
  Scenario: I check that I can import recordings into the Recording Only activity from other activities
    When I log in as "admin"
    And I am on "Test Course 2" course homepage
    Then I follow "RecordingsOnly1"
    Then I click on "Import recording links" "button"
    Then I select "Test Course 1 (C1)" from the "courseidscope" singleselect
    Then I select "RoomRecordings" from the "frombn" singleselect
    # add the first recording
    And I click on "a.action-icon" "css_element" in the "1" "mod_bigbluebuttonbn > Recording row"
    Then I wait until the page is ready
    # add the second recording
    And I click on "a.action-icon" "css_element" in the "1" "mod_bigbluebuttonbn > Recording row"
    Then I wait until the page is ready
    And I click on "Go back" "button"
    Then I should see "Recording 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    Then I should see "Recording 2" in the "2" "mod_bigbluebuttonbn > Recording row"

  @javascript
  Scenario: I check that I can import recordings into the Recording Only activity and then if I delete them
    they are back into the pool to be imported again
    When I log in as "admin"
    And I am on "Test Course 2" course homepage
    Then I follow "RecordingsOnly1"
    Then I click on "Import recording links" "button"
    Then I select "Test Course 1 (C1)" from the "courseidscope" singleselect
    Then I select "RoomRecordings" from the "frombn" singleselect
    # add the first recording
    And I click on "a.action-icon" "css_element" in the "1" "mod_bigbluebuttonbn > Recording row"
    # add the second recording
    And I click on "a.action-icon" "css_element" in the "1" "mod_bigbluebuttonbn > Recording row"
    Then I wait until the page is ready
    And I click on "Go back" "button"
    # This should be refactored with the right classes for the table element
    # We use javascript here to create the table so we don't get the same structure.
    Then I should see "Recording 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    Then I click on "a[data-action='delete']" "css_element" in the "1" "mod_bigbluebuttonbn > Recording row"
    # There is no confirmation dialog when deleting an imported record.
    And I wait until the page is ready
    Then I should not see "Recording 1"
    Then I click on "Import recording links" "button"
    Then I select "Test Course 1 (C1)" from the "courseidscope" singleselect
    Then I select "RoomRecordings" from the "frombn" singleselect
    Then I should see "Recording 1"
