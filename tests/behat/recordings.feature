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
    And the following "mod_bigbluebuttonbn > meeting" exists:
      | activity         | RoomRecordings |
    And the following "mod_bigbluebuttonbn > recordings" exist:
      | bigbluebuttonbn | name        | description   |
      | RoomRecordings  | Recording 1 | Description 1 |
      | RoomRecordings  | Recording 2 | Description 2 |

  @javascript
  Scenario: I can see the recordings related to an activity
    Given I am on the "C1" "Course" page logged in as admin
    When I follow "RoomRecordings"
    Then I should see "Recording 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    And I should see "Recording 2" in the "2" "mod_bigbluebuttonbn > Recording row"

  @javascript
  Scenario: I can rename the recording
    Given I am on the "C1" "Course" page logged in as admin
    And I follow "RoomRecordings"
    And I should see "Recording 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    When I set the field "Edit Name" in the "1" "mod_bigbluebuttonbn > Recording row" to "Recording with an updated name 1"
    Then I should see "Recording with an updated name 1"
    And I should see "Recording 2"
    And I reload the page
    And I should see "Recording with an updated name 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    And I should see "Recording 2" in the "2" "mod_bigbluebuttonbn > Recording row"

  @javascript
  Scenario: I can set a new description for this recording
    Given I am on the "C1" "Course" page logged in as admin
    And I follow "RoomRecordings"
    When I set the field "Edit Description" in the "1" "mod_bigbluebuttonbn > Recording row" to "This is a new recording description 1"
    Then I should see "This is a new recording description 1"
    Then I should see "Description 2"
    And I reload the page
    And I should see "This is a new recording description 1" in the "1" "mod_bigbluebuttonbn > Recording row"
    And I should see "Description 2" in the "2" "mod_bigbluebuttonbn > Recording row"

  @javascript
  Scenario: I can delete a recording
    Given I am on the "C1" "Course" page logged in as admin
    And I follow "RoomRecordings"
    When I click on "a[data-action='delete']" "css_element" in the "1" "mod_bigbluebuttonbn > Recording row"
    And I click on "OK" "button" in the "Confirm" "dialogue"
    Then I should not see "Recording 1"
    And I should see "Recording 2"
    And I reload the page
    And I should not see "Recording 1"
    And I should see "Recording 2"
