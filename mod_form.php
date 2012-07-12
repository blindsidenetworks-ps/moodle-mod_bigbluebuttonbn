<?php
/**
 * Config all BigBlueButtonBN instances in this course.
 * 
 * Authors:
 *    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 * 
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/locallib.php');

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_bigbluebuttonbn_mod_form extends moodleform_mod {

    function definition() {

        global $CFG, $PAGE;

        //Validates if the BigBlueButton server is running 
        //BigBlueButton server data
        $url = trim(trim($CFG->BigBlueButtonBNServerURL),'/').'/';
        $salt = trim($CFG->BigBlueButtonBNSecuritySalt);

        $serverVersion = bigbluebuttonbn_getServerVersion($url); 
        if ( !isset($serverVersion) ) {
            print_error( 'general_error_unable_connect', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
        }

        $mform =& $this->_form;
        $current_activity =& $this->current;

        $mform->addElement('header', 'general', get_string('mod_form_block_general', 'bigbluebuttonbn'));

        $mform->addElement('text', 'name', get_string('mod_form_field_name','bigbluebuttonbn'), 'maxlength="64" size="32"' );
        $mform->addRule( 'name', null, 'required', null, 'client' );

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
        // Second block starts here
        //-------------------------------------------------------------------------------
        //if ( $current_activity->section > 0 ) {  //This is not a general activity, it is part of a week, so it can have schedule 
            $mform->addElement('header', 'general', get_string('mod_form_block_schedule', 'bigbluebuttonbn'));

            $mform->addElement('date_time_selector', 'timeavailable', get_string('mod_form_field_availabledate', 'bigbluebuttonbn'), array('optional'=>true));
            $mform->setDefault('timeavailable', time());
            $mform->addElement('date_time_selector', 'timedue', get_string('mod_form_field_duedate', 'bigbluebuttonbn'), array('optional' => true));
            $mform->setDefault('timedue', time()+3600);
          
        //}
        //-------------------------------------------------------------------------------
        // Second block ends here
        //-------------------------------------------------------------------------------
        
        
        //-------------------------------------------------------------------------------
        // Third block starts here
        //-------------------------------------------------------------------------------
        if ( floatval($serverVersion) >= 0.8 ) {
            $mform->addElement('header', 'general', get_string('mod_form_block_record', 'bigbluebuttonbn'));

            $mform->addElement( 'checkbox', 'record', get_string('mod_form_field_record', 'bigbluebuttonbn') );
            $mform->setDefault( 'record', 0 );
	
            $mform->addElement('text', 'description', get_string('mod_form_field_description','bigbluebuttonbn'), 'maxlength="100" size="32"' );
            $mform->addElement('duration', 'timeduration', get_string('mod_form_field_duration', 'bigbluebuttonbn')); //Set zero for unlimited
            $mform->setDefault('timeduration', 14400);
            $mform->addHelpButton('timeduration', 'mod_form_field_duration', 'bigbluebuttonbn');
        }
        //-------------------------------------------------------------------------------
        // Third block ends here
        //-------------------------------------------------------------------------------


        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //$this->standard_hidden_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
        
        if ( isset($current_activity->add) ){ 
            // If is adding the activity, turn off the schedule by default & Take off the option [visible groups]
            //if ( $current_activity->section > 0 ) {  //This is not a general activity, it is part of a week, so it can have schedule 
                $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.modform_Adding_withSchedule');
            //} else {
            //    $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.modform_Adding_withoutSchedule');
            //}
        } else { 
            // Take off the option [visible groups]
            $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.modform_Editting');
        }
        
    }

    
    public function validation($data, $files) {
        $current_activity =& $this->current;
        
        $errors = parent::validation($data, $files);

        //if ( $current_activity->section > 0 ) {  //This is not a general activity, it is part of a week, so it can have schedule 
            // Check open and close times are consistent.
            if ($data['timeavailable'] != 0 && $data['timedue'] != 0 && $data['timedue'] < $data['timeavailable']) {
                $errors['timedue'] = get_string('bbbduetimeoverstartingtime', 'bigbluebuttonbn');
            }
        //}
        
        return $errors;
    }
    
    
}

?>
