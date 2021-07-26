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

/**
 * View Page tempalte renderable.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bigbluebuttonbn\output;

use core\output\notification;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\external\meeting_info;
use renderable;
use renderer_base;
use stdClass;
use templatable;

class view_page implements renderable, templatable {

    /** @var instance The instance being rendered */
    protected $instance;

    /**
     * Constructor for the View Page.
     */
    public function __construct(instance $instance) {
        $this->instance = $instance;
    }

    /**
     * Export the content required to render the template.
     *
     * @param  renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $templatedata = (object) [
            'instanceid' => $this->instance->get_instance_id(),
            'groupselector' => $output->render_groups_selector($this->instance),
            'meetingname' => $this->instance->get_meeting_name(),
            'description' => $this->instance->get_meeting_description(true),
            'joinurl' => $this->instance->get_join_url(),
        ];

        if ($this->show_default_server_warning()) {
            $templatedata->serverwarning = (new notification(
                get_string('view_warning_default_server', 'mod_bigbluebuttonbn'),
                notification::NOTIFY_WARNING,
                false
            ))->export_for_template($output);
        }

        $viewwarningmessage = config::get('general_warning_message');
        if ($this->show_view_warning() && !empty($viewwarningmessage)) {
            $templatedata->sitenotification = (object) [
                'message' => $viewwarningmessage,
                'type' => config::get('general_warning_box_type'),
                'icon' => [
                    'pix' => 'i/bullhorn',
                    'component' => 'core',
                ],
            ];

            if ($url = config::get('general_warning_button_href')) {
                $templatedata->sitenotification->actions = [[
                    'url' => $url,
                    'title' => config::get('general_warning_button_text'),
                ]];
            }
        }

        if ($this->instance->is_feature_enabled('showroom')) {
            $meeting = new \mod_bigbluebuttonbn\meeting($this->instance);
            // TODO Move meeting_info into a new location.
            $templatedata->room = $meeting->get_meeting_info();
        }

        if ($this->instance->is_feature_enabled('showrecordings') && $this->instance->is_recorded()) {
            $recordings = new recordings_session($this->instance);
            $templatedata->recordings = $recordings->export_for_template($output);
        } else if ($this->instance->is_type_recordings_only()) {
            $templatedata->recordingwarning = (new notification(
                get_string('view_message_recordings_disabled', 'mod_bigbluebuttonbn'),
                notification::NOTIFY_WARNING,
                false
            ))->export_for_template($output);
        }

        return $templatedata;
    }

    /**
     * Whether to show the default server warning.
     *
     * @return bool
     */
    protected function show_default_server_warning(): bool {
        if (!$this->instance->is_admin()) {
            return false;
        }

        if (bbb_constants::BIGBLUEBUTTONBN_DEFAULT_SERVER_URL != config::get('server_url')) {
            return false;
        }

        return true;
    }

    /**
     * Whether to show the view warning.
     *
     * @return bool
     */
    protected function show_view_warning(): bool {
        if ($this->instance->is_admin()) {
            return true;
        }

        $generalwarningroles = explode(',', config::get('general_warning_roles'));
        $userroles = \mod_bigbluebuttonbn\local\helpers\roles::bigbluebuttonbn_get_user_roles(
            $this->instance->get_context(),
            $this->instance->get_user_id()
        );

        foreach ($userroles as $userrole) {
            if (in_array($userrole->shortname, $generalwarningroles)) {
                return true;
            }
        }

        return false;
    }

}
