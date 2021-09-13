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
 * The mod_bigbluebuttonbn files helper
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */

namespace mod_bigbluebuttonbn\local\helpers;

use cache;
use cache_store;
use context;
use context_module;
use context_system;
use mod_bigbluebuttonbn\instance;
use moodle_url;
use stdClass;

/**
 * Utility class for all files routines helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class files {

    /**
     * Helper for validating pluginfile.
     *
     * @param stdClass $context context object
     * @param string $filearea file area
     *
     * @return false|null false if file not valid
     */
    public static function pluginfile_valid($context, $filearea) {

        // Can be in context module or in context_system (if is the presentation by default).
        if (!in_array($context->contextlevel, array(CONTEXT_MODULE, CONTEXT_SYSTEM))) {
            return false;
        }

        if (!array_key_exists($filearea, self::get_file_areas())) {
            return false;
        }

        return true;
    }

    /**
     * Helper for getting pluginfile.
     *
     * @param stdClass $course course object
     * @param \cm_info $cm course module object
     * @param context $context context object
     * @param string $filearea file area
     * @param array $args extra arguments
     *
     * @return \stored_file|bool
     */
    public static function pluginfile_file($course, $cm, $context, $filearea, $args) {
        $filename = self::get_plugin_filename($course, $cm, $context, $args);
        if (!$filename) {
            return false;
        }
        $fullpath = "/$context->id/mod_bigbluebuttonbn/$filearea/0/" . $filename;
        $fs = get_file_storage();
        $file = $fs->get_file_by_hash(sha1($fullpath));
        if (!$file || $file->is_directory()) {
            return false;
        }
        return $file;
    }

    /**
     * Get a full path to the file attached as a preuploaded presentation
     * or if there is none, set the presentation field will be set to blank.
     *
     * @param object $bigbluebuttonformdata BigBlueButtonBN form data
     * Note that $bigbluebuttonformdata->presentation is the id of the filearea whereas the bbb instance table
     * stores the file name/path
     * @return string
     */
    public static function save_media_file(&$bigbluebuttonformdata) {
        if (!isset($bigbluebuttonformdata->presentation) || $bigbluebuttonformdata->presentation == '') {
            return '';
        }
        $context = context_module::instance($bigbluebuttonformdata->coursemodule);
        // Set the filestorage object.
        $fs = get_file_storage();
        // Save the file if it exists that is currently in the draft area.
        file_save_draft_area_files($bigbluebuttonformdata->presentation, $context->id, 'mod_bigbluebuttonbn', 'presentation', 0);
        // Get the file if it exists.
        $files = $fs->get_area_files(
            $context->id,
            'mod_bigbluebuttonbn',
            'presentation',
            0,
            'itemid, filepath, filename',
            false
        );
        // Check that there is a file to process.
        $filesrc = '';
        if (count($files) == 1) {
            // Get the first (and only) file.
            $file = reset($files);
            $filesrc = '/' . $file->get_filename();
        }
        return $filesrc;
    }

    /**
     * Helper return array containing the file descriptor for a preuploaded presentation.
     *
     * @param context $context
     * @param string $presentation matching presentation file name
     * @param integer $id bigbluebutton instance id
     * @param bool $withnonce add nonce to the url
     * @return array|null the representation of the presentation as an associative array
     */
    public static function get_presentation($context, $presentation, $id = null, $withnonce = false): ?array {
        global $CFG;
        $fs = get_file_storage();
        $files = [];
        if (empty($presentation)) {
            if ($CFG->bigbluebuttonbn_preuploadpresentation_enabled) {
                // Item has not presentation but presentation is enabled..
                // Check if exist some file by default in general mod setting ("presentationdefault").
                $files = $fs->get_area_files(
                    context_system::instance()->id,
                    'mod_bigbluebuttonbn',
                    'presentationdefault',
                    0,
                    "filename",
                    false
                );
            }
        } else {
            $files = $fs->get_area_files(
                $context->id,
                'mod_bigbluebuttonbn',
                'presentation',
                false,
                'itemid, filepath, filename',
                false
            );
        }

        if (count($files) == 0) {
            return null; // No presentation.
        }

        $pnoncevalue = 0;
        if (!is_null($id) && $withnonce) {
            $instance = instance::get_from_instanceid($id);
            $pnoncevalue = self::generate_nonce_for_instance($instance);
        }

        $file = null;
        foreach ($files as $f) {
            if (basename($f->get_filename()) == basename($presentation)) {
                $file = $f;
            }
        }
        if (!$file && !empty($files)) {
            $file = reset($files);
        }
        if (empty($file)) {
            return null; // File was not found.
        }

        // Note: $pnoncevalue is an int.
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $withnonce ? $pnoncevalue : null, // Hack: item id as a nonce.
            $file->get_filepath(),
            $file->get_filename()
        );
        return [
            'icondesc' => get_mimetype_description($file),
            'iconname' => file_file_icon($file, 24),
            'name' => $file->get_filename(),
            'url' => $url->out(false),
        ];
    }

    /**
     * Helper for getting pluginfile name.
     *
     * @param object $course course object
     * @param object $cm course module object
     * @param context $context context object
     * @param array $args extra arguments
     *
     * @return string|array|null
     */
    public static function get_plugin_filename($course, $cm, $context, $args) {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            // Plugin has a file to use as default in general setting.
            // The difference with the standard bigbluebuttonbn_pluginfile_filename() are.
            // - Context is system, so we don't need to check the cmid in this case.
            // - The area is "presentationdefault_cache".
            if (!$DB->get_record('bigbluebuttonbn', array('id' => $cm->instance))) {
                return null;
            }
        }
        // Plugin has a file to use as default in general setting.
        // The difference with the standard bigbluebuttonbn_pluginfile_filename() are.
        // - Context is system, so we don't need to check the cmid in this case.
        // - The area is "presentationdefault_cache".
        if (count($args) > 1) {
            $instance = instance::get_from_cmid($cm->id);
            $actualnonce = self::get_nonce_for_instance($instance);
            return ($args['0'] == $actualnonce) ? $args['1'] : null;

        }
        require_course_login($course, true, $cm, true, true);
        if (!has_capability('mod/bigbluebuttonbn:join', $context)) {
            return null;
        }
        return implode('/', $args);
    }

    /**
     * Helper generates a salt used for the preuploaded presentation callback url.
     *
     * @param instance $instance
     * @return int
     * @throws \coding_exception
     */
    protected static function get_nonce_for_instance(instance $instance) {
        $cache = static::get_nonce_cache();
        $pnoncekey = sha1($instance->get_instance_id());
        $existingnonce = $cache->get($pnoncekey);
        if ($existingnonce) {
            $cache->delete($pnoncekey); // It has been used so now destroy it.
            return $existingnonce;
        }
        // The item id was adapted for granting public access to the presentation once in order to allow BigBlueButton to gather
        // the file once.
        return static::generate_nonce_for_instance($instance);
    }

    /**
     * Generate a nonce and store it in the cache
     *
     * @param instance $instance
     * @return int
     */
    protected static function generate_nonce_for_instance($instance) {
        $cache = static::get_nonce_cache();
        $pnoncekey = sha1($instance->get_instance_id());
        // The item id was adapted for granting public access to the presentation once in order to allow BigBlueButton to gather
        // the file once.
        $pnoncevalue = ((int) microtime()) + mt_rand();
        $cache->set($pnoncekey, $pnoncevalue);
        return $pnoncevalue;
    }

    /**
     * Get cache for nonce
     *
     * @return \cache_application|\cache_session|cache_store
     */
    private static function get_nonce_cache() {
        return cache::make_from_params(
            cache_store::MODE_APPLICATION,
            'mod_bigbluebuttonbn',
            'presentation_cache'
        );
    }

    /**
     * Returns an array of file areas.
     *
     * @return array a list of available file areas
     * @category files
     *
     */
    protected static function get_file_areas() {
        $areas = array();
        $areas['presentation'] = get_string('mod_form_block_presentation', 'bigbluebuttonbn');
        $areas['presentationdefault'] = get_string('mod_form_block_presentation_default', 'bigbluebuttonbn');
        return $areas;
    }

}
