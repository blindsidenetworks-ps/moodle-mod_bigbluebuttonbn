@mod @mod_bigbluebuttonbn @core_form @course
Feature: The recording can be managed through the room page
  As a user I am able to see the relevant recording for a given bigbluebutton activity and modify its parameters

  Background:  Make sure that import recording is enabled and course, activities and recording exists
    Given the following "courses" exist:
      | fullname      | shortname | category |
      | Test Course 1 | C1        | 0        |
      | Test Course 2 | C2        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | 1        | user1@example.com |
    And the following "activities" exist:
      | activity        | name           | intro                           | course | idnumber         | type | recordings_imported |
      | bigbluebuttonbn | RoomRecordings | Test Room Recording description | C1     | bigbluebuttonbn1 | 0    | 0                   |
    And the following "mod_bigbluebuttonbn > recordings" exist:
      | bigbluebuttonbn | meta_bbb-recording-name |
      | RoomRecordings  | Recording 1             |
      | RoomRecordings  | Recording 2             |

  @javascript
  Scenario: I can see the recordings related to an activity
    When I log in as "admin"
    Then I go to the courses management page
    And I follow "Test Course 1"
    Then I follow "View"
    Then I follow "RoomRecordings"
    # nth-child does not work unfortunately so we use xpath.
    Then I should see "Recording 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    Then I should see "Recording 2" in the "2" "mod_bigbluebuttonbn > Recording row"

  @javascript
  Scenario: I can rename the recording
    When I log in as "admin"
    Then I go to the courses management page
    And I follow "Test Course 1"
    Then I follow "View"
    Then I follow "RoomRecordings"
    Then I should see "Recording 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    And I set the field "Edit Name" in the "1" "mod_bigbluebuttonbn > Recording row" to "Recording 1.5"
    And I set the field "Edit Name" in the "2" "mod_bigbluebuttonbn > Recording row" to "Recording 2.5"
    And I should see "Recording 1.5"
    And I should see "Recording 2.5"
    And I reload the page
    Then I should see "Recording 1.5" in the "1" "mod_bigbluebuttonbn > Recording row"
    Then I should see "Recording 2.5" in the "2" "mod_bigbluebuttonbn > Recording row"

  @javascript
  Scenario: I can set a new description for this recording
    When I log in as "admin"
    Then I go to the courses management page
    And I follow "Test Course 1"
    Then I follow "View"
    Then I follow "RoomRecordings"
    Then I should see "Recording 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    And I set the field "Edit Description" in the "1" "mod_bigbluebuttonbn > Recording row" to "This is a new recording description 1"
    And I set the field "Edit Description" in the "2" "mod_bigbluebuttonbn > Recording row" to "This is a new recording description 2"
    Then I should see "This is a new recording description 1"
    Then I should see "This is a new recording description 2"
    And I reload the page
    Then I should see "This is a new recording description 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    Then I should see "This is a new recording description 2" in the "2" "mod_bigbluebuttonbn > Recording row"

  @javascript
  Scenario: I can delete a recording
    When I log in as "admin"
    Then I go to the courses management page
    And I follow "Test Course 1"
    Then I follow "View"
    Then I follow "RoomRecordings"
    Then I should see "Recording 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    Then I click on "a[data-action='delete']" "css_element" in the "1" "mod_bigbluebuttonbn > Recording row"
    And I click on "OK" "button" in the "Confirm" "dialogue"
    Then I should not see "Recording 1"
    Then I reload the page
    Then I should not see "Recording 1"
