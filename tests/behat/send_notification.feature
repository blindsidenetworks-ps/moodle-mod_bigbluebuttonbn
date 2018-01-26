@mod @mod_bigbluebuttonbn @rs
Feature: Send notification to user when activity is created
  In order to inform users about the creation of a new activity related to a course
  As a user
  I need to make the activity send automatically notifications to them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |

  @javascript
  Scenario: Add room acticity with recordings to an existent course with "send notification" option enabled
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
      | id_notification        | 1            |
    Then I should see "TestActivity"
    When I log out
    And I log in as "student1"
    Then I should see "1" in the "div.count-container" "css_element"
    When I click on "#nav-message-popover-container" "css_element"
    Then I should see "Admin User" in the "div.content-item-body" "css_element"
    And I should see "TestActivity" in the "div.content-item-body" "css_element"