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
 * The mod_bigbluebuttonbn settings/renderer.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\settings;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

/**
 * Helper class for rendering HTML for settings.php.
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer {

    /**
     * @var $settings stores the settings as they come from settings.php
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param object $settings
     */
    public function __construct(&$settings) {
        $this->settings = $settings;
    }

    /**
     * Render the header for a group.
     *
     * @param string $name
     * @param string $itemname
     * @param string $itemdescription
     *
     * @return void
     */
    public function render_group_header($name, $itemname = null, $itemdescription = null) {
        if ($itemname === null) {
            $itemname = get_string('config_' . $name, 'bigbluebuttonbn');
        }
        if ($itemdescription === null) {
            $itemdescription = get_string('config_' .$name . '_description', 'bigbluebuttonbn');
        }
        $item = new \admin_setting_heading('bigbluebuttonbn_config_' . $name, $itemname, $itemdescription);
        $this->settings->add($item);
    }

    /**
     * Render an element in a group.
     *
     * @param string $name
     * @param object $item
     *
     * @return void
     */
    public function render_group_element($name, $item) {
        global $CFG;
        if (!isset($CFG->bigbluebuttonbn[$name])) {
            $this->settings->add($item);
        }
    }

    /**
     * Render a text element in a group.
     *
     * @param string    $name
     * @param object    $default
     * @param string    $type
     *
     * @return Object
     */
    public function render_group_element_text($name, $default = null, $type = PARAM_RAW) {
        $item = new \admin_setting_configtext('bigbluebuttonbn_' . $name,
                get_string('config_' . $name, 'bigbluebuttonbn'),
                get_string('config_' . $name . '_description', 'bigbluebuttonbn'),
                $default, $type);
        return $item;
    }

    /**
     * Render a checkbox element in a group.
     *
     * @param string    $name
     * @param object    $default
     *
     * @return Object
     */
    public function render_group_element_checkbox($name, $default = null) {
        $item = new \admin_setting_configcheckbox('bigbluebuttonbn_' . $name,
                get_string('config_' . $name, 'bigbluebuttonbn'),
                get_string('config_' . $name . '_description', 'bigbluebuttonbn'),
                $default);
        return $item;
    }

    /**
     * Render a multiselect element in a group.
     *
     * @param string    $name
     * @param object    $defaultsetting
     * @param object    $choices
     *
     * @return Object
     */
    public function render_group_element_configmultiselect($name, $defaultsetting, $choices) {
        $item = new \admin_setting_configmultiselect('bigbluebuttonbn_' . $name,
                get_string('config_' . $name, 'bigbluebuttonbn'),
                get_string('config_' . $name . '_description', 'bigbluebuttonbn'),
                $defaultsetting, $choices);
        return $item;
    }

    /**
     * Render a select element in a group.
     *
     * @param string    $name
     * @param object    $defaultsetting
     * @param object    $choices
     *
     * @return Object
     */
    public function render_group_element_configselect($name, $defaultsetting, $choices) {
        $item = new \admin_setting_configselect('bigbluebuttonbn_' . $name,
                get_string('config_' . $name, 'bigbluebuttonbn'),
                get_string('config_' . $name . '_description', 'bigbluebuttonbn'),
                $defaultsetting, $choices);
        return $item;
    }

    /**
     * Render a general warning message.
     *
     * @param string    $name
     * @param string    $message
     * @param string    $type
     * @param boolean   $closable
     *
     * @return Object
     */
    public function render_warning_message($name, $message, $type = 'warning', $closable = true) {
        global $OUTPUT;
        $output = $OUTPUT->box_start('box boxalignleft adminerror alert alert-' . $type . ' alert-block fade in',
            'bigbluebuttonbn_' . $name)."\n";
        if ($closable) {
            $output .= '  <button type="button" class="close" data-dismiss="alert">&times;</button>' . "\n";
        }
        $output .= '  ' . $message . "\n";
        $output .= $OUTPUT->box_end() . "\n";
        $item = new \admin_setting_heading('bigbluebuttonbn_' . $name, '', $output);
        $this->settings->add($item);
        return $item;
    }

    /**
     * Render a general manage file for use as default presentation.
     *
     * @param string    $name
     *
     * @return Object
     */
    public function render_filemanager_default_file_presentation($name) {

        $filemanageroptions = array();
        $filemanageroptions['accepted_types'] = '*';
        $filemanageroptions['maxbytes'] = 0;
        $filemanageroptions['subdirs'] = 0;
        $filemanageroptions['maxfiles'] = 1;
        $filemanageroptions['mainfile'] = true;

        $filemanager = new \admin_setting_configstoredfile('mod_bigbluebuttonbn/presentationdefault',
            get_string('config_' . $name, 'bigbluebuttonbn'),
            get_string('config_' . $name . '_description', 'bigbluebuttonbn'),
            'presentationdefault',
            0,
            $filemanageroptions);

        $this->settings->add($filemanager);
        return $filemanager;
    }
}
