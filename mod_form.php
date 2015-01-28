<?php
/**
 * Config all BigBlueButtonBN instances in this course.
 * 
 * @package   mod_bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2014 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_bigbluebuttonbn_mod_form extends moodleform_mod {

    function definition() {

        global $CFG, $DB, $PAGE, $USER;

        $course_id = optional_param('course', 0, PARAM_INT); // course_module ID, or
        $course_module_id = optional_param('update', 0, PARAM_INT); // course_module ID, or
        if ($course_id) {
            $course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
            $bigbluebuttonbn = null;
        } else if ($course_module_id) {
            $cm = get_coursemodule_from_id('bigbluebuttonbn', $course_module_id, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
            $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
        }

        if ( $CFG->version < '2013111800' ) {
            //This is valid before v2.6
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
        } else {
            //This is valid after v2.6
            $context = context_course::instance($course->id);
        }
        //error_log('context: ' . print_r($context, true));
        
        //BigBlueButton server data
        $url = trim(trim($CFG->BigBlueButtonBNServerURL),'/').'/';
        $salt = trim($CFG->BigBlueButtonBNSecuritySalt);

        //Validates if the BigBlueButton server is running 
        $serverVersion = bigbluebuttonbn_getServerVersion($url); 
        if ( !isset($serverVersion) ) {
            print_error( 'general_error_unable_connect', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
        }

        $mform =& $this->_form;
        $current_activity =& $this->current;

        //-------------------------------------------------------------------------------
        // First block starts here
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('mod_form_block_general', 'bigbluebuttonbn'));

        $mform->addElement('text', 'name', get_string('mod_form_field_name','bigbluebuttonbn'), 'maxlength="64" size="32"' );
        $mform->addRule( 'name', null, 'required', null, 'client' );
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('textarea', 'welcome', get_string('mod_form_field_welcome','bigbluebuttonbn'), 'wrap="virtual" rows="5" cols="60"');
        $mform->addHelpButton('welcome', 'mod_form_field_welcome', 'bigbluebuttonbn');

        //$mform->addElement('text', 'voicebridge', get_string('mod_form_field_voicebridge','bigbluebuttonbn'), 'maxlength="5" size="10"' );
        //$mform->setDefault( 'voicebridge', 0 );
        //$mform->addHelpButton('voicebridge', 'mod_form_field_voicebridge', 'bigbluebuttonbn');

        $mform->addElement( 'checkbox', 'newwindow', get_string('mod_form_field_newwindow', 'bigbluebuttonbn') );
        $mform->setDefault( 'newwindow', 0 );

        $mform->addElement( 'checkbox', 'wait', get_string('mod_form_field_wait', 'bigbluebuttonbn') );
        $mform->setDefault( 'wait', 1 );
        //-------------------------------------------------------------------------------
        // First block ends here
        //-------------------------------------------------------------------------------
        
        
        //-------------------------------------------------------------------------------
        // Second block starts here
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('mod_form_block_participants', 'bigbluebuttonbn'));

        //$mform->addElement( 'checkbox', 'allmoderators', get_string('mod_form_field_allmoderators', 'bigbluebuttonbn') );
        //$mform->setDefault( 'allmoderators', 0 );

        // Data required for "Add participant" and initial "Participant list" setup
        $roles = bigbluebuttonbn_get_roles();
        $users = bigbluebuttonbn_get_users($context);

        $participant_list = bigbluebuttonbn_get_participant_list($bigbluebuttonbn != null? $bigbluebuttonbn: null);
        $mform->addElement('hidden', 'participants', json_encode($participant_list));
        $mform->setType('participants', PARAM_TEXT);
        
        $html_participant_selection = ''.
             '<div id="fitem_bigbluebuttonbn_participant_selection" class="fitem fitem_fselect">'."\n".
             '  <div class="fitemtitle">'."\n".
             '    <label for="bigbluebuttonbn_participant_selectiontype">'.get_string('mod_form_field_participant_add', 'bigbluebuttonbn').' </label>'."\n".
             '  </div>'."\n".
             '  <div class="felement fselect">'."\n".
             '    <select id="bigbluebuttonbn_participant_selection_type" onchange="bigbluebuttonbn_participant_selection_set(); return 0;">'."\n".
             '      <option value="all" selected="selected">'.get_string('mod_form_field_participant_list_type_all', 'bigbluebuttonbn').'</option>'."\n".
             '      <option value="role">'.get_string('mod_form_field_participant_list_type_role', 'bigbluebuttonbn').'</option>'."\n".
             '      <option value="user">'.get_string('mod_form_field_participant_list_type_user', 'bigbluebuttonbn').'</option>'."\n".
             '    </select>'."\n".
             '    &nbsp;&nbsp;'."\n".
             '    <select id="bigbluebuttonbn_participant_selection" disabled="disabled">'."\n".
             '      <option value="all" selected="selected">---------------</option>'."\n".
             '    </select>'."\n".
             '    &nbsp;&nbsp;'."\n".
             '    <input value="'.get_string('mod_form_field_participant_list_action_add', 'bigbluebuttonbn').'" type="button" id="id_addselectionid" onclick="bigbluebuttonbn_participant_add(); return 0;" />'."\n".
             '  </div>'."\n".
             '</div>'."\n".
             '<div id="fitem_bigbluebuttonbn_participant_list" class="fitem">'."\n".
             '  <div class="fitemtitle">'."\n".
             '    <label for="bigbluebuttonbn_participant_list">'.get_string('mod_form_field_participant_list', 'bigbluebuttonbn').' </label>'."\n".
             '  </div>'."\n".
             '  <div class="felement fselect">'."\n".
             '    <table id="participant_list_table">'."\n";
        
        // Add participant list
        foreach($participant_list as $participant){
            $participant_selectionid = '';
            $participant_selectiontype = $participant['selectiontype'];
            if( $participant_selectiontype == 'all') {
                $participant_selectiontype = '<b><i>'.get_string('mod_form_field_participant_list_type_'.$participant_selectiontype, 'bigbluebuttonbn').'</i></b>';
            } else {
                if ( $participant_selectiontype == 'role') {
                    $participant_selectionid = bigbluebuttonbn_get_role_name($participant['selectionid']);
                } else {
                    foreach($users as $user){
                        if( $user["id"] == $participant['selectionid']) {
                            $participant_selectionid = $user["name"];
                            break;
                        }
                    }
                }
                $participant_selectiontype = '<b><i>'.get_string('mod_form_field_participant_list_type_'.$participant_selectiontype, 'bigbluebuttonbn').':</i></b>&nbsp;';
            }
            $participant_role = get_string('mod_form_field_participant_bbb_role_'.$participant['role'], 'bigbluebuttonbn');
            
            $html_participant_selection .= ''.
                '      <tr id="participant_list_tr_'.$participant['selectiontype'].'-'.$participant['selectionid'].'">'."\n".
                '        <td width="20px"><a onclick="bigbluebuttonbn_participant_remove(\''.$participant['selectiontype'].'\', \''.$participant['selectionid'].'\'); return 0;" title="'.get_string('mod_form_field_participant_list_action_remove', 'bigbluebuttonbn').'">x</a></td>'."\n".
                '        <td width="125px">'.$participant_selectiontype.'</td>'."\n".
                '        <td>'.$participant_selectionid.'</td>'."\n".
                '        <td><i>&nbsp;'.get_string('mod_form_field_participant_list_text_as', 'bigbluebuttonbn').'&nbsp;</i>'."\n".
                '          <select id="participant_list_role_'.$participant['selectiontype'].'-'.$participant['selectionid'].'" onchange="bigbluebuttonbn_participant_list_role_update(\''.$participant['selectiontype'].'\', \''.$participant['selectionid'].'\'); return 0;">'."\n".
                '            <option value="'.BIGBLUEBUTTONBN_ROLE_VIEWER.'" '.($participant['role'] == BIGBLUEBUTTONBN_ROLE_VIEWER? 'selected="selected" ': '').'>'.get_string('mod_form_field_participant_bbb_role_'.BIGBLUEBUTTONBN_ROLE_VIEWER, 'bigbluebuttonbn').'</option>'."\n".
                '            <option value="'.BIGBLUEBUTTONBN_ROLE_MODERATOR.'" '.($participant['role'] == BIGBLUEBUTTONBN_ROLE_MODERATOR? 'selected="selected" ': '').'>'.get_string('mod_form_field_participant_bbb_role_'.BIGBLUEBUTTONBN_ROLE_MODERATOR, 'bigbluebuttonbn').'</option><select>'."\n".
                '        </td>'."\n".
                '      </tr>'."\n";
        }
        
        $html_participant_selection .= ''.
             '    </table>'."\n".
             '  </div>'."\n".
             '</div>'."\n".
             '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/bigbluebuttonbn/mod_form.js">'."\n".
             '</script>'."\n";

        $mform->addElement('html', $html_participant_selection);

        // Add data
        $mform->addElement('html', '<script type="text/javascript">var bigbluebuttonbn_participant_selection = {"all": [], "role": '.json_encode($roles).', "user": '.json_encode($users).'}; </script>');
        $mform->addElement('html', '<script type="text/javascript">var bigbluebuttonbn_participant_list = '.json_encode($participant_list).'; </script>');
        $bigbluebuttonbn_strings = Array( "as" => get_string('mod_form_field_participant_list_text_as', 'bigbluebuttonbn'),
                                          "viewer" => get_string('mod_form_field_participant_bbb_role_viewer', 'bigbluebuttonbn'),
                                          "moderator" => get_string('mod_form_field_participant_bbb_role_moderator', 'bigbluebuttonbn'),
                                          "remove" => get_string('mod_form_field_participant_list_action_remove', 'bigbluebuttonbn'),
                                    );
        $mform->addElement('html', '<script type="text/javascript">var bigbluebuttonbn_strings = '.json_encode($bigbluebuttonbn_strings).'; </script>');
        //-------------------------------------------------------------------------------
        // Second block ends here
        //-------------------------------------------------------------------------------
        
        
        //-------------------------------------------------------------------------------
        // Third block starts here
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('mod_form_block_schedule', 'bigbluebuttonbn'));

        $mform->addElement('date_time_selector', 'timeavailable', get_string('mod_form_field_availabledate', 'bigbluebuttonbn'), array('optional'=>true));
        $mform->setDefault('timeavailable', 0);
        $mform->addElement('date_time_selector', 'timedue', get_string('mod_form_field_duedate', 'bigbluebuttonbn'), array('optional' => true));
        $mform->setDefault('timedue', 0);
        //-------------------------------------------------------------------------------
        // Third block ends here
        //-------------------------------------------------------------------------------
        
        
        //-------------------------------------------------------------------------------
        // Fourth block starts here
        //-------------------------------------------------------------------------------
        if ( floatval($serverVersion) >= 0.8 ) {
            $mform->addElement('header', 'general', get_string('mod_form_block_record', 'bigbluebuttonbn'));

            $mform->addElement( 'checkbox', 'record', get_string('mod_form_field_record', 'bigbluebuttonbn') );
            $mform->setDefault( 'record', 0 );
	
            $mform->addElement('text', 'description', get_string('mod_form_field_description','bigbluebuttonbn'), 'maxlength="100" size="32"' );
            $mform->addHelpButton('description', 'mod_form_field_description', 'bigbluebuttonbn');
            $mform->setType('description', PARAM_TEXT);
            //$mform->addElement('duration', 'timeduration', get_string('mod_form_field_duration', 'bigbluebuttonbn')); //Set zero for unlimited
            //$mform->setDefault('timeduration', 14400);
            //$mform->addHelpButton('timeduration', 'mod_form_field_duration', 'bigbluebuttonbn');
        }
        //-------------------------------------------------------------------------------
        // Fourth block ends here
        //-------------------------------------------------------------------------------


        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //$this->standard_hidden_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
        
    }

    public function validation($data, $files) {
        $current_activity =& $this->current;
        
        $errors = parent::validation($data, $files);

        if ($data['timeavailable'] != 0 && $data['timedue'] != 0 && $data['timedue'] < $data['timeavailable']) {
            $errors['timedue'] = get_string('bbbduetimeoverstartingtime', 'bigbluebuttonbn');
        }
        
        return $errors;
    }
}

?>
