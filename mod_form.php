<?php
/**
 * Config all BigBlueButtonBN instances in this course.
 *
 * @package   mod_bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_bigbluebuttonbn_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $DB, $USER, $BIGBLUEBUTTONBN_CFG, $PAGE, $OUTPUT;

        $course_id = optional_param('course', 0, PARAM_INT); // course ID, or
        $course_module_id = optional_param('update', 0, PARAM_INT); // course_module ID, or
        $bigbluebuttonbn = null;
        if ($course_id) {
            $course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
        } else if ($course_module_id) {
            $cm = get_coursemodule_from_id('bigbluebuttonbn', $course_module_id, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
            $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
        }

        $context = bigbluebuttonbn_get_context_course($course->id);

        //BigBlueButton server data
        $endpoint = bigbluebuttonbn_get_cfg_server_url();

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

        //Validates if the BigBlueButton server is running
        $serverVersion = bigbluebuttonbn_getServerVersion($endpoint);
        if ( !isset($serverVersion) ) {
            print_error( 'general_error_unable_connect', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
        }

        $mform =& $this->_form;
        $current_activity =& $this->current;

        //-------------------------------------------------------------------------------
        // First block starts here
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('mod_form_block_general', 'bigbluebuttonbn'));

        $mform->addElement('text', 'name', get_string('mod_form_field_name','bigbluebuttonbn'), 'maxlength="64" size="32"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $version_major = bigbluebuttonbn_get_moodle_version_major();
        if ( $version_major < '2015051100' ) {
            //This is valid before v2.9
            $this->add_intro_editor(false, get_string('mod_form_field_intro', 'bigbluebuttonbn'));
        } else {
            //This is valid after v2.9
            $this->standard_intro_elements(get_string('mod_form_field_intro', 'bigbluebuttonbn'));
        }
        $mform->setAdvanced('introeditor');
        $mform->setAdvanced('showdescription');

        $mform->addElement('textarea', 'welcome', get_string('mod_form_field_welcome','bigbluebuttonbn'), 'wrap="virtual" rows="5" cols="60"');
        $mform->addHelpButton('welcome', 'mod_form_field_welcome', 'bigbluebuttonbn');
        $mform->setType('welcome', PARAM_TEXT);
        $mform->setAdvanced('welcome');

        if ( $voicebridge_editable ) {
            $mform->addElement('text', 'voicebridge', get_string('mod_form_field_voicebridge','bigbluebuttonbn'), array('maxlength'=>4, 'size'=>6));
            $mform->addRule('voicebridge', get_string('mod_form_field_voicebridge_format_error', 'bigbluebuttonbn'), 'numeric', '####', 'server');
            $mform->setDefault( 'voicebridge', 0 );
            $mform->addHelpButton('voicebridge', 'mod_form_field_voicebridge', 'bigbluebuttonbn');
            $mform->setAdvanced('voicebridge');
        }
        $mform->setType('voicebridge', PARAM_INT);

        if ( $waitformoderator_editable ) {
            $mform->addElement('checkbox', 'wait', get_string('mod_form_field_wait', 'bigbluebuttonbn'));
            $mform->addHelpButton('wait', 'mod_form_field_wait', 'bigbluebuttonbn');
            $mform->setDefault( 'wait', $waitformoderator_default );
            $mform->setAdvanced('wait');
        } else {
            $mform->addElement('hidden', 'wait', $waitformoderator_default );
        }
        $mform->setType('wait', PARAM_INT);

        if ( $userlimit_editable ) {
            $mform->addElement('text', 'userlimit', get_string('mod_form_field_userlimit','bigbluebuttonbn'), 'maxlength="3" size="5"' );
            $mform->addHelpButton('userlimit', 'mod_form_field_userlimit', 'bigbluebuttonbn');
            $mform->setDefault( 'userlimit', $userlimit_default );
        } else {
            $mform->addElement('hidden', 'userlimit', $userlimit_default );
        }
        $mform->setType('userlimit', PARAM_TEXT);

        if ( floatval($serverVersion) >= 0.8 ) {
            if ( $recording_editable ) {
                $mform->addElement('checkbox', 'record', get_string('mod_form_field_record', 'bigbluebuttonbn'));
                $mform->setDefault( 'record', $recording_default );
                $mform->setAdvanced('record');
            } else {
                $mform->addElement('hidden', 'record', $recording_default);
            }
            $mform->setType('record', PARAM_INT);

            if ( $recording_tagging_editable ) {
                $mform->addElement('checkbox', 'tagging', get_string('mod_form_field_recordingtagging', 'bigbluebuttonbn'));
                $mform->setDefault('tagging', $recording_tagging_default);
                $mform->setAdvanced('tagging');
            } else {
                $mform->addElement('hidden', 'tagging', $recording_tagging_default );
            }
            $mform->setType('tagging', PARAM_INT);
        }

        if ( $sendnotifications_enabled ) {
            $mform->addElement('checkbox', 'notification', get_string('mod_form_field_notification', 'bigbluebuttonbn'));
            if ($this->current->instance) {
                $mform->addHelpButton('notification', 'mod_form_field_notification', 'bigbluebuttonbn');
            } else {
                $mform->addHelpButton('notification', 'mod_form_field_notification', 'bigbluebuttonbn');
            }
            $mform->setDefault('notification', 0);
        }
        $mform->setType('notification', PARAM_INT);
        //-------------------------------------------------------------------------------
        // First block ends here
        //-------------------------------------------------------------------------------


        //-------------------------------------------------------------------------------
        // Second block starts here
        //-------------------------------------------------------------------------------
        if ( $preuploadpresentation_enabled ) {
            $mform->addElement('header', 'preupload', get_string('mod_form_block_presentation', 'bigbluebuttonbn'));
            $mform->setExpanded('preupload');

            $filemanager_options = array();
            $filemanager_options['accepted_types'] = '*';
            $filemanager_options['maxbytes'] = 0;
            $filemanager_options['subdirs'] = 0;
            $filemanager_options['maxfiles'] = 1;
            $filemanager_options['mainfile'] = true;

            $mform->addElement('filemanager', 'presentation', get_string('selectfiles'), null, $filemanager_options);
        }
        //-------------------------------------------------------------------------------
        // Second block ends here
        //-------------------------------------------------------------------------------


        //-------------------------------------------------------------------------------
        // Third block starts here
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'permission', get_string('mod_form_block_participants', 'bigbluebuttonbn'));

        $participantselection = bigbluebuttonbn_get_participant_selection_data();
        $participantlist = bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context);

        $mform->addElement('hidden', 'participants', json_encode($participantlist));
        $mform->setType('participants', PARAM_TEXT);

        $this->mform_participant_renderer($mform, $context, $participantselection, $participantlist);
        //-------------------------------------------------------------------------------
        // Third block ends here
        //-------------------------------------------------------------------------------


        //-------------------------------------------------------------------------------
        // Fourth block starts here
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'schedule', get_string('mod_form_block_schedule', 'bigbluebuttonbn'));
        if( isset($current_activity->openingtime) && $current_activity->openingtime != 0 || isset($current_activity->closingtime) && $current_activity->closingtime != 0 )
            $mform->setExpanded('schedule');

        $mform->addElement('date_time_selector', 'openingtime', get_string('mod_form_field_openingtime', 'bigbluebuttonbn'), array('optional' => true));
        $mform->setDefault('openingtime', 0);
        $mform->addElement('date_time_selector', 'closingtime', get_string('mod_form_field_closingtime', 'bigbluebuttonbn'), array('optional' => true));
        $mform->setDefault('closingtime', 0);
        //-------------------------------------------------------------------------------
        // Fourth block ends here
        //-------------------------------------------------------------------------------


        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

        // JavaScript for locales.
        $PAGE->requires->strings_for_js(array_keys(bigbluebuttonbn_get_strings_for_js()), 'bigbluebuttonbn');

        if ($version_major >= '2016052300') {
            // Valid after v3.1
            $jsvars['participant_data'] = bigbluebuttonbn_get_participant_data($context);
            $jsvars['participant_list'] = bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context);
            $jsvars['icons_enabled'] = (boolean)bigbluebuttonbn_get_cfg_recording_icons_enabled();
            $jsvars['pix_icon_delete'] = (string)$OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle');
            $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-modform', 'M.mod_bigbluebuttonbn.modform.init', array($jsvars));
        }
    }

    function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            // Editing existing instance - copy existing files into draft area.
            try {
                $draftitemid = file_get_submitted_draft_itemid('presentation');
                file_prepare_draft_area($draftitemid, $this->context->id, 'mod_bigbluebuttonbn', 'presentation', 0, array('subdirs'=>0, 'maxbytes' => 0, 'maxfiles' => 1, 'mainfile' => true));
                $default_values['presentation'] = $draftitemid;
            } catch (Exception $e){
                return NULL;
            }
        }
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ( isset($data['openingtime']) && isset($data['closingtime']) ) {
            if ( $data['openingtime'] != 0 && $data['closingtime'] != 0 && $data['closingtime'] < $data['openingtime']) {
                $errors['closingtime'] = get_string('bbbduetimeoverstartingtime', 'bigbluebuttonbn');
            }
        }

        if ( isset($data['voicebridge']) ) {
            if ( !bigbluebuttonbn_voicebridge_unique($data['voicebridge'], $data['instance'])) {
                $errors['voicebridge'] = get_string('mod_form_field_voicebridge_notunique_error', 'bigbluebuttonbn');
            }
        }

        return $errors;
    }

    private function mform_participant_renderer($mform, $context, $participantselection, $participantlist) {
        $version_major = bigbluebuttonbn_get_moodle_version_major();
        if ($version_major < '2016052300') {
            //This is valid before v3.1
            $this->mform_participant_renderer_old_format($mform, $context, $participantselection, $participantlist);
            return;
        }
        //This is valid after v3.1
        $this->mform_participant_renderer_updated_format($mform, $context, $participantselection, $participantlist);
    }

    private function mform_participant_renderer_old_format($mform, $context, $participantselection, $participantlist) {
        global $CFG;
        $htmlparticipantselection = ''.
             '<div class="fitem fitem_fselect">'."\n".
             '  <div class="fitemtitle">'."\n".
             '    <label for="bigbluebuttonbn_participant_selectiontype">'.get_string('mod_form_field_participant_add', 'bigbluebuttonbn').' </label>'."\n".
             '  </div>'."\n".
             '  <div class="felement fselect">'."\n".
             '    <select id="bigbluebuttonbn_participant_selection_type" onchange="bigbluebuttonbn_participant_selection_set(); return 0;" class="select custom-select">'."\n".
             '      <option value="all" selected="selected">'.get_string('mod_form_field_participant_list_type_all', 'bigbluebuttonbn').'</option>'."\n".
             '      <option value="role">'.get_string('mod_form_field_participant_list_type_role', 'bigbluebuttonbn').'</option>'."\n".
             '      <option value="user">'.get_string('mod_form_field_participant_list_type_user', 'bigbluebuttonbn').'</option>'."\n".
             '    </select>'."\n".
             '    &nbsp;&nbsp;'."\n".
             '    <select id="bigbluebuttonbn_participant_selection" disabled="disabled" class="select custom-select">'."\n".
             '      <option value="all" selected="selected">---------------</option>'."\n".
             '    </select>'."\n".
             '    &nbsp;&nbsp;'."\n".
             '    <input value="'.get_string('mod_form_field_participant_list_action_add', 'bigbluebuttonbn').'" class="btn btn-secondary" type="button" id="id_addselectionid" onclick="bigbluebuttonbn_participant_add(); return 0;" />'."\n".
             '  </div>'."\n".
             '</div>'."\n";


        $htmlparticipantselection .= ''.
             '<div class="fitem">'."\n".
             '  <div class="fitemtitle">'."\n".
             '    <label for="bigbluebuttonbn_participant_list">'.get_string('mod_form_field_participant_list', 'bigbluebuttonbn').' </label>'."\n".
             '  </div>'."\n".
             '  <div class="felement fselect">'."\n".
             '    <table id="participant_list_table">'."\n";


        // Add participant list
        foreach($participantlist as $participant){
            $participantselectionid = '';
            $participantselectiontype = $participant['selectiontype'];
            if( $participantselectiontype == 'all') {
                $participantselectiontype = '<b><i>'.get_string('mod_form_field_participant_list_type_'.$participantselectiontype, 'bigbluebuttonbn').'</i></b>';
            } else {
                if ( $participantselectiontype == 'role') {
                    $participantselectionid = bigbluebuttonbn_get_role_name($participant['selectionid']);
                } else {
                    foreach($users as $user){
                        if( $user->id == $participant['selectionid']) {
                            $participantselectionid = $user->firstname.' '.$user->lastname;
                            break;
                        }
                    }
                }
                $participantselectiontype = '<b><i>'.get_string('mod_form_field_participant_list_type_'.$participantselectiontype, 'bigbluebuttonbn').':</i></b>&nbsp;';
            }

            $htmlparticipantselection .= ''.
                '      <tr id="participant_list_tr_'.$participant['selectiontype'].'-'.$participant['selectionid'].'">'."\n".
                '        <td width="20px"><a onclick="bigbluebuttonbn_participant_remove(\''.$participant['selectiontype'].'\', \''.$participant['selectionid'].'\'); return 0;" title="'.get_string('mod_form_field_participant_list_action_remove', 'bigbluebuttonbn').'" class="btn btn-link">x</a></td>'."\n".
                '        <td width="125px">'.$participantselectiontype.'</td>'."\n".
                '        <td>'.$participantselectionid.'</td>'."\n".
                '        <td><i>&nbsp;'.get_string('mod_form_field_participant_list_text_as', 'bigbluebuttonbn').'&nbsp;</i>'."\n".
                '          <select id="participant_list_role_'.$participant['selectiontype'].'-'.$participant['selectionid'].'" onchange="bigbluebuttonbn_participant_list_role_update(\''.$participant['selectiontype'].'\', \''.$participant['selectionid'].'\'); return 0;" class="select custom-select">'."\n".
                '            <option value="'.BIGBLUEBUTTONBN_ROLE_VIEWER.'" '.($participant['role'] == BIGBLUEBUTTONBN_ROLE_VIEWER? 'selected="selected" ': '').'>'.get_string('mod_form_field_participant_bbb_role_'.BIGBLUEBUTTONBN_ROLE_VIEWER, 'bigbluebuttonbn').'</option>'."\n".
                '            <option value="'.BIGBLUEBUTTONBN_ROLE_MODERATOR.'" '.($participant['role'] == BIGBLUEBUTTONBN_ROLE_MODERATOR? 'selected="selected" ': '').'>'.get_string('mod_form_field_participant_bbb_role_'.BIGBLUEBUTTONBN_ROLE_MODERATOR, 'bigbluebuttonbn').'</option><select>'."\n".
                '        </td>'."\n".
                '      </tr>'."\n";
        }

        $htmlparticipantselection .= ''.
             '    </table>'."\n".
             '  </div>'."\n".
             '</div>'."\n".
             '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/bigbluebuttonbn/mod_form.js">'."\n".
             '</script>'."\n";

        $mform->addElement('html', $htmlparticipantselection);

        // Data required for "Add participant" and initial "Participant list" setup
        $roles = bigbluebuttonbn_get_roles();
        $users = bigbluebuttonbn_get_users($context);

        // Add data
        $mform->addElement('html', '<script type="text/javascript">var bigbluebuttonbn_participant_selection = {"all": [], "role": '.json_encode($roles).', "user": '.bigbluebuttonbn_get_users_json($users).'}; </script>');
        $mform->addElement('html', '<script type="text/javascript">var bigbluebuttonbn_participant_list = '.json_encode($participantlist).'; </script>');
        $bigbluebuttonbn_strings = Array( "as" => get_string('mod_form_field_participant_list_text_as', 'bigbluebuttonbn'),
                                          "viewer" => get_string('mod_form_field_participant_bbb_role_viewer', 'bigbluebuttonbn'),
                                          "moderator" => get_string('mod_form_field_participant_bbb_role_moderator', 'bigbluebuttonbn'),
                                          "remove" => get_string('mod_form_field_participant_list_action_remove', 'bigbluebuttonbn'),
                                    );
        $mform->addElement('html', '<script type="text/javascript">var bigbluebuttonbn_strings = '.json_encode($bigbluebuttonbn_strings).'; </script>');

    }

    private function mform_participant_renderer_updated_format($mform, $context, $participantselection, $participantlist) {
        // Render elements for participant selection.
        $htmlselection = html_writer::tag('div',
            html_writer::select($participantselection['type_options'], 'bigbluebuttonbn_participant_selection_type',
                $participantselection['type_selected'], array(),
                array('id' => 'bigbluebuttonbn_participant_selection_type',
                      'onchange' => 'M.mod_bigbluebuttonbn.modform.participant_selection_set(); return 0;')).'&nbsp;&nbsp;'.
            html_writer::select($participantselection['options'], 'bigbluebuttonbn_participant_selection',
                $participantselection['selected'], array(),
                array('id' => 'bigbluebuttonbn_participant_selection', 'disabled' => 'disabled')).'&nbsp;&nbsp;'.
            html_writer::tag('input', '', array('id' => 'id_addselectionid', 'type' => 'button', 'class' => 'btn btn-secondary',
                'value' => get_string('mod_form_field_participant_list_action_add', 'bigbluebuttonbn'),
                'onclick' => 'M.mod_bigbluebuttonbn.modform.participant_add(); return 0;'
                ))
        );

        $mform->addElement('html', "\n\n");
        $mform->addElement('static', 'static_add_participant',
            get_string('mod_form_field_participant_add', 'bigbluebuttonbn'), $htmlselection);
        $mform->addElement('html', "\n\n");

        // Declare the table.
        $table = new html_table();
        $table->id = 'participant_list_table';
        $table->data = array();

        // Render elements for participant list.
        $htmllist = html_writer::tag('div',
            html_writer::label(get_string('mod_form_field_participant_list', 'bigbluebuttonbn'),
                'bigbluebuttonbn_participant_list').
            html_writer::table($table)
        );

        $mform->addElement('html', "\n\n");
        $mform->addElement('static', 'participant_list', '', $htmllist);
        $mform->addElement('html', "\n\n");
    }
}
