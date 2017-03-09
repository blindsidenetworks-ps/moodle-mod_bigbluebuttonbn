<?php
// This file is part of Moodle - http://moodle.org/

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Config all BigBlueButtonBN instances in this course.
 *
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__).'/locallib.php';
require_once $CFG->dirroot.'/course/moodleform_mod.php';

class mod_bigbluebuttonbn_mod_form extends moodleform_mod
{
    public function definition()
    {
        global $CFG, $DB, $OUTPUT, $PAGE;

        $course_id = optional_param('course', 0, PARAM_INT); // course ID, or
        $course_module_id = optional_param('update', 0, PARAM_INT); // course_module ID, or
        if ($course_id) {
            $course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
            $bigbluebuttonbn = null;
        } else {
            $cm = get_coursemodule_from_id('bigbluebuttonbn', $course_module_id, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
            $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
        }

        $context = bigbluebuttonbn_get_context_course($course->id);

        //UI configuration options
        $voicebridge_editable = bigbluebuttonbn_get_cfg_voicebridge_editable();
        $recording_default = bigbluebuttonbn_get_cfg_recording_default();
        $recording_editable = bigbluebuttonbn_get_cfg_recording_editable();
        $recording_tagging_default = bigbluebuttonbn_get_cfg_recording_tagging_default();
        $recording_tagging_editable = bigbluebuttonbn_get_cfg_recording_tagging_editable();
        $waitformoderator_default = bigbluebuttonbn_get_cfg_waitformoderator_default();
        $waitformoderator_editable = bigbluebuttonbn_get_cfg_waitformoderator_editable();
        $userlimit_default = bigbluebuttonbn_get_cfg_userlimit_default();
        $userlimit_editable = bigbluebuttonbn_get_cfg_userlimit_editable();
        $preuploadpresentation_enabled = bigbluebuttonbn_get_cfg_preuploadpresentation_enabled();
        $sendnotifications_enabled = bigbluebuttonbn_get_cfg_sendnotifications_enabled();
        $recordings_html_default = bigbluebuttonbn_get_cfg_recordings_html_default();
        $recordings_html_editable = bigbluebuttonbn_get_cfg_recordings_html_editable();
        $recordings_deleted_activities_default = bigbluebuttonbn_get_cfg_recordings_deleted_activities_default();
        $recordings_deleted_activities_editable = bigbluebuttonbn_get_cfg_recordings_deleted_activities_editable();
        $recording_icons_enabled = bigbluebuttonbn_get_cfg_recording_icons_enabled();
        $pix_icon_delete_url = ''.$OUTPUT->pix_url('t/delete', 'moodle');

        $instance_type_enabled = true;
        $instance_type_default = BIGBLUEBUTTONBN_TYPE_ALL;

        //Validates if the BigBlueButton server is running
        $endpoint = bigbluebuttonbn_get_cfg_server_url();
        $serverVersion = bigbluebuttonbn_getServerVersion($endpoint);
        if (!isset($serverVersion)) {
            print_error('general_error_unable_connect', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
        }

        $mform = &$this->_form;
        $current_activity = &$this->current;

        $instance_type_profiles = bigbluebuttonbn_get_instance_type_profiles();

        if ($instance_type_enabled) {
            $mform->addElement('select', 'type', get_string('mod_form_field_instanceprofiles', 'bigbluebuttonbn'), bigbluebuttonbn_get_instance_types_array($instance_type_profiles), array('onchange' => 'M.mod_bigbluebuttonbn.mod_form_update_instance_type_profile(this);'));
            $mform->addHelpButton('type', 'mod_form_field_instanceprofiles', 'bigbluebuttonbn');
        } else {
            $mform->addElement('hidden', 'type', $instance_type_default);
        }

        $jsvars = array(
            'instance_type_profiles' => $instance_type_profiles,
            'icons_enabled' => $recording_icons_enabled,
            'pix_icon_delete' => $pix_icon_delete_url,
        );

        //-------------------------------------------------------------------------------
        // First block starts here
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('mod_form_block_general', 'bigbluebuttonbn'));

        $mform->addElement('text', 'name', get_string('mod_form_field_name', 'bigbluebuttonbn'), 'maxlength="64" size="32"');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $version_major = bigbluebuttonbn_get_moodle_version_major();
        if ($version_major < '2015051100') {
            //This is valid before v2.9
            $this->add_intro_editor(false, get_string('mod_form_field_intro', 'bigbluebuttonbn'));
        } else {
            //This is valid after v2.9
            $this->standard_intro_elements(get_string('mod_form_field_intro', 'bigbluebuttonbn'));
        }
        $mform->setAdvanced('introeditor');
        $mform->setAdvanced('showdescription');

        if ($sendnotifications_enabled) {
            $mform->addElement('checkbox', 'notification', get_string('mod_form_field_notification', 'bigbluebuttonbn'));
            if ($this->current->instance) {
                $mform->addHelpButton('notification', 'mod_form_field_notification', 'bigbluebuttonbn');
            } else {
                $mform->addHelpButton('notification', 'mod_form_field_notification', 'bigbluebuttonbn');
            }
            $mform->setDefault('notification', 0);
            $mform->setType('notification', PARAM_INT);
        }
        //-------------------------------------------------------------------------------
        // First block ends here
        //-------------------------------------------------------------------------------

        //-------------------------------------------------------------------------------
        // Second block starts here
        //-------------------------------------------------------------------------------
        if ($voicebridge_editable || $waitformoderator_editable || $userlimit_editable || $recording_editable || $recording_tagging_editable) {
            $mform->addElement('header', 'room', get_string('mod_form_block_room', 'bigbluebuttonbn'));
            $mform->addElement('textarea', 'welcome', get_string('mod_form_field_welcome', 'bigbluebuttonbn'), 'wrap="virtual" rows="5" cols="60"');
            $mform->addHelpButton('welcome', 'mod_form_field_welcome', 'bigbluebuttonbn');
            $mform->setType('welcome', PARAM_TEXT);

            if ($voicebridge_editable) {
                $mform->addElement('text', 'voicebridge', get_string('mod_form_field_voicebridge', 'bigbluebuttonbn'), array('maxlength' => 4, 'size' => 6));
                $mform->addRule('voicebridge', get_string('mod_form_field_voicebridge_format_error', 'bigbluebuttonbn'), 'numeric', '####', 'server');
                $mform->setDefault('voicebridge', 0);
                $mform->addHelpButton('voicebridge', 'mod_form_field_voicebridge', 'bigbluebuttonbn');
            }
            $mform->setType('voicebridge', PARAM_INT);

            if ($waitformoderator_editable) {
                add_form_element($mform, 'checkbox', 'wait', PARAM_INT, 'mod_form_field_wait', $waitformoderator_default);
            } else {
                add_form_element($mform, 'hidden', 'wait', PARAM_INT, null, $waitformoderator_default);
            }

            if ($userlimit_editable) {
                add_form_element($mform, 'text', 'userlimit', PARAM_TEXT, 'mod_form_field_userlimit', $userlimit_default);
            } else {
                add_form_element($mform, 'hidden', 'userlimit', PARAM_INT, null, $userlimit_default);
            }

            if (floatval($serverVersion) >= 0.8) {
                if ($recording_editable) {
                    add_form_element($mform, 'checkbox', 'record', PARAM_INT, 'mod_form_field_record', $recording_default);
                } else {
                    add_form_element($mform, 'hidden', 'record', PARAM_INT, null, $recording_default);
                }

                if ($recording_tagging_editable) {
                    add_form_element($mform, 'checkbox', 'tagging', PARAM_INT, 'mod_form_field_recordingtagging', $recording_tagging_default);
                } else {
                    add_form_element($mform, 'hidden', 'tagging', PARAM_INT, null, $recording_tagging_default);
                }
                $mform->setType('tagging', PARAM_INT);
            }
        }

        if ($recordings_html_editable || $recordings_deleted_activities_editable) {
            $mform->addElement('header', 'recordings', get_string('mod_form_block_recordings', 'bigbluebuttonbn'));

            if ($recordings_html_editable) {
                add_form_element($mform, 'checkbox', 'recordings_html', PARAM_INT, 'mod_form_field_recordings_html', $recordings_html_default);
            } else {
                add_form_element($mform, 'hidden', 'recordings_html', PARAM_INT, null, $recordings_html_default);
            }

            if ($recordings_deleted_activities_editable) {
                add_form_element($mform, 'checkbox', 'recordings_deleted_activities', PARAM_INT, 'mod_form_field_recordings_deleted_activities', $recordings_deleted_activities_default);
            } else {
                add_form_element($mform, 'hidden', 'recordings_deleted_activities', PARAM_INT, null, $recordings_deleted_activities_default);
            }
        }
        //-------------------------------------------------------------------------------
        // Second block ends here
        //-------------------------------------------------------------------------------

        //-------------------------------------------------------------------------------
        // Third block starts here
        //-------------------------------------------------------------------------------
        if ($preuploadpresentation_enabled) {
            $mform->addElement('header', 'preuploadpresentation', get_string('mod_form_block_presentation', 'bigbluebuttonbn'));
            $mform->setExpanded('preuploadpresentation');

            $filemanager_options = array();
            $filemanager_options['accepted_types'] = '*';
            $filemanager_options['maxbytes'] = 0;
            $filemanager_options['subdirs'] = 0;
            $filemanager_options['maxfiles'] = 1;
            $filemanager_options['mainfile'] = true;

            $mform->addElement('filemanager', 'presentation', get_string('selectfiles'), null, $filemanager_options);
        }
        //-------------------------------------------------------------------------------
        // Third block ends here
        //-------------------------------------------------------------------------------

        //-------------------------------------------------------------------------------
        // Fourth block starts here
        //-------------------------------------------------------------------------------
        $strings = array();
        $strings['as'] = get_string('mod_form_field_participant_list_text_as', 'bigbluebuttonbn');
        $strings['viewer'] = get_string('mod_form_field_participant_bbb_role_viewer', 'bigbluebuttonbn');
        $strings['moderator'] = get_string('mod_form_field_participant_bbb_role_moderator', 'bigbluebuttonbn');
        $strings['remove'] = get_string('mod_form_field_participant_list_action_remove', 'bigbluebuttonbn');
        $jsvars['strings'] = $strings;

        $mform->addElement('header', 'permissions', get_string('mod_form_block_participants', 'bigbluebuttonbn'));
        $mform->setExpanded('permissions');

        $participant_list = bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context);
        $mform->addElement('hidden', 'participants', json_encode($participant_list));
        $mform->setType('participants', PARAM_TEXT);

        // Data for participant selection
        $participant_selection_type_options = [
            'all' => get_string('mod_form_field_participant_list_type_all', 'bigbluebuttonbn'),
            'role' => get_string('mod_form_field_participant_list_type_role', 'bigbluebuttonbn'),
            'user' => get_string('mod_form_field_participant_list_type_user', 'bigbluebuttonbn'),
        ];
        $participant_selection_type_selected = 'all';
        $participant_selection_options = ['all' => '---------------'];
        $participant_selection_selected = 'all';

        // Render elements for participant selection
        $html_my_participant_selection = html_writer::tag('div',
            html_writer::select($participant_selection_type_options, 'bigbluebuttonbn_participant_selection_type', $participant_selection_type_selected, array(), array('id' => 'bigbluebuttonbn_participant_selection_type', 'onchange' => 'M.mod_bigbluebuttonbn.mod_form_participant_selection_set(); return 0;')).
            '&nbsp;&nbsp;'.
            html_writer::select($participant_selection_options, 'bigbluebuttonbn_participant_selection', $participant_selection_selected, array(), array('id' => 'bigbluebuttonbn_participant_selection', 'disabled' => 'disabled')).
            '&nbsp;&nbsp;'.
            '<input value="'.get_string('mod_form_field_participant_list_action_add', 'bigbluebuttonbn').'" class="btn btn-secondary" type="button" id="id_addselectionid" onclick="M.mod_bigbluebuttonbn.mod_form_participant_add(); return 0;" />'
        );

        $mform->addElement('html', "\n\n");
        $mform->addElement('static', 'my_add_participant', get_string('mod_form_field_participant_add', 'bigbluebuttonbn'), $html_my_participant_selection);
        $mform->addElement('html', "\n\n");

        // Data for participant list
        /// Data required for "Add participant" and initial "Participant list" setup
        $roles = bigbluebuttonbn_get_roles($context);
        $users = bigbluebuttonbn_get_users($context);
        /// Declare the table
        $table = new html_table();
        $table->id = 'participant_list_table';
        $table->data = array();
        ///Build table content
        foreach ($participant_list as $participant) {
            $participant_selectionid = '';
            $participant_selectiontype = $participant['selectiontype'];
            if ($participant_selectiontype == 'role') {
                $participant_selectionid = $roles[$participant['selectionid']];
            } else {
                foreach ($users as $user) {
                    if ($user->id == $participant['selectionid']) {
                        $participant_selectionid = fullname($user);
                        break;
                    }
                }
            }
            $participant_selectiontype = '<b><i>'.get_string('mod_form_field_participant_list_type_'.$participant_selectiontype, 'bigbluebuttonbn').'</i></b>';

            $row = new html_table_row();
            $row->id = 'participant_list_tr_'.$participant['selectiontype'].'-'.$participant['selectionid'];

            $col0 = new html_table_cell();
            $col0->text = $participant_selectiontype;
            $col1 = new html_table_cell();
            $col1->text = $participant_selectionid;
            $col2 = new html_table_cell();
            $options = [
                BIGBLUEBUTTONBN_ROLE_VIEWER => get_string('mod_form_field_participant_bbb_role_'.BIGBLUEBUTTONBN_ROLE_VIEWER, 'bigbluebuttonbn'),
                BIGBLUEBUTTONBN_ROLE_MODERATOR => get_string('mod_form_field_participant_bbb_role_'.BIGBLUEBUTTONBN_ROLE_MODERATOR, 'bigbluebuttonbn'),
            ];
            $option_selected = $participant['role'];
            $col2->text = html_writer::tag('i', '&nbsp;'.get_string('mod_form_field_participant_list_text_as', 'bigbluebuttonbn').'&nbsp;'.
                html_writer::select($options,
                    'participant_list_role_'.$participant['selectiontype'].'-'.$participant['selectionid'],
                    $option_selected, array(),
                    array('id' => 'participant_list_role_'.$participant['selectiontype'].'-'.$participant['selectionid'],
                        'onchange' => 'M.mod_bigbluebuttonbn.mod_form_participant_list_role_update(\''.$participant['selectiontype'].'\', \''.$participant['selectionid'].'\'); return 0;',
                    )
                )
            );
            $col3 = new html_table_cell();
            $onclick = 'M.mod_bigbluebuttonbn.mod_form_participant_remove(\''.$participant['selectiontype'].'\', \''.$participant['selectionid'].'\'); return 0;';
            if ($recording_icons_enabled) {
                //With icon for delete
                $pix_icon_delete = html_writer::tag('img', null, array('class' => 'btn icon smallicon',
                                                                        'title' => $strings['remove'],
                                                                        'alt' => $strings['remove'],
                                                                        'src' => $pix_icon_delete_url, ));
                $col3->text = html_writer::tag('a', $pix_icon_delete, array('class' => 'action_icon',
                    'onclick' => $onclick, 'title' => $strings['remove'], ));
            } else {
                //With text for delete
                $col3->text = html_writer::tag('a', '<b>x</b>', array('class' => 'btn action_icon',
                    'onclick' => $onclick, 'title' => $strings['remove'], ));
            }

            $row->cells = array($col0, $col1, $col2, $col3);
            array_push($table->data, $row);
        }

        // Render elements for participant list
        $html_participant_list = html_writer::tag('div',
            html_writer::label(get_string('mod_form_field_participant_list', 'bigbluebuttonbn'), 'bigbluebuttonbn_participant_list').
            html_writer::table($table)
        );

        $mform->addElement('html', "\n\n");
        $mform->addElement('static', 'participant_list', '', $html_participant_list);
        $mform->addElement('html', "\n\n");

        // Add data
        $jsvars['participant_selection'] = json_decode('{"all": [], "role": '.
            json_encode(bigbluebuttonbn_get_roles_select($roles)).', "user": '.
            json_encode(bigbluebuttonbn_get_users_select($users)).'}');
        $jsvars['participant_list'] = $participant_list;
        //-------------------------------------------------------------------------------
        // Fourth block ends here
        //-------------------------------------------------------------------------------

        //-------------------------------------------------------------------------------
        // Fifth block starts here
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'schedule', get_string('mod_form_block_schedule', 'bigbluebuttonbn'));
        if (isset($current_activity->openingtime) && $current_activity->openingtime != 0 ||
            isset($current_activity->closingtime) && $current_activity->closingtime != 0) {
            $mform->setExpanded('schedule');
        }

