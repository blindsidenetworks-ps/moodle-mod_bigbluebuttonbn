<?php
/**
 * Defines the renderer for the bigbluebuttonbn module.
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
 
defined('MOODLE_INTERNAL') || die();


/**
 * The renderer for the bigbluebuttonbn module.
 *
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
class mod_bigbluebuttonbn_renderer extends plugin_renderer_base {
    
    public function view_page($course, $bigbluebuttonbn, $cm, $context, $viewobj) {
        $output = '';
        $output .= $this->view_information($bigbluebuttonbn, $cm, $context);
        return $output;
    }

    public function view_information($bigbluebuttonbn, $cm, $context) {
        global $CFG;

        $output = '';

        $output .= html_writer::start_tag('div');
        $output .= 'Hello world!';
        $output .= html_writer::end_tag('div');
        
        //$output .= html_writer::select($options, $name, $selected, false, $attributes)
        
        return $output;
    }

    
}

class mod_bigbluebuttonbn_view_object {
}