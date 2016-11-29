<?php
/**
 * The mod_bigbluebuttonbn meeting joined event.
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2014-2016 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

namespace mod_bigbluebuttonbn\event;
defined('MOODLE_INTERNAL') || die();

class bigbluebuttonbn_meeting_joined extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'bigbluebuttonbn';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_meeting_joined', 'mod_bigbluebuttonbn');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $a = (object) array('userid' => $this->userid, 'bigbluebuttonbnid' => $this->objectid, 'courseid' => $this->contextinstanceid);
        return "The user with id '$a->userid' has joined a bigbluebutton meeting for the bigbluebuttonbn activity with id '$a->bigbluebuttonbnid' for the course id '$a->courseid'.";
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return(array($this->courseid, 'bigbluebuttonbn', 'meeting joined',
                'view.php?pageid=' . $this->objectid, get_string('event_meeting_joined', 'bigbluebuttonbn'), $this->contextinstanceid));
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/bigbluebuttonbn/view.php', array('id' => $this->objectid));
    }

    public static function get_objectid_mapping() {
        return array('db' => 'bigbluebuttonbn', 'restore' => 'bigbluebuttonbn');
    }
}