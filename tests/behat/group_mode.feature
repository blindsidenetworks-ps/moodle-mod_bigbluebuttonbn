@mod @mod_bigbluebuttonbn @course
Feature: Test the module in group mode.

  Background:
    Given the following "courses" exist:
      | fullname      | shortname | category | groupmode | groupmodeforce |
      | Test Course 1 | C1        | 0        | 1         | 1              |
    # 1 = separate groups, we force the group
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | TeacherG1 | 1        | teacher1@example.com |
      | teacher2 | TeacherG2 | 2        | teacher2@example.com |
      | user1    | UserG1    | 1        | user1@example.com    |
      | user2    | UserG1    | 2        | user2@example.com    |
      | user3    | UserG2    | 3        | user3@example.com    |
      | user4    | UserG2    | 4        | user4@example.com    |
      | user5    | UserG2    | 5        | user5@example.com    |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | user3    | C1     | student        |
      | user4    | C1     | student        |
      | user5    | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
      | teacher2 | G2    |
      | user1    | G1    |
      | user2    | G1    |
      | user3    | G2    |
      | user4    | G2    |
      | user5    | G2    |
    And the following "activities" exist:
      | activity        | name           | intro                           | course | idnumber         | type | recordings_imported |
      | bigbluebuttonbn | RoomRecordings | Test Room Recording description | C1     | bigbluebuttonbn1 | 0    | 0                   |

  @javascript
  Scenario: When I create a BBB activity as a teacher who cannot acces all groups,
  I should only be able to select the group I belong on the main bigblue button page.
    Given the following "permission overrides" exist:
      | capability                  | permission | role           | contextlevel | reference |
      | moodle/site:accessallgroups | Prevent    | editingteacher | Course       | C1        |
    And I log in as "teacher1"
    And I am on "Test Course 1" course homepage
    Then I follow "RoomRecordings"
    And I should see "Separate groups: Group 1"

  @javascript
  Scenario: When I create a BBB activity as a teacher, I should only be able to specify individual "User" participants
  with whom I share a group with (or can view on the course participants screen).
    And I log in as "teacher1"
    And I am on "Test Course 1" course homepage
    Then I follow "RoomRecordings"
    And I should see "Group 1" in the "select[name='group']" "css_element"
    And I should see "Group 2" in the "select[name='group']" "css_element"

  @javascript
  Scenario: When I create a BBB activity as a teacher, I should only be able to specify individual "User" participants
  with whom I share a group with (or can view on the course participants screen).
    Given the following "permission overrides" exist:
      | capability                  | permission | role           | contextlevel | reference |
      | moodle/site:accessallgroups | Prevent    | editingteacher | Course       | C1        |
    And I log in as "teacher1"
    And I am on "Test Course 1" course homepage with editing mode on
    And I open "RoomRecordings" actions menu
    And I click on "Edit settings" "link" in the "RoomRecordings" activity
    Then I select "User" from the "bigbluebuttonbn_participant_selection_type" singleselect
    And I should see "TeacherG1 1" in the "#bigbluebuttonbn_participant_selection" "css_element"
    And I should see "UserG1 1" in the "#bigbluebuttonbn_participant_selection" "css_element"
    And I should not see "UserG2 3" in the "#bigbluebuttonbn_participant_selection" "css_element"
    And I should not see "TeacherG2 2" in the "#bigbluebuttonbn_participant_selection" "css_element"

  @javascript
  Scenario: When I create a BBB activity as a teacher, I should only be able to specify individual "User" participants
  with whom I share a group with (or can view on the course participants screen).
    And I log in as "teacher1"
    And I am on "Test Course 1" course homepage with editing mode on
    And I open "RoomRecordings" actions menu
    And I click on "Edit settings" "link" in the "RoomRecordings" activity
    Then I select "User" from the "bigbluebuttonbn_participant_selection_type" singleselect
    And I should see "TeacherG1 1" in the "#bigbluebuttonbn_participant_selection" "css_element"
    And I should see "UserG1 1" in the "#bigbluebuttonbn_participant_selection" "css_element"
    And I should see "UserG2 3" in the "#bigbluebuttonbn_participant_selection" "css_element"
