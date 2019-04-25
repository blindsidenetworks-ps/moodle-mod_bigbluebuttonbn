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

use moodle_url;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

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
     * @throws \moodle_exception
     */
    public static function necurl($url, $params = null, $anchor = null) {
        $lurl = new moodle_url($url, $params, $anchor);
        return $lurl->out(false);
    }

}