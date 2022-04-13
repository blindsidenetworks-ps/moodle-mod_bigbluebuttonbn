<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_bigbluebuttonbn;

use cm_info;
use context;
use context_course;
use context_module;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\helpers\files;
use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use moodle_url;
use stdClass;

/**
 * Instance record for mod_bigbluebuttonbn.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instance {

    /** @var int Defines an instance type that includes room and recordings */
    public const TYPE_ALL = 0;

    /** @var int Defines an instance type that includes only room */
    public const TYPE_ROOM_ONLY = 1;

    /** @var int Defines an instance type that includes only recordings */
    public const TYPE_RECORDING_ONLY = 2;

    /** @var cm_info The cm_info object relating to the instance */
    protected $cm;

    /** @var stdClass The course that the instance is in */
    protected $course;

    /** @var stdClass The instance data for the instance */
    protected $instancedata;

    /** @var context The current context */
    protected $context;

    /** @var array The list of participants */
    protected $participantlist;

    /** @var int The current groupid if set */
    protected $groupid;

    /**
     * instance constructor.
     *
     * @param cm_info $cm
     * @param stdClass $course
     * @param stdClass $instancedata
     * @param int|null $groupid
     */
    public function __construct(cm_info $cm, stdClass $course, stdClass $instancedata, ?int $groupid = null) {
        $this->cm = $cm;
        $this->course = $course;
        $this->instancedata = $instancedata;
        $this->groupid = $groupid;
    }

    /**
     * Get a group instance of the specified instance.
     *
     * @param self $originalinstance
     * @param int $groupid
     * @return null|self
     */
    public static function get_group_instance_from_instance(self $originalinstance, int $groupid): ?self {
        return new self(
            $originalinstance->get_cm(),
            $originalinstance->get_course(),
            $originalinstance->get_instance_data(),
            $groupid
        );
    }

    /**
     * Get the instance information from an instance id.
     *
     * @param int $instanceid The id from the bigbluebuttonbn table
     * @return null|self
     */
    public static function get_from_instanceid(int $instanceid): ?self {
        global $DB;

        $coursetable = new \core\dml\table('course', 'c', 'c');
        $courseselect = $coursetable->get_field_select();
        $coursefrom = $coursetable->get_from_sql();

        $cmtable = new \core\dml\table('course_modules', 'cm', 'cm');
        $cmfrom = $cmtable->get_from_sql();

        $bbbtable = new \core\dml\table('bigbluebuttonbn', 'bbb', 'b');
        $bbbselect = $bbbtable->get_field_select();
        $bbbfrom = $bbbtable->get_from_sql();

        $sql = <<<EOF
    SELECT {$courseselect}, {$bbbselect}
      FROM {$cmfrom}
INNER JOIN {$coursefrom} ON c.id = cm.course
INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
INNER JOIN {$bbbfrom} ON cm.instance = bbb.id
     WHERE bbb.id = :instanceid
EOF;

        $result = $DB->get_record_sql($sql, [
            'modname' => 'bigbluebuttonbn',
            'instanceid' => $instanceid,
        ]);

        if (empty($result)) {
            return null;
        }

        $course = $coursetable->extract_from_result($result);
        $instancedata = $bbbtable->extract_from_result($result);
        $cm = get_fast_modinfo($course)->instances['bigbluebuttonbn'][$instancedata->id];

        return new self($cm, $course, $instancedata);
    }

    /**
     * Get the instance information from a cmid.
     *
     * @param int $cmid
     * @return null|self
     */
    public static function get_from_cmid(int $cmid): ?self {
        global $DB;

        $coursetable = new \core\dml\table('course', 'c', 'c');
        $courseselect = $coursetable->get_field_select();
        $coursefrom = $coursetable->get_from_sql();

        $cmtable = new \core\dml\table('course_modules', 'cm', 'cm');
        $cmfrom = $cmtable->get_from_sql();

        $bbbtable = new \core\dml\table('bigbluebuttonbn', 'bbb', 'b');
        $bbbselect = $bbbtable->get_field_select();
        $bbbfrom = $bbbtable->get_from_sql();

        $sql = <<<EOF
    SELECT {$courseselect}, {$bbbselect}
      FROM {$cmfrom}
INNER JOIN {$coursefrom} ON c.id = cm.course
INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
INNER JOIN {$bbbfrom} ON cm.instance = bbb.id
     WHERE cm.id = :cmid
EOF;

        $result = $DB->get_record_sql($sql, [
            'modname' => 'bigbluebuttonbn',
            'cmid' => $cmid,
        ]);

        if (empty($result)) {
            return null;
        }

        $course = $coursetable->extract_from_result($result);
        $instancedata = $bbbtable->extract_from_result($result);
        $cm = get_fast_modinfo($course)->get_cm($cmid);

        return new self($cm, $course, $instancedata);
    }

    /**
     * Get the instance information from a meetingid.
     *
     * If a group is specified in the meetingid then this will also be set.
     *
     * @param string $meetingid
     * @return null|self
     */
    public static function get_from_meetingid(string $meetingid): ?self {
        $matches = self::parse_meetingid($meetingid);

        $instance = self::get_from_instanceid($matches['instanceid']);

        if ($instance && array_key_exists('groupid', $matches)) {
            $instance->set_group_id($matches['groupid']);
        }

        return $instance;
    }

    /**
     * Parse a meetingID for key data.
     *
     * @param string $meetingid
     * @return array
     * @throws \moodle_exception
     */
    public static function parse_meetingid(string $meetingid): array {
        $result = preg_match(
            '@(?P<meetingid>[^-]*)-(?P<courseid>[^-]*)-(?P<instanceid>\d+)(\[(?P<groupid>\d*)\])?@',
            $meetingid,
            $matches
        );

        if ($result !== 1) {
            throw new \moodle_exception("The supplied meeting id '{$meetingid}' is invalid found.");
        }

        return $matches;
    }

    /**
     * Get all instances in the specified course.
     *
     * @param int $courseid
     * @return self[]
     */
    public static function get_all_instances_in_course(int $courseid): array {
        global $DB;

        $coursetable = new \core\dml\table('course', 'c', 'c');
        $courseselect = $coursetable->get_field_select();
        $coursefrom = $coursetable->get_from_sql();

        $cmtable = new \core\dml\table('course_modules', 'cm', 'cm');
        $cmfrom = $cmtable->get_from_sql();

        $bbbtable = new \core\dml\table('bigbluebuttonbn', 'bbb', 'b');
        $bbbselect = $bbbtable->get_field_select();
        $bbbfrom = $bbbtable->get_from_sql();

        $sql = <<<EOF
    SELECT cm.id as cmid, {$courseselect}, {$bbbselect}
      FROM {$cmfrom}
INNER JOIN {$coursefrom} ON c.id = cm.course
INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
INNER JOIN {$bbbfrom} ON cm.instance = bbb.id
     WHERE cm.course = :courseid
EOF;

        $results = $DB->get_records_sql($sql, [
            'modname' => 'bigbluebuttonbn',
            'courseid' => $courseid,
        ]);

        $instances = [];
        foreach ($results as $result) {
            $course = $coursetable->extract_from_result($result);
            $instancedata = $bbbtable->extract_from_result($result);
            $cm = get_fast_modinfo($course)->get_cm($result->cmid);
            $instances[$cm->id] = new self($cm, $course, $instancedata);
        }

        return $instances;
    }

    /**
     * Set the current group id of the activity.
     *
     * @param int $groupid
     */
    public function set_group_id(int $groupid): void {
        $this->groupid = $groupid;
    }

    /**
     * Get the current groupid if set.
     *
     * @return int
     */
    public function get_group_id(): int {
        return empty($this->groupid) ? 0 : $this->groupid;
    }

    /**
     * Check whether this instance is configured to use a group.
     *
     * @return bool
     */
    public function uses_groups(): bool {
        $groupmode = groups_get_activity_groupmode($this->get_cm());
        return $groupmode != NOGROUPS;
    }

    /**
     * Get the group name for the current group, if a group has been set.
     *
     * @return null|string
     */
    public function get_group_name(): ?string {
        $groupid = $this->get_group_id();

        if (!$this->uses_groups()) {
            return null;
        }

        if ($groupid == 0) {
            return get_string('allparticipants');
        }

        return groups_get_group_name($groupid);
    }

    /**
     * Get the course object for the instance.
     *
     * @return stdClass
     */
    public function get_course(): stdClass {
        return $this->course;
    }

    /**
     * Get the course id of the course that the instance is in.
     *
     * @return int
     */
    public function get_course_id(): int {
        return $this->course->id;
    }

    /**
     * Get the cm_info object for the instance.
     *
     * @return cm_info
     */
    public function get_cm(): cm_info {
        return $this->cm;
    }

    /**
     * Get the id of the course module.
     *
     * @return int
     */
    public function get_cm_id(): int {
        return $this->get_cm()->id;
    }

    /**
     * Get the context.
     *
     * @return context_module
     */
    public function get_context(): context_module {
        if ($this->context === null) {
            $this->context = context_module::instance($this->get_cm()->id);
        }

        return $this->context;
    }

    /**
     * Get the context ID of the module context.
     *
     * @return int
     */
    public function get_context_id(): int {
        return $this->get_context()->id;
    }

    /**
     * Get the course context.
     *
     * @return context_course
     */
    public function get_course_context(): context_course {
        return $this->get_context()->get_course_context();
    }

    /**
     * Get the big blue button instance data.
     *
     * @return stdClass
     */
    public function get_instance_data(): stdClass {
        return $this->instancedata;
    }

    /**
     * Get the instance id.
     *
     * @return int
     */
    public function get_instance_id(): int {
        return $this->instancedata->id;
    }

    /**
     * Helper to get an instance var.
     *
     * @param string $name
     * @return string
     */
    public function get_instance_var(string $name) {
        $instance = $this->get_instance_data();
        if (property_exists($instance, $name)) {
            return $instance->{$name};
        }

        return null;
    }

    /**
     * Get the meeting id for this meeting.
     *
     * @param null|int $groupid
     * @return string
     */
    public function get_meeting_id(?int $groupid = null): string {
        $baseid = sprintf(
            '%s-%s-%s',
            $this->get_instance_var('meetingid'),
            $this->get_course_id(),
            $this->get_instance_var('id')
        );

        if ($groupid === null) {
            $groupid = $this->get_group_id();
        }

        return sprintf('%s[%s]', $baseid, $groupid);
    }

    /**
     * Get the name of the meeting, considering any group if set.
     *
     * @return string
     */
    public function get_meeting_name(): string {
        $meetingname = $this->get_instance_var('name');

        $groupname = $this->get_group_name();
        if ($groupname !== null) {
            $meetingname .= " ({$groupname})";
        }

        return $meetingname;
    }

    /**
     * Get the meeting description with the pluginfile URLs optionally rewritten.
     *
     * @param bool $rewritepluginfileurls
     * @return string
     */
    public function get_meeting_description(bool $rewritepluginfileurls = false): string {
        $description = $this->get_instance_var('intro');

        if ($rewritepluginfileurls) {
            $description = file_rewrite_pluginfile_urls(
                $description,
                'pluginfile.php',
                $this->get_context_id(),
                'mod_bigbluebuttonbn',
                'intro',
                null
            );
        }

        return $description;
    }

    /**
     * Get the meeting type if set.
     *
     * @return null|string
     */
    public function get_type(): ?string {
        return $this->get_instance_var('type');
    }

    /**
     * Whether this instance is includes both a room, and recordings.
     *
     * @return bool
     */
    public function is_type_room_and_recordings(): bool {
        return $this->get_type() == self::TYPE_ALL;
    }

    /**
     * Whether this instance is one that only includes a room.
     *
     * @return bool
     */
    public function is_type_room_only(): bool {
        return $this->get_type() == self::TYPE_ROOM_ONLY;
    }

    /**
     * Whether this instance is one that only includes recordings.
     *
     * @return bool
     */
    public function is_type_recordings_only(): bool {
        return $this->get_type() == self::TYPE_RECORDING_ONLY;
    }

    /**
     * Get the participant list for the session.
     *
     * @return array
     */
    public function get_participant_list(): array {
        if ($this->participantlist === null) {
            $this->participantlist = roles::get_participant_list(
                $this->get_instance_data(),
                $this->get_context()
            );
        }

        return $this->participantlist;
    }

    /**
     * Get the user.
     *
     * @return stdClass
     */
    public function get_user(): stdClass {
        global $USER;

        return $USER;
    }

    /**
     * Get the id of the user.
     *
     * @return int
     */
    public function get_user_id(): int {
        $user = $this->get_user();

        return $user->id;
    }

    /**
     * Get the fullname of the current user.
     *
     * @return string
     */
    public function get_user_fullname(): string {
        $user = $this->get_user();

        return fullname($user);
    }

    /**
     * Whether the current user is an administrator.
     *
     * @return bool
     */
    public function is_admin(): bool {
        global $USER;

        return is_siteadmin($USER->id);
    }

    /**
     * Whether the user is a session moderator.
     *
     * @return bool
     */
    public function is_moderator(): bool {
        return roles::is_moderator(
            $this->get_context(),
            $this->get_participant_list()
        );
    }

    /**
     * Whether this user can join the conference.
     *
     * This checks the user right for access against capabilities and group membership
     *
     * @return bool
     */
    public function can_join(): bool {
        $groupid = $this->get_group_id();
        $context = $this->get_context();
        $inrightgroup =
            groups_group_visible($groupid, $this->get_course(), $this->get_cm());
        $hascapability = has_capability('moodle/category:manage', $context)
            || (has_capability('mod/bigbluebuttonbn:join', $context) && $inrightgroup);
        $canjoin = $this->get_type() != self::TYPE_RECORDING_ONLY && $hascapability; // Recording only cannot be joined ever.
        return $canjoin;
    }

    /**
     * Whether this user can manage recordings.
     *
     * @return bool
     */
    public function can_manage_recordings(): bool {
        // Note: This will include site administrators.
        // The has_capability() function returns truthy for admins unless otherwise directed.
        return has_capability('mod/bigbluebuttonbn:managerecordings', $this->get_context());
    }

    /**
     * Whether this user can publish/unpublish/protect/unprotect/delete recordings.
     *
     * @param string $action
     * @return bool
     */
    public function can_perform_on_recordings($action): bool {
        // Note: This will include site administrators.
        // The has_capability() function returns truthy for admins unless otherwise directed.
        return has_capability("mod/bigbluebuttonbn:{$action}recordings", $this->get_context());
    }

    /**
     * Get the configured user limit.
     *
     * @return int
     */
    public function get_user_limit(): int {
        if ((boolean) config::get('userlimit_editable')) {
            return intval($this->get_instance_var('userlimit'));
        }

        return intval((int) config::get('userlimit_default'));
    }

    /**
     * Check whether the user limit has been reached.
     *
     * @param int $currentusercount The user count to check
     * @return bool
     */
    public function has_user_limit_been_reached(int $currentusercount): bool {
        $userlimit = $this->get_user_limit();
        if (empty($userlimit)) {
            return false;
        }

        return $currentusercount >= $userlimit;
    }

    /**
     * Check whether the current user counts towards the user limit.
     *
     * @return bool
     */
    public function does_current_user_count_towards_user_limit(): bool {
        if ($this->is_admin()) {
            return false;
        }

        if ($this->is_moderator()) {
            return false;
        }

        return true;
    }

    /**
     * Get the voice bridge details.
     *
     * @return null|int
     */
    public function get_voice_bridge(): ?int {
        $voicebridge = (int) $this->get_instance_var('voicebridge');
        if ($voicebridge > 0) {
            return 70000 + $voicebridge;
        }

        return null;
    }

    /**
     * Whether participants are muted on entry.
     *
     * @return bool
     */
    public function get_mute_on_start(): bool {
        return $this->get_instance_var('muteonstart');
    }

    /**
     * Get the moderator password.
     *
     * @return string
     */
    public function get_moderator_password(): string {
        return $this->get_instance_var('moderatorpass');
    }

    /**
     * Get the viewer password.
     *
     * @return string
     */
    public function get_viewer_password(): string {
        return $this->get_instance_var('viewerpass');
    }

    /**
     * Get the appropriate password for the current user.
     *
     * @return string
     */
    public function get_current_user_password(): string {
        if ($this->is_admin() || $this->is_moderator()) {
            return $this->get_moderator_password();
        }

        return $this->get_viewer_password();
    }

    /**
     * Whether to show the recording button
     *
     * @return bool
     */
    public function should_show_recording_button(): bool {
        global $CFG;
        if (!empty($CFG->bigbluebuttonbn_recording_hide_button_editable)) {
            $recordhidebutton = (bool) $this->get_instance_var('recordhidebutton');
            $recordallfromstart = (bool) $this->get_instance_var('recordallfromstart');
            return !($recordhidebutton || $recordallfromstart);
        }

        return !$CFG->bigbluebuttonbn_recording_hide_button_default;
    }

    /**
     * Whether this instance is recorded.
     *
     * @return bool
     */
    public function is_recorded(): bool {
        return (bool) $this->get_instance_var('record');
    }

    /**
     * Whether this instance can import recordings from another instance.
     *
     * @return bool
     */
    public function can_import_recordings(): bool {
        if ($this->can_manage_recordings()) {
            return true;
        }

        return $this->is_feature_enabled('importrecordings');
    }

    /**
     * Whether this instance is recorded from the start.
     *
     * @return bool
     */
    public function should_record_from_start(): bool {
        if (!$this->is_recorded()) {
            // This meeting is not recorded.
            return false;
        }

        return (bool) $this->get_instance_var('recordallfromstart');
    }

    /**
     * Whether recording can be started and stopped.
     *
     * @return bool
     */
    public function allow_recording_start_stop(): bool {
        if (!$this->is_recorded()) {
            // If the meeting is not configured for recordings, do not allow it to be recorded.
            return false;
        }

        return $this->should_show_recording_button();
    }

    /**
     * Get the welcome message to display.
     *
     * @return string
     */
    public function get_welcome_message(): string {
        $welcomestring = $this->get_instance_var('welcome');
        if (!config::get('welcome_editable') || empty($welcomestring)) {
            $welcomestring = config::get('welcome_default');
        }
        if (empty($welcomestring)) {
            $welcomestring = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn');
        }

        $welcome = [$welcomestring];

        if ($this->is_recorded()) {
            if ($this->should_record_from_start()) {
                $welcome[] = get_string('bbbrecordallfromstartwarning', 'bigbluebuttonbn');
            } else {
                $welcome[] = get_string('bbbrecordwarning', 'bigbluebuttonbn');
            }
        }

        return implode('<br><br>', $welcome);
    }

    /**
     * Get the presentation data for internal use.
     *
     * The URL returned for the presentation will be accessible through moodle with checks about user being logged in.
     *
     * @return array|null
     */
    public function get_presentation(): ?array {
        return $this->do_get_presentation_with_nonce(false);
    }

    /**
     * Get the presentation data for external API url.
     *
     * The URL returned for the presentation will be accessible publicly but once and with a specific URL.
     *
     * @return array|null
     */
    public function get_presentation_for_bigbluebutton_upload(): ?array {
        return $this->do_get_presentation_with_nonce(true);
    }

    /**
     * Generate Presentation URL.
     *
     * @param bool $withnonce The generated url will have a nonce included
     * @return array|null
     */
    protected function do_get_presentation_with_nonce(bool $withnonce): ?array {
        if ($this->has_ended()) {
            return files::get_presentation(
                $this->get_context(),
                $this->get_instance_var('presentation'),
                null,
                $withnonce
            );
        } else if ($this->is_currently_open()) {
            return files::get_presentation(
                $this->get_context(),
                $this->get_instance_var('presentation'),
                $this->get_instance_id(),
                $withnonce
            );
        } else {
            return [];
        }
    }

    /**
     * Whether the current time is before the scheduled start time.
     *
     * @return bool
     */
    public function before_start_time(): bool {
        $openingtime = $this->get_instance_var('openingtime');
        if (empty($openingtime)) {
            return false;
        }

        return $openingtime >= time();
    }

    /**
     * Whether the meeting time has passed.
     *
     * @return bool
     */
    public function has_ended(): bool {
        $closingtime = $this->get_instance_var('closingtime');
        if (empty($closingtime)) {
            return false;
        }

        return $closingtime < time();
    }

    /**
     * Whether this session is currently open.
     *
     * @return bool
     */
    public function is_currently_open(): bool {
        if ($this->before_start_time()) {
            return false;
        }

        if ($this->has_ended()) {
            return false;
        }

        return true;
    }

    /**
     * Whether the user must wait to join the session.
     *
     * @return bool
     */
    public function user_must_wait_to_join(): bool {
        if ($this->is_admin() || $this->is_moderator()) {
            return false;
        }

        return (bool) $this->get_instance_var('wait');
    }

    /**
     * Whether the user can force join in all cases
     *
     * @return bool
     */
    public function user_can_force_join(): bool {
        return $this->is_admin() || $this->is_moderator();
    }

    /**
     * Whether the user can end a meeting
     *
     * @return bool
     */
    public function user_can_end_meeting(): bool {
        return $this->is_admin() || $this->is_moderator();
    }

    /**
     * Get information about the origin.
     *
     * @return stdClass
     */
    public function get_origin_data(): stdClass {
        global $CFG;

        $parsedurl = parse_url($CFG->wwwroot);
        return (object) [
            'origin' => 'Moodle',
            'originVersion' => $CFG->release,
            'originServerName' => $parsedurl['host'],
            'originServerUrl' => $CFG->wwwroot,
            'originServerCommonName' => '',
            'originTag' => sprintf('moodle-mod_bigbluebuttonbn (%s)', get_config('mod_bigbluebuttonbn', 'version')),
        ];
    }

    /**
     * Whether this is a server belonging to blindside networks.
     *
     * @return bool
     */
    public function is_blindside_network_server(): bool {
        return bigbluebutton_proxy::is_bn_server();
    }

    /**
     * Get the URL used to access the course that the instance is in.
     *
     * @return moodle_url
     */
    public function get_course_url(): moodle_url {
        return new moodle_url('/course/view.php', ['id' => $this->get_course_id()]);
    }

    /**
     * Get the URL used to view the instance as a user.
     *
     * @return moodle_url
     */
    public function get_view_url(): moodle_url {
        return new moodle_url('/mod/bigbluebuttonbn/view.php', [
            'id' => $this->cm->id,
        ]);
    }

    /**
     * Get the logout URL used to log out of the meeting.
     *
     * @return moodle_url
     */
    public function get_logout_url(): moodle_url {
        return new moodle_url('/mod/bigbluebuttonbn/bbb_view.php', [
            'action' => 'logout',
            'id' => $this->cm->id,
        ]);
    }

    /**
     * Get the URL that the remote server will use to notify that the recording is ready.
     *
     * @return moodle_url
     */
    public function get_record_ready_url(): moodle_url {
        return new moodle_url('/mod/bigbluebuttonbn/bbb_broker.php', [
            'action' => 'recording_ready',
            'bigbluebuttonbn' => $this->instancedata->id,
        ]);
    }

    /**
     * Get the URL that the remote server will use to notify of meeting events.
     *
     * @return moodle_url
     */
    public function get_meeting_event_notification_url(): moodle_url {
        return new moodle_url('/mod/bigbluebuttonbn/bbb_broker.php', [
            'action' => 'meeting_events',
            'bigbluebuttonbn' => $this->instancedata->id,
        ]);
    }

    /**
     * Get the URL used to join a meeting.
     *
     * @return moodle_url
     */
    public function get_join_url(): moodle_url {
        return new moodle_url('/mod/bigbluebuttonbn/bbb_view.php', [
            'action' => 'join',
            'id' => $this->cm->id,
            'bn' => $this->instancedata->id,
        ]);
    }

    /**
     * Get the URL used for the import page.
     *
     * @return moodle_url
     */
    public function get_import_url(): moodle_url {
        return new moodle_url('/mod/bigbluebuttonbn/import_view.php', [
            'destbn' => $this->instancedata->id,
        ]);
    }

    /**
     * Get the list of enabled features for this instance.
     *
     * @return array
     */
    public function get_enabled_features(): array {
        return config::get_enabled_features(
            bigbluebutton_proxy::get_instance_type_profiles(),
            $this->get_instance_var('type') ?? null
        );
    }

    /**
     * Check whetherthe named features is enabled.
     *
     * @param string $feature
     * @return bool
     */
    public function is_feature_enabled(string $feature): bool {
        $features = $this->get_enabled_features();

        return !empty($features[$feature]);
    }

    /**
     * Check if meeting is recorded.
     *
     * @return bool
     */
    public function should_record() {
        return (boolean) config::recordings_enabled() && $this->is_recorded();
    }

    /**
     * Get recordings for this instance
     *
     * @param string[] $excludedid
     * @param bool $viewdeleted view deleted recordings ?
     * @return recording[]
     */
    public function get_recordings(array $excludedid = [], $viewdeleted = false): array {
        // Fetch the list of recordings depending on the status of the instance.
        // show room is enabled for TYPE_ALL and TYPE_ROOM_ONLY.
        if ($this->is_feature_enabled('showroom')) {
            // Not in the import page.
            return recording::get_recordings_for_instance(
                $this,
                $this->is_feature_enabled('importrecordings'),
                $this->get_instance_var('recordings_imported'),
            );
        }
        // We show all recording from this course as this is TYPE_RECORDING.
        return recording::get_recordings_for_course(
            $this->get_course_id(),
            $excludedid,
            $this->is_feature_enabled('importrecordings'),
            false,
            $viewdeleted
        );
    }

    /**
     * Check if this is a valid group for this user/instance,
     *
     *
     * @param stdClass $user
     * @param int $groupid
     * @return bool
     */
    public function user_has_group_access($user, $groupid) {
        $cm = $this->get_cm();
        $context = $this->get_context();
        // Then validate group.
        $groupmode = groups_get_activity_groupmode($cm);
        if ($groupmode && $groupid) {
            $accessallgroups = has_capability('moodle/site:accessallgroups', $context);
            if ($accessallgroups || $groupmode == VISIBLEGROUPS) {
                $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid);
            } else {
                $allowedgroups = groups_get_all_groups($cm->course, $user->id, $cm->groupingid);
            }
            if (!array_key_exists($groupid, $allowedgroups)) {
                return false;
            }
            if (!groups_group_visible($groupid, $this->get_course(), $this->get_cm())) {
                return false;
            }
        }
        return true;
    }
}
