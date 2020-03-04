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
 * Config all BigBlueButtonBN instances in this course.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Moodle class for mod_form.
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_bigbluebuttonbn_mod_form extends moodleform_mod {

    /**
     * Define (add) particular settings this activity can have.
     *
     * @return void
     */
    public function definition() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        // Validates if the BigBlueButton server is running.
        $serverversion = bigbluebuttonbn_get_server_version();
        if (is_null($serverversion)) {
            print_error('general_error_unable_connect', 'bigbluebuttonbn',
                $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
            return;
        }
        // Context.
        $bigbluebuttonbn = null;
        $course = null;
        $courseid = optional_param('course', 0, PARAM_INT);
        if ($courseid) {
            $course = get_course($courseid);
        }
        if (!$course) {
            $cm = get_coursemodule_from_id('bigbluebuttonbn',
                optional_param('update', 0, PARAM_INT), 0, false, MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course),
                '*', MUST_EXIST);
            $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn',
                array('id' => $cm->instance), '*', MUST_EXIST);
        }
        $context = context_course::instance($course->id);
        // UI configuration options.
        $cfg = \mod_bigbluebuttonbn\locallib\config::get_options();
        $mform = &$this->_form;
        $jsvars = array();
        // Get only those that are allowed.
        $createroom = has_capability('mod/bigbluebuttonbn:meeting', $context);
        $createrecording = has_capability('mod/bigbluebuttonbn:recording', $context);
        $jsvars['instanceTypeProfiles'] = bigbluebuttonbn_get_instance_type_profiles_create_allowed(
            $createroom, $createrecording);
        $jsvars['instanceTypeDefault'] = array_keys($jsvars['instanceTypeProfiles'])[0];
        // If none is allowed, fail and return.
        if (empty($jsvars['instanceTypeProfiles'])) {
            print_error('general_error_not_allowed_to_create_instances)', 'bigbluebuttonbn',
                $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
            return;
        }
        $this->bigbluebuttonbn_mform_add_block_profiles($mform, $jsvars['instanceTypeProfiles']);
        // Data for participant selection.
        $participantlist = bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context);
        // Add block 'General'.
        $this->bigbluebuttonbn_mform_add_block_general($mform, $cfg);
        // Add block 'Room'.
        $this->bigbluebuttonbn_mform_add_block_room($mform, $cfg);
        // Add block 'Preuploads'.
        $this->bigbluebuttonbn_mform_add_block_preuploads($mform, $cfg);
        // Add block 'Participant List'.
        $this->bigbluebuttonbn_mform_add_block_participants($mform, $participantlist);
        // Add block 'Schedule'.
        $this->bigbluebuttonbn_mform_add_block_schedule($mform, $this->current);
        // Add block 'client Type'.
        $this->bigbluebuttonbn_mform_add_block_clienttype($mform, $cfg);
        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
        // JavaScript for locales.
        $PAGE->requires->strings_for_js(array_keys(bigbluebuttonbn_get_strings_for_js()), 'bigbluebuttonbn');
        $jsvars['participantData'] = bigbluebuttonbn_get_participant_data($context, $bigbluebuttonbn);
        $jsvars['participantList'] = $participantlist;
        $jsvars['iconsEnabled'] = (boolean)$cfg['recording_icons_enabled'];
        $jsvars['pixIconDelete'] = (string)$OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle');
        $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-modform',
            'M.mod_bigbluebuttonbn.modform.init', array($jsvars));
    }

    /**
     * Prepare the attachment for being stored.
     *
     * @param array $defaultvalues
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            // Editing existing instance - copy existing files into draft area.
            try {
                $draftitemid = file_get_submitted_draft_itemid('presentation');
                file_prepare_draft_area($draftitemid, $this->context->id, 'mod_bigbluebuttonbn', 'presentation', 0,
                    array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1, 'mainfile' => true)
                );
                $defaultvalues['presentation'] = $draftitemid;
            } catch (Exception $e) {
                debugging('Presentation could not be loaded: '.$e->getMessage(), DEBUG_DEVELOPER);
                return;
            }
        }
    }

    /**
     * Validates the data processed by the form.
     *
     * @param array $data
     * @param array $files
     * @return void
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (isset($data['openingtime']) && isset($data['closingtime'])) {
            if ($data['openingtime'] != 0 && $data['closingtime'] != 0 &&
                $data['closingtime'] < $data['openingtime']) {
                $errors['closingtime'] = get_string('bbbduetimeoverstartingtime', 'bigbluebuttonbn');
            }
        }
        if (isset($data['voicebridge'])) {
            if (!bigbluebuttonbn_voicebridge_unique($data['instance'], $data['voicebridge'])) {
                $errors['voicebridge'] = get_string('mod_form_field_voicebridge_notunique_error', 'bigbluebuttonbn');
            }
        }
        return $errors;
    }

    /**
     * Function for showing the block for selecting profiles.
     *
     * @param object $mform
     * @param array $profiles
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_profiles(&$mform, $profiles) {
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::recordings_enabled()) {
            $mform->addElement('select', 'type', get_string('mod_form_field_instanceprofiles', 'bigbluebuttonbn'),
                bigbluebuttonbn_get_instance_profiles_array($profiles),
                array('onchange' => 'M.mod_bigbluebuttonbn.modform.updateInstanceTypeProfile(this);'));
            $mform->addHelpButton('type', 'mod_form_field_instanceprofiles', 'bigbluebuttonbn');
        }
    }

    /**
     * Function for showing the block for general settings.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_general(&$mform, $cfg) {
        global $CFG;
        $mform->addElement('header', 'general', get_string('mod_form_block_general', 'bigbluebuttonbn'));
        $mform->addElement('text', 'name', get_string('mod_form_field_name', 'bigbluebuttonbn'),
            'maxlength="64" size="32"');
        $mform->setType('name', empty($CFG->formatstringstriptags) ? PARAM_CLEANHTML : PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements(get_string('mod_form_field_intro', 'bigbluebuttonbn'));
        $mform->setAdvanced('introeditor');
        $mform->setAdvanced('showdescription');
        if ($cfg['sendnotifications_enabled']) {
            $field = ['type' => 'checkbox', 'name' => 'notification', 'data_type' => PARAM_INT,
                'description_key' => 'mod_form_field_notification'];
            $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
                $field['description_key'], 0);
        }
    }

    /**
     * Function for showing details of the room settings for the room.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_room_room(&$mform, $cfg) {
        $field = ['type' => 'textarea', 'name' => 'welcome', 'data_type' => PARAM_TEXT,
            'description_key' => 'mod_form_field_welcome'];
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], '', ['wrap' => 'virtual', 'rows' => 5, 'cols' => '60']);
        $field = ['type' => 'hidden', 'name' => 'voicebridge', 'data_type' => PARAM_INT,
            'description_key' => null];
        if ($cfg['voicebridge_editable']) {
            $field['type'] = 'text';
            $field['data_type'] = PARAM_TEXT;
            $field['description_key'] = 'mod_form_field_voicebridge';
            $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
                $field['description_key'], 0, ['maxlength' => 4, 'size' => 6],
                ['message' => get_string('mod_form_field_voicebridge_format_error', 'bigbluebuttonbn'),
                 'type' => 'numeric', 'rule' => '####', 'validator' => 'server']
              );
        } else {
            $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
                $field['description_key'], 0, ['maxlength' => 4, 'size' => 6]);
        }
        $field = ['type' => 'hidden', 'name' => 'wait', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['waitformoderator_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_wait';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['waitformoderator_default']);
        $field = ['type' => 'hidden', 'name' => 'userlimit', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['userlimit_editable']) {
            $field['type'] = 'text';
            $field['data_type'] = PARAM_TEXT;
            $field['description_key'] = 'mod_form_field_userlimit';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['userlimit_default']);
        $field = ['type' => 'hidden', 'name' => 'record', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['recording_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_record';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recording_default']);

        // Record all from start and hide button.
        $field = ['type' => 'hidden', 'name' => 'recordallfromstart', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['recording_all_from_start_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordallfromstart';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recording_all_from_start_default']);

        $field = ['type' => 'hidden', 'name' => 'recordhidebutton', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['recording_hide_button_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordhidebutton';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recording_hide_button_default']);

        $mform->disabledIf('recordallfromstart', 'record', $condition = 'notchecked', $value = '0');
        $mform->disabledIf('recordhidebutton', 'record', $condition = 'notchecked', $value = '0');
        $mform->disabledIf('recordhidebutton', 'recordallfromstart', $condition = 'notchecked', $value = '0');
        // End Record all from start and hide button.

        $field = ['type' => 'hidden', 'name' => 'muteonstart', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['muteonstart_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_muteonstart';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['muteonstart_default']);

    }

    /**
     * Function for showing details of the recording settings for the room.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_room_recordings(&$mform, $cfg) {
        $recordingsettings = false;
        $field = ['type' => 'hidden', 'name' => 'recordings_html', 'data_type' => PARAM_INT,
                  'description_key' => null];
        if ($cfg['recordings_html_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordings_html';
            $recordingsettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recordings_html_default']);
        $field = ['type' => 'hidden', 'name' => 'recordings_deleted', 'data_type' => PARAM_INT,
                  'description_key' => null];
        if ($cfg['recordings_deleted_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordings_deleted';
            $recordingsettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recordings_deleted_default']);
        $field = ['type' => 'hidden', 'name' => 'recordings_imported', 'data_type' => PARAM_INT,
                  'description_key' => null];
        if ($cfg['importrecordings_enabled'] && $cfg['recordings_imported_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordings_imported';
            $recordingsettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recordings_imported_default']);
        $field = ['type' => 'hidden', 'name' => 'recordings_preview', 'data_type' => PARAM_INT,
                  'description_key' => null];
        if ($cfg['recordings_preview_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordings_preview';
            $recordingsettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recordings_preview_default']);

        if (!$recordingsettings) {
            $field = ['type' => 'static', 'name' => 'no_recordings',
                'defaultvalue' => get_string('mod_form_field_nosettings', 'bigbluebuttonbn')];
            $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], null, null,
                $field['defaultvalue']);
        }
    }

    /**
     * Function for showing the block for room settings.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_room(&$mform, $cfg) {
        if ($cfg['voicebridge_editable'] || $cfg['waitformoderator_editable'] ||
            $cfg['userlimit_editable'] || $cfg['recording_editable']) {
            $mform->addElement('header', 'room', get_string('mod_form_block_room', 'bigbluebuttonbn'));
            $this->bigbluebuttonbn_mform_add_block_room_room($mform, $cfg);
        }
        if ($cfg['recordings_html_editable'] || $cfg['recordings_deleted_editable'] ||
            $cfg['recordings_imported_editable'] || $cfg['recordings_preview_editable']) {
            $mform->addElement('header', 'recordings', get_string('mod_form_block_recordings', 'bigbluebuttonbn'));
            $this->bigbluebuttonbn_mform_add_block_room_recordings($mform, $cfg);
        }
    }

    /**
     * Function for showing the block for preuploaded presentation.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_preuploads(&$mform, $cfg) {
        if ($cfg['preuploadpresentation_enabled']) {
            $mform->addElement('header', 'preuploadpresentation',
                get_string('mod_form_block_presentation', 'bigbluebuttonbn'));
            $mform->setExpanded('preuploadpresentation');
            $filemanageroptions = array();
            $filemanageroptions['accepted_types'] = '*';
            $filemanageroptions['maxbytes'] = 0;
            $filemanageroptions['subdirs'] = 0;
            $filemanageroptions['maxfiles'] = 1;
            $filemanageroptions['mainfile'] = true;
            $mform->addElement('filemanager', 'presentation', get_string('selectfiles'),
                null, $filemanageroptions);
        }
    }

    /**
     * Function for showing the block for setting participant roles.
     *
     * @param object $mform
     * @param string $participantlist
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_participants(&$mform, $participantlist) {
        $participantselection = bigbluebuttonbn_get_participant_selection_data();
        $mform->addElement('header', 'permissions', get_string('mod_form_block_participants', 'bigbluebuttonbn'));
        $mform->setExpanded('permissions');
        $mform->addElement('hidden', 'participants', json_encode($participantlist));
        $mform->setType('participants', PARAM_TEXT);
        // Render elements for participant selection.
        $htmlselectiontype = html_writer::select($participantselection['type_options'],
            'bigbluebuttonbn_participant_selection_type', $participantselection['type_selected'], array(),
            array('id' => 'bigbluebuttonbn_participant_selection_type',
                  'onchange' => 'M.mod_bigbluebuttonbn.modform.participantSelectionSet(); return 0;'));
        $htmlselectionoptions = html_writer::select($participantselection['options'], 'bigbluebuttonbn_participant_selection',
            $participantselection['selected'], array(),
            array('id' => 'bigbluebuttonbn_participant_selection', 'disabled' => 'disabled'));
        $htmlselectioninput = html_writer::tag('input', '', array('id' => 'id_addselectionid',
            'type' => 'button', 'class' => 'btn btn-secondary',
            'value' => get_string('mod_form_field_participant_list_action_add', 'bigbluebuttonbn'),
            'onclick' => 'M.mod_bigbluebuttonbn.modform.participantAdd(); return 0;'
          ));
        $htmladdparticipant = html_writer::tag('div',
            $htmlselectiontype . '&nbsp;&nbsp;' . $htmlselectionoptions . '&nbsp;&nbsp;' . $htmlselectioninput, null);
        $mform->addElement('html', "\n\n");
        $mform->addElement('static', 'static_add_participant',
            get_string('mod_form_field_participant_add', 'bigbluebuttonbn'), $htmladdparticipant);
        $mform->addElement('html', "\n\n");
        // Declare the table.
        $htmltable = new html_table();
        $htmltable->align = array('left', 'left', 'left', 'left');
        $htmltable->id = 'participant_list_table';
        $htmltable->data = array(array());
        // Render elements for participant list.
        $htmlparticipantlist = html_writer::table($htmltable);
        $mform->addElement('html', "\n\n");
        $mform->addElement('static', 'static_participant_list',
            get_string('mod_form_field_participant_list', 'bigbluebuttonbn'), $htmlparticipantlist);
        $mform->addElement('html', "\n\n");
    }

    /**
     * Function for showing the client type
     *
     * @param object $mform
     * @param object $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_clienttype(&$mform, &$cfg) {
        // Validates if clienttype capability is enabled.
        if (!$cfg['clienttype_enabled']) {
            return;
        }
        // Validates if the html5client is supported by the BigBlueButton Server.
        if (!bigbluebuttonbn_has_html5_client()) {
            return;
        }
        $field = ['type' => 'hidden', 'name' => 'clienttype', 'data_type' => PARAM_INT,
            'description_key' => null];
        if ($cfg['clienttype_editable']) {
            $field['type'] = 'select';
            $field['data_type'] = PARAM_TEXT;
            $field['description_key'] = 'mod_form_field_block_clienttype';
             $choices = array(BIGBLUEBUTTON_CLIENTTYPE_FLASH => get_string('mod_form_block_clienttype_flash', 'bigbluebuttonbn'),
                             BIGBLUEBUTTON_CLIENTTYPE_HTML5 => get_string('mod_form_block_clienttype_html5', 'bigbluebuttonbn'));
             $mform->addElement('header', 'clienttypeselection', get_string('mod_form_block_clienttype', 'bigbluebuttonbn'));
            $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
                                    $field['description_key'], $cfg['clienttype_default'], $choices);
            return;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
                                null, $cfg['clienttype_default']);
    }

    /**
     * Function for showing the block for integration with the calendar.
     *
     * @param object $mform
     * @param object $activity
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_schedule(&$mform, &$activity) {
        $mform->addElement('header', 'schedule', get_string('mod_form_block_schedule', 'bigbluebuttonbn'));
        if (isset($activity->openingtime) && $activity->openingtime != 0 ||
            isset($activity->closingtime) && $activity->closingtime != 0) {
            $mform->setExpanded('schedule');
        }
        $mform->addElement('date_time_selector', 'openingtime',
            get_string('mod_form_field_openingtime', 'bigbluebuttonbn'), array('optional' => true));
        $mform->setDefault('openingtime', 0);
        $mform->addElement('date_time_selector', 'closingtime',
            get_string('mod_form_field_closingtime', 'bigbluebuttonbn'), array('optional' => true));
        $mform->setDefault('closingtime', 0);
    }

    /**
     * Function for showing an element.
     *
     * @param object $mform
     * @param string $type
     * @param string $name
     * @param string $datatype
     * @param string $descriptionkey
     * @param string $defaultvalue
     * @param array $options
     * @param string $rule
     * @return void
     */
    private function bigbluebuttonbn_mform_add_element(&$mform, $type, $name, $datatype,
            $descriptionkey, $defaultvalue = null, $options = null, $rule = null) {
        if ($type === 'hidden' || $type === 'static') {
            $mform->addElement($type, $name, $defaultvalue);
            $mform->setType($name, $datatype);
            return;
        }
        $mform->addElement($type, $name, get_string($descriptionkey, 'bigbluebuttonbn'), $options);
        if (get_string_manager()->string_exists($descriptionkey.'_help', 'bigbluebuttonbn')) {
            $mform->addHelpButton($name, $descriptionkey, 'bigbluebuttonbn');
        }
        if (!empty($rule)) {
            $mform->addRule($name, $rule['message'], $rule['type'], $rule['rule'], $rule['validator']);
        }
        $mform->setDefault($name, $defaultvalue);
        $mform->setType($name, $datatype);
    }
}
