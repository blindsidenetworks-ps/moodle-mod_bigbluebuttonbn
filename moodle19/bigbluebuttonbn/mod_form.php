<?php

/**
 * Apply settings.
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *      Jesus Federico (jesus [at] blindsidenetworks [dt] org)
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_bigbluebuttonbn_mod_form extends moodleform_mod {

    function definition() {

        global $course, $CFG;
        $mform =& $this->_form;

	$mform->addElement('text', 'name', get_string('bigbluebuttonbnname','bigbluebuttonbn') );
	$mform->addRule( 'name', null, 'required', null, 'client' );

	$mform->addElement( 'checkbox', 'wait', get_string('bbbuserwait', 'bigbluebuttonbn') );
	$mform->setDefault( 'wait', 1 );

	#$mform->setHelpButton('moderatorpw', array('moderatorpw', get_string('bigbluebuttonbnmodpw', 'bigbluebuttonbn' )),true);

#	echo '<pre>';
#   	var_dump( $CFG );
#	echo '</pre>';


//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $features = new stdClass;
        $features->groups = true;
        $features->grouping = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules

        $this->add_action_buttons();
    }
}














?>
