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
use mod_bigbluebuttonbn\plugin;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

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
    public static function bigbluebuttonbn_pluginfile_valid($context, $filearea) {

        // Can be in context module or in context_system (if is the presentation by default).
        if (!in_array($context->contextlevel, array(CONTEXT_MODULE, CONTEXT_SYSTEM))) {
            return false;
        }

        if (!array_key_exists($filearea, static::bigbluebuttonbn_get_file_areas())) {
            return false;
        }

        return true;
    }

    /**
     * Helper for getting pluginfile.
     *
     * @param stdClass $course course object
     * @param stdClass $cm course module object
     * @param stdClass $context context object
     * @param string $filearea file area
     * @param array $args extra arguments
     *
     * @return object|bool
     */
    public static function bigbluebuttonbn_pluginfile_file($course, $cm, $context, $filearea, $args) {
        $filename = static::bigbluebuttonbn_pluginfile_filename($course, $cm, $context, $args);
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
     * Helper for give access to the file configured in setting as default presentation.
     *
     * @param stdClass $course course object
     * @param stdClass $cm course module object
     * @param stdClass $context context object
     * @param array $args extra arguments
     *
     * @return array|string|null
     */
    public static function bigbluebuttonbn_default_presentation_get_file($course, $cm, $context, $args) {

        // The difference with the standard bigbluebuttonbn_pluginfile_filename() are.
        // - Context is system, so we don't need to check the cmid in this case.
        // - The area is "presentationdefault_cache".
        if (count($args) > 1) {
            $cache = cache::make_from_params(
                cache_store::MODE_APPLICATION,
                'mod_bigbluebuttonbn',
                'presentationdefault_cache'
            );

            $noncekey = sha1($context->id);
            $presentationnonce = $cache->get($noncekey);
            $noncevalue = $presentationnonce['value'];
            $noncecounter = $presentationnonce['counter'];
            if ($args['0'] != $noncevalue) {
                return null;
            }

            // The nonce value is actually used twice because BigBlueButton reads the file two times.
            $noncecounter += 1;
            $cache->set($noncekey, array('value' => $noncevalue, 'counter' => $noncecounter));
            if ($noncecounter == 2) {
                $cache->delete($noncekey);
            }
            return ($args['1']);
        }
        require_course_login($course, true, $cm);
        if (!has_capability('mod/bigbluebuttonbn:join', $context)) {
            return null;
        }
        return implode('/', $args);
    }

    /**
     * Helper for getting pluginfile name.
     *
     * @param stdClass $course course object
     * @param stdClass $cm course module object
     * @param stdClass $context context object
     * @param array $args extra arguments
     *
     * @return string|array|null
     */
    public static function bigbluebuttonbn_pluginfile_filename($course, $cm, $context, $args) {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Plugin has a file to use as default in general setting.
            return (static::bigbluebuttonbn_default_presentation_get_file($course, $cm, $context, $args));
        }

        if (count($args) > 1) {
            if (!$bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance))) {
                return null;
            }
            $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'presentation_cache');
            $noncekey = sha1($bigbluebuttonbn->id);
            $presentationnonce = $cache->get($noncekey);
            if (!empty($presentationnonce)) {
                $noncevalue = $presentationnonce['value'];
                $noncecounter = $presentationnonce['counter'];
            } else {
                $noncevalue = null;
                $noncecounter = 0;
            }

            if ($args['0'] != $noncevalue) {
                return null;
            }
            // The nonce value is actually used twice because BigBlueButton reads the file two times.
            $noncecounter += 1;
            $cache->set($noncekey, array('value' => $noncevalue, 'counter' => $noncecounter));
            if ($noncecounter == 2) {
                $cache->delete($noncekey);
            }
            return $args['1'];
        }
        require_course_login($course, true, $cm);
        if (!has_capability('mod/bigbluebuttonbn:join', $context)) {
            return null;
        }
        return implode('/', $args);
    }

    /**
     * Returns an array of file areas.
     *
     * @return array a list of available file areas
     * @category files
     *
     */
    public static function bigbluebuttonbn_get_file_areas() {
        $areas = array();
        $areas['presentation'] = get_string('mod_form_block_presentation', 'bigbluebuttonbn');
        $areas['presentationdefault'] = get_string('mod_form_block_presentation_default', 'bigbluebuttonbn');
        return $areas;
    }

    /**
     * Get a full path to the file attached as a preuploaded presentation
     * or if there is none, set the presentation field will be set to blank.
     *
     * @param object $bigbluebuttonbn BigBlueButtonBN form data
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_media_file(&$bigbluebuttonbn) {
        if (!isset($bigbluebuttonbn->presentation) || $bigbluebuttonbn->presentation == '') {
            return '';
        }
        $context = context_module::instance($bigbluebuttonbn->coursemodule);
        // Set the filestorage object.
        $fs = get_file_storage();
        // Save the file if it exists that is currently in the draft area.
        file_save_draft_area_files($bigbluebuttonbn->presentation, $context->id, 'mod_bigbluebuttonbn', 'presentation', 0);
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
     * @param string $presentation
     * @param integer $id
     *
     * @return array|null
     */
    public static function bigbluebuttonbn_get_presentation_array($context, $presentation, $id = null): ?array {
        global $CFG;
        if (empty($presentation)) {
            if ($CFG->bigbluebuttonbn_preuploadpresentation_enabled) {
                // Item has not presentation but presentation is enabled..
                // Check if exist some file by default in general mod setting ("presentationdefault").
                $fs = get_file_storage();
                $files = $fs->get_area_files(
                    context_system::instance()->id,
                    'mod_bigbluebuttonbn',
                    'presentationdefault',
                    0,
                    "filename",
                    false
                );

                if (count($files) == 0) {
                    // Not exist file by default in "presentationbydefault" setting.
                    return [
                        'icondesc' => null,
                        'iconname' => null,
                        'name' => null,
                        'url' => null,
                    ];
                }

                // Exists file in general setting to use as default for presentation. Cache image for temp public access.
                $file = reset($files);
                unset($files);
                $pnoncevalue = null;
                if (!is_null($id)) {
                    // Create the nonce component for granting a temporary public access.
                    $cache = cache::make_from_params(
                        cache_store::MODE_APPLICATION,
                        'mod_bigbluebuttonbn',
                        'presentationdefault_cache'
                    );
                    $pnoncekey = sha1(context_system::instance()->id);
                    /* The item id was adapted for granting public access to the presentation once in order
                     * to allow BigBlueButton to gather the file. */
                    $pnoncevalue = plugin::bigbluebuttonbn_generate_nonce();
                    $cache->set($pnoncekey, array('value' => $pnoncevalue, 'counter' => 0));
                }

                $url = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $pnoncevalue,
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

            return null; // No presentation.
        }
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'mod_bigbluebuttonbn',
            'presentation',
            0,
            'itemid, filepath, filename',
            false
        );
        if (count($files) == 0) {
            return null; // No presentation.
        }
        $file = reset($files);
        unset($files);
        $pnoncevalue = null;
        if (!is_null($id)) {
            // Create the nonce component for granting a temporary public access.
            $cache = cache::make_from_params(
                cache_store::MODE_APPLICATION,
                'mod_bigbluebuttonbn',
                'presentation_cache'
            );
            $pnoncekey = sha1($id);
            /* The item id was adapted for granting public access to the presentation once in order
             * to allow BigBlueButton to gather the file. */
            $pnoncevalue = plugin::bigbluebuttonbn_generate_nonce();
            $cache->set($pnoncekey, array('value' => $pnoncevalue, 'counter' => 0));
        }
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $pnoncevalue,
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
}
