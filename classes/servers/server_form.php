<?php
/**
 * This script is owned by CBlue SPRL, please contact CBlue regarding any licences issues.
 *
 * @date :       30/10/2020
 * @author:      rlemaire@cblue.be
 * @copyright:   CBlue SPRL, 2020
 */

namespace mod_bigbluebuttonbn\servers;

use coding_exception;
use core\form\persistent;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class server_form extends persistent
{
    /**
     * @var string Persistent class name.
     */
    protected static $persistentclass = 'mod_bigbluebuttonbn\\server';

    /**
     * Form definition.
     */
    protected function definition()
    {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('server_name', 'bigbluebuttonbn'));
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'url', get_string('server_url', 'bigbluebuttonbn'));
        $mform->addRule('url', get_string('required'), 'required', null, 'client');
        $mform->setType('url', PARAM_TEXT);

        $mform->addElement('text', 'secret', get_string('server_secret', 'bigbluebuttonbn'));
        $mform->addRule('secret', get_string('required'), 'required', null, 'client');
        $mform->setType('secret', PARAM_TEXT);

        $mform->addElement('text', 'weight', get_string('server_weight', 'bigbluebuttonbn'));
        $mform->addRule('weight', get_string('required'), 'required', null, 'client');
        $mform->setType('weight', PARAM_INT);

        $mform->addElement('selectyesno', 'enabled', get_string('server_enabled', 'bigbluebuttonbn'));
        $mform->setType('enabled', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Extra validation.
     *
     * @param stdClass $data Data to validate.
     * @param array $files Array of files.
     * @param array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     * @throws coding_exception
     */
    protected function extra_validation($data, $files, array &$errors)
    {
        $newerrors = [];

        // Check name.
        if ($data->weight < 1) {
            $newerrors['weight'] = get_string('server_weight_greater_zero', 'bigbluebuttonbn');
        }

        return $newerrors;
    }
}
