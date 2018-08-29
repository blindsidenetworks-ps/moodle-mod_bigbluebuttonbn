@mod @mod_bigbluebuttonbn @ro
Feature: room activity only instance
  In order to create a room activity only
  As a user
  I need to add a room activity without recordings to an existent course

  @javascript
  Scenario: Add a room activity only to an existent course
    When I log in as "admin"
    And I create a course with:
      | Course full name | Test Course |
      | Course short name | testcourse |
    And I follow "Test Course"
    And I turn editing mode on
    And I add a "BigBlueButtonBN" to section "1" and I fill the form with:
      | Instance type | Room/Activity only |
      | Virtual classroom name | TestActivity |
    Then I should see "TestActivity"
    When I follow "TestActivity"
    Then I should see "TestActivity"
    And "#bigbluebuttonbn_view_message_box" "css_element" should be visible
    And "#bigbluebuttonbn_view_action_button_box" "css_element" should be visible
    And I should not see "Recordings"
    And "#bigbluebuttonbn_recordings_table" "css_element" should not be visible
