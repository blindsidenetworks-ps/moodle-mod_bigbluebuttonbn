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

namespace mod_bigbluebuttonbn\output;

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\helpers\roles;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Renderable for the instance notification updated message
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */
class notifier_instance_updated implements renderable, templatable {

    /**
     * @var object $bigbluebuttonbn
     */
    protected $bigbluebuttonbn;

    /**
     * @var object $sender
     */
    protected $sender;

    /**
     * @var $action string
     */
    protected $action;

    /**
     * Instance updated constructor
     *
     * @param object $bigbluebuttonbn
     * @param object $sender the user object for sender
     * @param string $action
     */
    public function __construct(object $bigbluebuttonbn, object $sender, string $action ) {
        $this->bigbluebuttonbn = $bigbluebuttonbn;
        $this->sender = $sender;
        $this->action = $action;
    }

    /**
     * Defer to template.
     *
     * @param renderer_base $output
     * @return object
     */
    public function export_for_template(renderer_base $output) {
        $msg = new \stdClass();
        $coursemodinfo = \course_modinfo::instance($this->bigbluebuttonbn->course);
        $course = $coursemodinfo->get_course($this->bigbluebuttonbn->course);
        // Build the message_body.
        $msg->action = $this->action;
        $msg->activity_url = new moodle_url('/mod/bigbluebuttonbn/view.php', ['id' => $this->bigbluebuttonbn->coursemodule]);
        $msg->activity_title = format_string($this->bigbluebuttonbn->name);
        // Add the meeting details to the message_body.
        $msg->action = ucfirst($this->action);
        $msg->activity_description = '';
        if (!empty($bigbluebuttonbn->intro)) {
            $msg->activity_description = format_string(trim($bigbluebuttonbn->intro));
        }
        $msg->activity_openingtime = $this->bigbluebuttonbn->openingtime;
        $msg->activity_closingtime = $this->bigbluebuttonbn->closingtime;
        $msg->activity_owner = fullname($this->sender);

        $msg->user_name = fullname($this->sender);
        $msg->user_email = $this->sender->email;
        $msg->course_name = $course->fullname;
        return $msg;
    }
}
