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

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\helpers\roles;
use renderable;
use renderer_base;
use templatable;

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
     * @var instance $destinationinstance
     */
    protected $destinationinstance;

    /**
     * @var instance $sourceinstance
     */
    protected $sourceinstance;

    /**
     * @var $courseidscope int
     */
    protected $courseidscope;

    /**
     * import_view constructor.
     *
     * @param instance $destinationinstance
     * @param int $courseidscope
     * @param instance $frombbbiinstance $sourceinstance
     */
    public function __construct(instance $destinationinstance, ?int $courseidscope, ?instance $sourceinstance) {
        $this->destinationinstance = $destinationinstance;
        $this->courseidscope = $courseidscope;
        $this->sourceinstance = $sourceinstance;
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

        $courses = roles::bigbluebuttonbn_import_get_courses_for_select($this->destinationinstance);

        $context = (object) [
            'bbboriginid' => $this->destinationinstance->get_instance_id(),
            'has_recordings' => false,
        ];

        if (!empty($this->sourceinstance)) {
            $context->bbbid = $this->sourceinstance->get_instance_id();
            $context->search = [
                'value' => ''
            ];

            if ($this->sourceinstance->is_type_recordings_only()) {
                $context->has_recordings = true;
            } else if ($this->sourceinstance->is_type_room_and_recordings()) {
                $context->has_recordings = true;
            }
        }

        // Now the selects.
        if ($this->courseidscope) {
            $selectrecords = [];

            $cms = get_fast_modinfo($this->courseidscope)->instances['bigbluebuttonbn'];
            foreach ($cms as $cm) {
                if ($cm->id == $this->destinationinstance->get_cm_id()) {
                    // Skip the target instance.
                    continue;
                }

                if ($cm->deletioninprogress) {
                    // Check if the BBB is not currently scheduled for deletion.
                    continue;
                }

                $selectrecords[$cm->instance] = $cm->name;
            }
            $actionurl = $this->destinationinstance->get_import_url();
            $actionurl->param('courseidscope', $this->courseidscope);

            $select = new \single_select(
                $actionurl,
                'frombn',
                $selectrecords,
                empty($this->sourceinstance) ? 0 : $this->sourceinstance->get_instance_id()
            );
            $context->bbb_select = $select->export_for_template($output);
            $context->has_selected_course = true;
        }

        // Course selector.
        $context->course_select = (new \single_select(
            $this->destinationinstance->get_import_url(),
            'courseidscope',
            $courses,
            empty($this->courseidscope) ? 0 : $this->courseidscope
        ))->export_for_template($output);

        // Back button.
        $context->back_button = (new \single_button(
            $this->destinationinstance->get_view_url(),
            get_string('view_recording_button_return', 'mod_bigbluebuttonbn')
        ))->export_for_template($output);

        return $context;
    }

}