        $mform->addElement('date_time_selector', 'openingtime', get_string('mod_form_field_openingtime', 'bigbluebuttonbn'),
            array('optional' => true));
        $mform->setDefault('openingtime', 0);
        $mform->addElement('date_time_selector', 'closingtime', get_string('mod_form_field_closingtime', 'bigbluebuttonbn'),
            array('optional' => true));
        $mform->setDefault('closingtime', 0);
        //-------------------------------------------------------------------------------
        // Fifth block ends here
        //-------------------------------------------------------------------------------

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

        $PAGE->requires->data_for_js('bigbluebuttonbn', $jsvars);
        $jsmodule = array(
            'name' => 'mod_bigbluebuttonbn',
            'fullpath' => '/mod/bigbluebuttonbn/mod_form.js',
        );
        $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.mod_form_init', array(), false, $jsmodule);
    }

    public function data_preprocessing(&$default_values)
    {
        if ($this->current->instance) {
            // Editing existing instance - copy existing files into draft area.
            try {
                $draftitemid = file_get_submitted_draft_itemid('presentation');
                file_prepare_draft_area($draftitemid, $this->context->id, 'mod_bigbluebuttonbn', 'presentation', 0,
                    array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1, 'mainfile' => true)
                );
                $default_values['presentation'] = $draftitemid;
            } catch (Exception $e) {
                error_log('Presentation could not be loaded: '.$e->getMessage());

                return null;
            }
        }
    }

    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);

        if (isset($data['openingtime']) && isset($data['closingtime'])) {
            if ($data['openingtime'] != 0 && $data['closingtime'] != 0 && $data['closingtime'] < $data['openingtime']) {
                $errors['closingtime'] = get_string('bbbduetimeoverstartingtime', 'bigbluebuttonbn');
            }
        }

        if (isset($data['voicebridge'])) {
            if (!bigbluebuttonbn_voicebridge_unique($data['voicebridge'], $data['instance'])) {
                $errors['voicebridge'] = get_string('mod_form_field_voicebridge_notunique_error', 'bigbluebuttonbn');
            }
        }

        return $errors;
    }

    private function add_form_element($mform, $elementType, $elementName, $elementDataType,
            $elementDescriptionKey, $elementDefaultValue = null)
    {
        if (!is_null($elementDescriptionKey) && !empty($elementDescriptionKey)) {
            $mform->addElement($elementType, $elementName, get_string($elementDescriptionKey, 'bigbluebuttonbn'));
            $mform->addHelpButton($elementName, $elementDescriptionKey, 'bigbluebuttonbn');
            $mform->setDefault($elementName, $elementDefaultValue);
        } else {
            $mform->addElement($elementType, $elementName, $elementDefaultValue);
        }
        $mform->setType($elementName, $elementDataType);
    }
}
