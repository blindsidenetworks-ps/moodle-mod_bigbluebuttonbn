@mod @mod_bigbluebuttonbn @rm
Feature: Create an instance and deny student from joining it until moderator join
  In order to create a room activity and deny student from joining it until moderator join
  As a user
  I need to add a room activity with recordings to an existent course
  And Enable an option "wait for moderator" in it

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |

  @javascript
  Scenario: Add a room activity with recordings to an existent course and enable "wait for moderator" option
    When I log in as "admin"
    And I create a course with:
      | Course full name | Test Course |
      | Course short name | testcourse |
    And I enrol "Student 1" user as "Student"
    And I follow "Test Course"
    And I turn editing mode on
    And I add a "BigBlueButtonBN" to section "1" and I fill the form with:
      | Instance type | Room/Activity with recordings |
      | Virtual classroom name | TestActivity |
      | id_wait                | 1            |
    Then I should see "TestActivity"
    When I follow "TestActivity"
    Then I should see "TestActivity"
    And I should see "This conference room is ready. You can join the session now."
    And the "Join session" "button" should be enabled
    When I log out
    And I log in as "student1"
    And I follow "Site home"
    And I follow "Test Course"
    And I follow "TestActivity"
    Then I should see "TestActivity"
    And I should see "Waiting for a moderator to join"
    And the "Join session" "button" should be disabled