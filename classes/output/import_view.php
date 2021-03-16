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

use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\local\view;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Class import_view
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */
class import_view implements renderable, templatable {

    /**
     * @var $origingbbbid int
     */
    protected $origingbbbid;
    /**
     * @var $frombbbid int
     */
    protected $frombbbid;

    /**
     * @var $courseidscope int
     */
    protected $courseidscope;

    /**
     * import_view constructor.
     *
     * @param int $origingbbbid
     * @param int $frombbbid
     * @param int $courseidscope
     */
    public function __construct($origingbbbid, $frombbbid, $courseidscope) {
        $this->origingbbbid = $origingbbbid;
        $this->frombbbid = $frombbbid;
        $this->courseidscope = $courseidscope;
    }

    /**
     * Defer to template.
     *
     * @param renderer_base $output
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     * @throws \required_capability_exception
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $DB;

        list('cm' => $origincm, 'course' => $origincourse, 'bigbluebuttonbn' => $originbigbluebuttonbn) =
            view::bigbluebuttonbn_view_instance_bigbluebuttonbn($this->origingbbbid);
        $bbbsession = bigbluebutton::build_bbb_session($origincm, $origincourse, $originbigbluebuttonbn);
        $courses = roles::bigbluebuttonbn_import_get_courses_for_select($bbbsession);

        $hasrecordings = !empty($this->frombbbid);
        if (!empty($this->frombbbid)) {
            $context['bbbid'] = $this->frombbbid;
            $context['bbboriginid'] = $this->origingbbbid;
            $searchbutton = [
                'value' => ''
            ];
            $context['search'] = $searchbutton;
            list('bigbluebuttonbn' => $frombigbluebuttonbn) =
                view::bigbluebuttonbn_view_instance_bigbluebuttonbn($this->frombbbid);
            $hasrecordings = $hasrecordings &&
                (in_array($frombigbluebuttonbn->type, [bbb_constants::BIGBLUEBUTTONBN_TYPE_ALL,
                    bbb_constants::BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY]));
        }
        $context['has_recordings'] = $hasrecordings;

        // Now the selects
        if ($this->courseidscope) {
            $bbbrecords = $DB->get_records('bigbluebuttonbn', array('course' => $this->courseidscope));
            $selectrecords = [];
            foreach ($bbbrecords as $record) {
                if ($record->id == $this->origingbbbid) {
                    continue;
                }
                // Check if the BBB is not currently scheduled for deletion.
                list('cm' => $cm) =
                    view::bigbluebuttonbn_view_instance_bigbluebuttonbn($record->id);
                if ($cm->deletioninprogress) {
                    continue;
                }

                $selectrecords[$record->id] = $record->name;
            }
            $actionurl = new moodle_url($PAGE->url);
            $actionurl->remove_all_params();
            $actionurl->param('originbn', $this->origingbbbid);
            $actionurl->param('courseidscope', $this->courseidscope);
            $select = new \single_select(
                $actionurl,
                'frombn',
                $selectrecords,
                empty($this->frombbbid) ? 0 : $this->frombbbid
            );
            $context['bbb_select'] = $select->export_for_template($output);
            $context['has_selected_course'] = true;
        }
        $actionurl = new moodle_url($PAGE->url);
        $actionurl->remove_all_params();
        $actionurl->param('originbn', $this->origingbbbid);
        $select = new \single_select(
            $actionurl,
            'courseidscope',
            $courses,
            empty($this->courseidscope) ? 0 : $this->courseidscope
        );
        $context['course_select'] = $select->export_for_template($output);

        $backurl = new moodle_url('/mod/bigbluebuttonbn/view.php', array(
            'id' => $origincm->id
        ));
        $button = new \single_button(
            $backurl,
            get_string('view_recording_button_return', 'mod_bigbluebuttonbn'));
        $context['back_button'] = $button->export_for_template($output);
        return $context;
    }

}