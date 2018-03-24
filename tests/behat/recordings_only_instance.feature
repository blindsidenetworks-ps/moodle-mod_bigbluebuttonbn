@mod @mod_bigbluebuttonbn @re
Feature: recordings only instance
  In order to create a recordings activity only
  As a user
  I need to add a recordings activity without room to an existent course

  @javascript
  Scenario: Add a recordings activity only to an existent course
    When I log in as "admin"
    And I create a course with:
      | Course full name | Test Course |
      | Course short name | testcourse |
    And I follow "Test Course"
    And I turn editing mode on
    And I add a "BigBlueButtonBN" to section "1" and I fill the form with:
      | Instance type | Recordings only |
      | Virtual classroom name | TestActivity |
    Then I should see "TestActivity"
    When I follow "TestActivity"
    Then I should see "TestActivity"
    And "#bigbluebuttonbn_view_message_box" "css_element" should not be visible
    And "#bigbluebuttonbn_view_action_button_box" "css_element" should not be visible
    And "#bigbluebuttonbn_recordings_table" "css_element" should be visible
