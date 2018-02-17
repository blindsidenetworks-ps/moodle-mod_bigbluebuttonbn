@mod @mod_bigbluebuttonbn @rr
Feature: room activity with recordings instance
  In order to create a room activity with recordings
  As a user
  I need to add a room activity with recordings to an existent course

  @javascript
  Scenario: Add a room activity with recordings to an existent course
    When I log in as "admin"
    And I create a course with:
      | Course full name | Test Course |
      | Course short name | testcourse |
    And I follow "Test Course"
    And I turn editing mode on
    And I add a "BigBlueButtonBN" to section "1" and I fill the form with:
      | Instance type | Room/Activity with recordings |
      | Virtual classroom name | TestActivity |
    Then I should see "TestActivity"
    When I follow "TestActivity"
    Then I should see "TestActivity"
    And "#bigbluebuttonbn_view_message_box" "css_element" should be visible
    And "#bigbluebuttonbn_view_action_button_box" "css_element" should be visible
    And I should see "Recordings"
    And "#bigbluebuttonbn_recordings_table" "css_element" should be visible
