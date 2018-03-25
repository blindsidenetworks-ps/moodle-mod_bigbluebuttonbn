@mod @mod_bigbluebuttonbn @general_settings
Feature: test general settings :
                       - name,
                       - description,
                       - display description on course page,
                       - send notification

  In order to create a bbb room with general settings
  As a user
  I need to add an name to a bbb room
  And a description
  And display description in course page
  And send notification to participants

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |

  @javascript
  Scenario: Add room activity with recordings to an existent course with general settings filled
    When I log in as "admin"
    And I create a course with:
      | Course full name | Test Course |
      | Course short name | testcourse |
    And I enrol "Student 1" user as "Student"
    And I follow "Test Course"
    And I turn editing mode on
    And I add a "BigBlueButtonBN" to section "1" and I fill the form with:
      | Instance type | Room/Activity with recordings |
      | Virtual classroom name | TestActivity            |
      | Description            | TestActivityDescription |
      | id_showdescription     | 1                       |
      | id_notification        | 1                       |
    Then I should see "TestActivity"
    And I should see "TestActivityDescription"
    When I follow "TestActivity"
    Then I should see "TestActivity"
    And I should see "TestActivityDescription"
    When I log out
    And I log in as "student1"
    Then I should see "1" in the "div.count-container" "css_element"
    When I click on "#nav-message-popover-container" "css_element"
    Then I should see "Admin User" in the "div.content-item-body" "css_element"
    And I should see "TestActivity" in the "div.content-item-body" "css_element"