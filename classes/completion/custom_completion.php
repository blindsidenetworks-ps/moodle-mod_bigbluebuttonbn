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
 * The mod_bigbluebuttonbn custom completion.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

declare(strict_types=1);

namespace mod_bigbluebuttonbn\completion;

use core_completion\activity_custom_completion;

/** This version still uses the old (and deprecated) way of registering completion.
 *  The class has been included for maintain compatibility with version 3.11. But it is not
 *  fully implemented. It will be completed as part of 2.5 (CONTRIB-8520)
 */

/**
 * The mod_bigbluebuttonbn implementation of custom_completion for completion.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion
{
    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        return COMPLETION_UNKNOWN;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        // This activity/resource do not have any custom rules.
        return [];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        // This activity/resource do not have any custom rule descriptions.
        return [];
    }

    /**
     * Show the manual completion or not regardless of the course's showcompletionconditions setting.
     *
     * @return bool
     */
    public function manual_completion_always_shown(): bool {
        return true;
    }
}
