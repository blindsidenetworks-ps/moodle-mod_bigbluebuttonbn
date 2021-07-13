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
 * The mod_bigbluebuttonbn plugin helper.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2019 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */

namespace mod_bigbluebuttonbn;

use cache;
use cache_store;
use core_tag_tag;
use mod_bigbluebuttonbn\local\config;
use moodle_url;
use stdClass;

/**
 * Class plugin.
 * @package mod_bigbluebuttonbn
 * @copyright 2019 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */
abstract class plugin {

    /**
     * Component name.
     */
    const COMPONENT = 'mod_bigbluebuttonbn';

    /**
     * Outputs url with plain parameters.
     * @param  string $url
     * @param  array $params
     * @param  string $anchor
     * @return string
     */
    public static function necurl($url, $params = null, $anchor = null) {
        $lurl = new moodle_url($url, $params, $anchor);
        return $lurl->out(false);
    }

    /**
     * Helper for setting a value in a bigbluebuttonbn cache.
     *
     * @param  string   $name       BigBlueButtonBN cache
     * @param  string   $key        Key to be created/updated
     * @param  variable $value      Default value to be set
     */
    public static function bigbluebuttonbn_cache_set($name, $key, $value) {
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', $name);
        $cache->set($key, $value);
    }

    /**
     * Helper for getting a value from a bigbluebuttonbn cache.
     *
     * @param  string   $name       BigBlueButtonBN cache
     * @param  string   $key        Key to be retrieved
     * @param  integer  $default    Default value in case key is not found or it is empty
     *
     * @return mixed key value
     */
    public static function bigbluebuttonbn_cache_get($name, $key, $default = null) {
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', $name);
        $result = $cache->get($key);
        if (!empty($result)) {
            return $result;
        }
        return $default;
    }

    /**
     * Helper function returns the locale code based on the locale set by moodle.
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_localcode() {
        $locale = self::bigbluebuttonbn_get_locale();
        return substr($locale, 0, strpos($locale, '_'));
    }

    /**
     * Helper function returns the locale set by moodle.
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_locale() {
        $lang = get_string('locale', 'core_langconfig');
        return substr($lang, 0, strpos($lang, '.'));
    }

    /**
     * Helper function to convert an html string to plain text.
     *
     * @param string $html
     * @param integer $len
     *
     * @return string
     */
    public static function bigbluebuttonbn_html2text($html, $len = 0) {
        $text = strip_tags($html);
        $text = str_replace('&nbsp;', ' ', $text);
        $textlen = strlen($text);
        $text = mb_substr($text, 0, $len);
        if ($textlen > $len) {
            $text .= '...';
        }
        return $text;
    }

    /**
     * Helper evaluates if the bigbluebutton server used belongs to blindsidenetworks domain.
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_is_bn_server() {
        if (config::get('bn_server')) {
            return true;
        }
        $parsedurl = parse_url(config::get('server_url'));
        if (!isset($parsedurl['host'])) {
            return false;
        }
        $h = $parsedurl['host'];
        $hends = explode('.', $h);
        $hendslength = count($hends);
        return ($hends[$hendslength - 1] == 'com' && $hends[$hendslength - 2] == 'blindsidenetworks');
    }

    /**
     * Helper generates a nonce used for the preuploaded presentation callback url.
     *
     * @return string
     */
    public static function bigbluebuttonbn_generate_nonce() {
        $mt = microtime();
        $rand = mt_rand();
        return md5($mt . $rand);
    }

    /**
     * Helper generates a random password.
     *
     * @param integer $length
     * @param string $unique
     *
     * @return string
     */
    public static function bigbluebuttonbn_random_password($length = 8, $unique = "") {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {
            $password = substr(str_shuffle($chars), 0, $length);
        } while ($unique == $password);
        return $password;
    }

    /**
     * Helper returns error message key for the language file that corresponds to a bigbluebutton error key.
     *
     * @param string $messagekey
     * @param string $defaultkey
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_error_key($messagekey, $defaultkey = null) {
        if ($messagekey == 'checksumError') {
            return 'index_error_checksum';
        }
        if ($messagekey == 'maxConcurrent') {
            return 'view_error_max_concurrent';
        }
        return $defaultkey;
    }

    /**
     * Helper function to obtain the tags linked to a bigbluebuttonbn activity
     *
     * @param string $id
     *
     * @return string containing the tags separated by commas
     */
    public static function bigbluebuttonbn_get_tags($id) {
        if (class_exists('core_tag_tag')) {
            return implode(',', core_tag_tag::get_item_tags_array('core', 'course_modules', $id));
        }
        return implode(',', tag_get_tags('bigbluebuttonbn', $id));
    }

    /**
     * Helper function returns a sha1 encoded string that is unique and will be used as a seed for meetingid.
     *
     * @return string
     */
    public static function bigbluebuttonbn_unique_meetingid_seed() {
        global $DB;
        do {
            $encodedseed = sha1(self::bigbluebuttonbn_random_password(12));
            $meetingid = (string) $DB->get_field('bigbluebuttonbn', 'meetingid', array('meetingid' => $encodedseed));
        } while ($meetingid == $encodedseed);
        return $encodedseed;
    }

    /**
     * Get the meetingid of the specified BBB Instance.
     *
     * @param stdClass $instance
     * @param null|stdClass $group
     * @return string
     */
    public static function get_meeting_id(stdClass $instance, ?stdClass $group = null): string {
        if ($group) {
            return sprintf('%s-%d-%d[%d]', $instance->meetingid, $instance->course, $instance->id, $group->id);
        } else {
            return sprintf('%s-%d-%d', $instance->meetingid, $instance->course, $instance->id);
        }
    }
}
