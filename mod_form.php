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

        $participant_types = array(
                'all' => get_string('mod_form_field_participant_list_type_all', 'bigbluebuttonbn'),
                'role' => get_string('mod_form_field_participant_list_type_role', 'bigbluebuttonbn'),
                'user' => get_string('mod_form_field_participant_list_type_user', 'bigbluebuttonbn')
                );
        $participant_roles = array();
        foreach( role_fix_names(get_all_roles()) as $role ){
            if($role->archetype != 'guest' && $role->archetype != 'user' && $role->archetype != 'frontpage' && $role->archetype != 'coursecreator')
            $participant_roles[$role->archetype] = $role->localname;  
        }

        //var_dump($participant_roles);
        //var_dump($participant_roles[1]->shortname);
        //var_dump($participant_roles[1]->localname);
        //$coursecontext = get_course_context($course->id);
        //$participant_roles =  role_fix_names(get_all_roles($coursecontext), $coursecontext, ROLENAME_ALIAS);
        //$participant_users = bigbluebuttonbn_get_roster();

        $bbb_roles = array(
                BIGBLUEBUTTONBN_ROLE_VIEWER => get_string('mod_form_field_participant_bbb_role_viewer', 'bigbluebuttonbn'),
                BIGBLUEBUTTONBN_ROLE_MODERATOR => get_string('mod_form_field_participant_bbb_role_moderator', 'bigbluebuttonbn')
                );
        
        $participant_list = array();
        if( isset($this->curent->meetingid) ){
            $bigbluebuttonbn_participant_list = $DB->get_record('bigbluebuttonbn_participant', array('meetingid'=>$this->current->meetingid), '*', MUST_EXIST);
            foreach($bigbluebuttonbn_participant_list as $bigbluebuttonbn_participant){
                array_push($participant_list, array('selectiontype' => $bigbluebuttonbn_participant->selectiontype, 
                                                    'selectionid' => $bigbluebuttonbn_participant->selectionid, 
                                                    'role' => $bigbluebuttonbn_participant->role));
            }
        } else {
            array_push($participant_list, array('selectiontype' => 'all',
                                                'selectionid' => 'all',
                                                'role' => BIGBLUEBUTTONBN_ROLE_VIEWER));
            array_push($participant_list, array('selectiontype' => 'user',
                                                'selectionid' => $USER->id,
                                                'role' => BIGBLUEBUTTONBN_ROLE_MODERATOR));
        }

        
        $html_participant_selection = ''.
             '<div id="fitem_bigbluebuttonbn_participant_selection" class="fitem fitem_fselect">'."\n".
             '  <div class="fitemtitle">'."\n".
             '    <label for="bigbluebuttonbn_participant_selectiontype">Add participant </label>'."\n".
             '  </div>'."\n".
             '  <div class="felement fselect">'."\n".
             '    <select name="bigbluebuttonbn_participant_selection_type" id="bigbluebuttonbn_participant_selection_type" onchange="bigbluebuttonbn_set_participant_selection(); return 0;" name="selectiontype">'."\n".
             '      <option value="all" selected="selected">All users enrolled</option>'."\n".
             '      <option value="role">Role</option>'."\n".
             '      <option value="user">User</option>'."\n".
             '    </select>'."\n".
             '    &nbsp;&nbsp;'."\n".
             '    <select name="bigbluebuttonbn_participant_selection" id="bigbluebuttonbn_participant_selection" disabled="disabled">'."\n".
             '      <option value="all" selected="selected">---------------</option>'."\n".
             '    </select>'."\n".
             '    &nbsp;&nbsp;'."\n".
             '    <input name="addselectionid" value="Add" type="button" id="id_addselectionid" />'."\n".
             '  </div>'."\n".
             '</div>'."\n".
             '<div id="fitem_bigbluebuttonbn_participant_list" class="fitem">'."\n".
             '  <div class="fitemtitle">'."\n".
             '    <label for="bigbluebuttonbn_participant_list">Participant list </label>'."\n".
             '  </div>'."\n".
             '  <div class="felement fselect">'."\n".
             '    <table>'."\n";
        
        // Add participant list
        $participant_list = bigbluebuttonbn_get_participant_list($bigbluebuttonbn != null? $bigbluebuttonbn->id: null);
        foreach($participant_list as $participant){
            $participant_id = $participant['id'];
            $participant_selectiontype = $participant['selectiontype'];
            if( $participant_selectiontype == 'all') {
                $participant_selectionid = '';
                $participant_selectiontype = '<b><i>'.get_string('mod_form_field_participant_list_type_'.$participant_selectiontype, 'bigbluebuttonbn').'</i></b>';
            } else {
                if ( $participant_selectiontype == 'role') {
                    $participant_selectionid = bigbluebuttonbn_get_role_name($participant['selectionid']);
                } else {
                    $participant_selectionid = $participant['selectionid'];
                }
                $participant_selectiontype = '<b><i>'.get_string('mod_form_field_participant_list_type_'.$participant_selectiontype, 'bigbluebuttonbn').':</i></b>&nbsp;';
            }
            $participant_role = get_string('mod_form_field_participant_bbb_role_'.$participant['role'], 'bigbluebuttonbn');
            
            $html_participant_selection .= ''.
                '      <tr>'."\n".
                '        <td id="participant_selectiontype" width="150px">'.$participant_selectiontype.'</td>'."\n".
                '        <td id="participant_selectionid">'.$participant_selectionid.'</td>'."\n".
                '        <td id="participant_role"><i>&nbsp;as&nbsp;</i>'.$participant_role.'</td>'."\n".
                '      </tr>'."\n";
        }
        
        $html_participant_selection .= ''.
             '    </table>'."\n".
             '  </div>'."\n".
             '</div>'."\n".
             '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/bigbluebuttonbn/mod_form.js">'."\n".
             '</script>'."\n";
        /*
         * bigbluebuttonbn_set_participant_selection()
        */      
        $mform->addElement('html', $html_participant_selection);

        /*
        $attributes = array('id' => 'bigbluebuttonbn_participant_selectiontype', 
                            'onclick' => 'console.debug("Hello!"); return 0;'
                            );
        $select = $mform->addElement('select', 'selectiontype', get_string('mod_form_field_participant_add', 'bigbluebuttonbn'), $participant_types, $attributes);
        $select->setSelected('all');

        $attributes = array();
        $select = $mform->addElement('select', 'selectionid', null, array(), $attributes);

        $mform->addElement('button', 'addselectionid', 'Add');
        */        

        $mform->addElement('html', '<script type="text/javascript">var bigbluebuttonbn_participant_selection = {"all": [], "role": '.bigbluebuttonbn_get_roles_json().', "user": '.bigbluebuttonbn_get_users_json($context).'}; </script>');
        $mform->addElement('html', '<script type="text/javascript">var bigbluebuttonbn_participant_list = '.bigbluebuttonbn_get_participant_list_json($bigbluebuttonbn != null? $bigbluebuttonbn->id: null).'; </script>');
        $mform->addElement('html', '<div id="bigbluebuttonbn_participant_roles"></div>');
        $mform->addElement('html', '<div id="bigbluebuttonbn_participant_users"></div>');
        $mform->addElement('html', '<div id="bigbluebuttonbn_participant_list"></div>');
        
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
            $mform->addElement('duration', 'timeduration', get_string('mod_form_field_duration', 'bigbluebuttonbn')); //Set zero for unlimited
            $mform->setDefault('timeduration', 14400);
            $mform->addHelpButton('timeduration', 'mod_form_field_duration', 'bigbluebuttonbn');
            $mform->setType('description', PARAM_TEXT);
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
