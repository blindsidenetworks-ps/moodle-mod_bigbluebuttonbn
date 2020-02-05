@mod @mod_bigbluebuttonbn
Feature: bigbluebuttonbn instance
  In order to create a room activity with recordings
  As a user
  I need to add three room activities to an existent course

  @javascript
  Scenario: Add three room activities to an existent course
    When I log in as "admin"
    And I create a course with:
      | Course full name | Test Course |
      | Course short name | testcourse |
    And I follow "Test Course"
    And I turn editing mode on
    And I add a "BigBlueButtonBN" to section "1" and I fill the form with:
      | Instance type | Room/Activity with recordings |
      | Virtual classroom name | RoomRecordings |
    Then I should see "RoomRecordings"
    When I follow "RoomRecordings"
    Then I should see "RoomRecordings"
    And "#bigbluebuttonbn_view_message_box" "css_element" should be visible
    And "#bigbluebuttonbn_view_action_button_box" "css_element" should be visible
    And "#bigbluebuttonbn_recordings_table" "css_element" should be visible
    When I follow "testcourse"
    And I add a "BigBlueButtonBN" to section "1" and I fill the form with:
      | Instance type | Room/Activity only |
      | Virtual classroom name | RoomOnly  |
    Then I should see "RoomOnly"
    When I follow "RoomOnly"
    Then I should see "RoomOnly"
    And "#bigbluebuttonbn_view_message_box" "css_element" should be visible
    And "#bigbluebuttonbn_view_action_button_box" "css_element" should be visible
    And "#bigbluebuttonbn_recordings_table" "css_element" should not be visible
    When I follow "testcourse"
    And I add a "BigBlueButtonBN" to section "1" and I fill the form with:
      | Instance type | Recordings only |
      | Virtual classroom name | RecordingsOnly |
    Then I should see "RecordingsOnly"
    When I follow "RecordingsOnly"
    Then I should see "RecordingsOnly"
    And "#bigbluebuttonbn_view_message_box" "css_element" should not be visible
    And "#bigbluebuttonbn_view_action_button_box" "css_element" should not be visible
    And "#bigbluebuttonbn_recordings_table" "css_element" should be visible

  @javascript
  Scenario: Add an activity and check that required settings are available for the three
    types of instance types
    When I log in as "admin"
    And I create a course with:
      | Course full name | Test Course |
      | Course short name | testcourse |
    And I follow "Test Course"
    And I turn editing mode on
    And I add a "BigBlueButtonBN" to section "1"
    And I wait until the page is ready
    When  I select "Room/Activity with recordings" from the "Instance type" singleselect
    Then I should see "Restrict access"
    When  I select "Room/Activity only" from the "Instance type" singleselect
    Then I wait until the page is ready
    Then I should see "Restrict access"
    When  I select "Recordings only" from the "Instance type" singleselect
    Then I wait until the page is ready
    Then I should see "Restrict access"
