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
 * Renderer.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */

namespace mod_bigbluebuttonbn\output;

use renderable;
use renderer_base;
use templatable;
use html_table;
use html_writer;
use stdClass;
use coding_exception;
use mod_bigbluebuttonbn\plugin;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Class import_view
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */
class import_view implements renderable, templatable {

    /** @var array */
    private $context = [];

    /**
     * import_view constructor.
     *
     * @param stdClass $course
     * @param stdClass $bigbluebuttonbn
     * @param int $tc
     */
    public function __construct($course, $bigbluebuttonbn, $tc) {
        global $SESSION, $PAGE;
        $bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
        $options = bigbluebuttonbn_import_get_courses_for_select($bbbsession);
        $selected = isset($options[$tc]) ? $tc : '';
        $this->context['backactionurl'] = plugin::necurl('/mod/bigbluebuttonbn/view.php');
        $this->context['cmid'] = $PAGE->cm->id;
        if (!empty($options)) {
            $selectoptions = [];
            $toadd = ['value' => '', 'label' => get_string('choosedots')];
            if ('' == $tc) {
                $toadd['selected'] = true;
            }
            $selectoptions[] = $toadd;
            foreach ($options as $key => $option) {
                $toadd = ['value' => $key, 'label' => $option];
                if ($key == $tc) {
                    $toadd['selected'] = true;
                }
                $selectoptions[] = $toadd;
            }
            $this->context['hascontent'] = true;
            $this->context['selectoptions'] = $selectoptions;
            // Get course recordings.
            $bigbluebuttonbnid = null;
            if ($course->id == $selected) {
                $bigbluebuttonbnid = $bigbluebuttonbn->id;
            }
            $recordings = bigbluebuttonbn_get_allrecordings(
                $selected, $bigbluebuttonbnid, false,
                (boolean) \mod_bigbluebuttonbn\locallib\config::get('importrecordings_from_deleted_enabled')
            );
            // Exclude the ones that are already imported.
            if (!empty($recordings)) {
                $recordings = bigbluebuttonbn_unset_existent_recordings_already_imported(
                    $recordings, $course->id, $bigbluebuttonbn->id
                );
            }
            // Store recordings (indexed) in a session variable.
            $SESSION->bigbluebuttonbn_importrecordings = $recordings;
            // Proceed with rendering.
            if (!empty($recordings)) {
                $this->context['recordings'] = true;
                $this->context['recordingtable'] = bigbluebuttonbn_output_recording_table($bbbsession, $recordings, ['import']);
            }
            // JavaScript for locales.
            $PAGE->requires->strings_for_js(array_keys(bigbluebuttonbn_get_strings_for_js()), 'bigbluebuttonbn');
            // Require JavaScript modules.
            $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-imports', 'M.mod_bigbluebuttonbn.imports.init',
                array(array('bn' => $bigbluebuttonbn->id, 'tc' => $selected)));
            $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-broker', 'M.mod_bigbluebuttonbn.broker.init',
                array());
            $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-recordings', 'M.mod_bigbluebuttonbn.recordings.init',
                array(array('hide_table' => true, 'bbbid' => $bigbluebuttonbn->id))
            );
        }
    }

    /**
     * Defer to template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        return $this->context;
    }

}
