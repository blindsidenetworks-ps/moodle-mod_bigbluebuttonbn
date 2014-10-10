<?php
/**
 * The mod_bigbluebuttonbn viewed event.
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2014 Blindside Networks Inc.
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
        return "The user with id '$this->userid' has joined a bigbluebutton meeting for the bigbluebuttonbn activity with id '$this->contextinstanceid' for " .
        "the course id '$this->objectid'.";
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return(array($this->courseid, 'bigbluebuttonbn', 'view',
                'view.php?pageid=' . $this->objectid, $this->objectid, $this->contextinstanceid));
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/bigbluebuttonbn/view.php', array('id' => $this->objectid));
    }
}
