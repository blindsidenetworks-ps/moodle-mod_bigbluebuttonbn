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
 * Contains the class for fetching the important dates in mod_bigbluebuttonbn for a given module instance and a user.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright  2022 - present, Blindside Networks Inc
 * @author    Shamiso Jaravaza (shamiso.jaravaza@blindsidenetworks.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_bigbluebuttonbn;

use core\activity_dates;
use mod_bigbluebuttonbn\instance;

/**
 * Class for fetching the important dates in mod_bigbluebuttonbn for a given module instance and a user.
 *
 */
class dates extends activity_dates {
    /**
     * Returns a list of important dates in mod_bigbluebuttonbn
     *
     * @return array
     */
    protected function get_dates(): array {
        // Get instance details.
        $instance = instance::get_from_cmid( (int) $this->cm->id);
        $timeopen = $instance->get_instance_var('openingtime') ?? null;
        $timeclose = $instance->get_instance_var('closingtime') ?? null;
        $now = time();
        $dates = [];

        if ($timeopen) {
            $openlabelid = $timeopen > $now ? 'activitydate:opens' : 'activitydate:opened';
            $dates[] = [
                'label' => get_string($openlabelid, 'core_course'),
                'timestamp' => (int) $timeopen,
            ];
        }

        if ($timeclose) {
            $closelabelid = $timeclose > $now ? 'activitydate:closes' : 'activitydate:closed';
            $dates[] = [
                'label' => get_string($closelabelid, 'core_course'),
                'timestamp' => (int) $timeclose,
            ];
        }
        return $dates;
    }
}
