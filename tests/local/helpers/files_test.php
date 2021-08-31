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
 * BBB Library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
namespace mod_bigbluebuttonbn\local\helpers;

use cache;
use cache_store;
use context_course;
use context_module;
use context_system;
use mod_bigbluebuttonbn\test\testcase_helper_trait;
use stdClass;
use stored_file;
/**
 * BBB Library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 * @covers \mod_bigbluebuttonbn\local\helpers\files
 * @coversDefaultClass \mod_bigbluebuttonbn\local\helpers\files
 */
class files_test extends \advanced_testcase {

    use testcase_helper_trait;
    /**
     * Setup basic
     */
    public function setUp(): void {
        parent::setUp();
        $this->basic_setup();
    }
    /**
     * Plugin valid test case
     */
    public function test_bigbluebuttonbn_pluginfile_valid() {
        $this->resetAfterTest();
        $this->assertFalse(files::pluginfile_valid(context_course::instance($this->get_course()->id), 'presentation'));
        $this->assertTrue(files::pluginfile_valid(context_system::instance(), 'presentation'));
        $this->assertFalse(files::pluginfile_valid(context_system::instance(), 'otherfilearea'));
    }

    /**
     * Plugin file test case
     */
    public function test_bigbluebuttonbn_pluginfile_file() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $user = $this->generator->create_user();
        $this->setUser($user);
        $this->generator->enrol_user($user->id, $this->get_course()->id, 'editingteacher');
        // From test_delete_original_file_from_draft (lib/test/filelib_test.php)
        // Create a bbb private file.
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $context = context_module::instance($bbformdata->coursemodule);

        $bbbfilerecord = new stdClass;
        $bbbfilerecord->contextid = $context->id;
        $bbbfilerecord->component = 'mod_bigbluebuttonbn';
        $bbbfilerecord->filearea = 'presentation';
        $bbbfilerecord->itemid = 0;
        $bbbfilerecord->filepath = '/';
        $bbbfilerecord->filename = 'bbfile.pptx';
        $bbbfilerecord->source = 'test';
        $fs = get_file_storage();
        $bbbfile = $fs->create_file_from_string($bbbfilerecord, 'Presentation file content');
        file_prepare_draft_area($bbformdata->presentation,
                context_module::instance($bbformdata->coursemodule)->id,
                'mod_bigbluebuttonbn',
                'presentation', 0);
        list($course, $bbactivitycmuser) = get_course_and_cm_from_instance($bbactivity->id, 'bigbluebuttonbn');
        /** @var stored_file $mediafile */
        $mediafile =
            files::pluginfile_file($this->get_course(), $bbactivitycmuser, $context, 'presentation', ['bbfile.pptx']);
        $this->assertEquals('bbfile.pptx', $mediafile->get_filename());
    }

    /**
     * Get presentation file
     */
    public function test_bigbluebuttonbn_default_presentation_get_file() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $user = $this->generator->create_user();
        $this->setUser($user);
        $this->generator->enrol_user($user->id, $this->get_course()->id, 'editingteacher');
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        // From test_delete_original_file_from_draft (lib/test/filelib_test.php)
        // Create a bbb private file.
        $context = context_module::instance($bbformdata->coursemodule);
        list($course, $bbactivitycmuser) = get_course_and_cm_from_instance($bbactivity->id, 'bigbluebuttonbn');
        $mediafile =
            files::pluginfile_filename($this->get_course(), $bbactivitycmuser, $context, ['presentation'],
                '/bbfile.pptx');
        $this->assertEquals('presentation', $mediafile);
    }

    /**
     * Get filename test
     */
    public function test_bigbluebuttonbn_pluginfile_filename() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $user = $this->generator->create_user();
        $this->setUser($user);
        $this->generator->enrol_user($user->id, $this->get_course()->id, 'editingteacher');
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'presentation_cache');
        $noncekey = sha1($bbactivity->id);
        $presentationnonce = $cache->get($noncekey);
        $filename = files::pluginfile_filename($this->get_course(), $bbactivitycm, $bbactivitycontext,
            [$presentationnonce, 'bbfile.pptx']);
        $this->assertEquals('bbfile.pptx', $filename);
    }

    /**
     * Get media files
     */
    public function test_bigbluebuttonbn_get_media_file() {
        $this->resetAfterTest();
        $user = $this->generator->create_user();
        $this->setUser($user);
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $mediafilepath = files::get_media_file($bbformdata);
        $this->assertEmpty($mediafilepath);

        // From test_delete_original_file_from_draft (lib/test/filelib_test.php)
        // Create a bbb private file.
        $bbbfilerecord = new stdClass;
        $bbbfilerecord->contextid = context_module::instance($bbformdata->coursemodule)->id;
        $bbbfilerecord->component = 'mod_bigbluebuttonbn';
        $bbbfilerecord->filearea = 'presentation';
        $bbbfilerecord->itemid = 0;
        $bbbfilerecord->filepath = '/';
        $bbbfilerecord->filename = 'bbfile.pptx';
        $bbbfilerecord->source = 'test';
        $fs = get_file_storage();
        $bbbfile = $fs->create_file_from_string($bbbfilerecord, 'Presentation file content');
        file_prepare_draft_area($bbformdata->presentation,
            context_module::instance($bbformdata->coursemodule)->id,
            'mod_bigbluebuttonbn',
            'presentation', 0);

        $mediafilepath = files::get_media_file($bbformdata);
        $this->assertEquals('/bbfile.pptx', $mediafilepath);
    }

}
