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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/recordingsbn/backup/moodle2/restore_recordingsbn_stepslib.php'); // Because it exists (must)

/**
 * recordingsbn restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_recordingsbn_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // recordingsbn only has one structure step
        $this->add_step(new restore_recordingsbn_activity_structure_step('recordingsbn_structure', 'recordingsbn.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        return array();
    }

    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('recordingsbn', 'add', 'view.php?id={course_module}', '{recordingsbn}');
        $rules[] = new restore_log_rule('recordingsbn', 'update', 'view.php?id={course_module}', '{recordingsbn}');
        $rules[] = new restore_log_rule('recordingsbn', 'view', 'view.php?id={course_module}', '{recordingsbn}');

        return $rules;
    }

    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('recordingsbn', 'view all', 'index.php?id={course}', null);

        return $rules;
    }

}
