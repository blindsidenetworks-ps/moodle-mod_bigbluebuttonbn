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
 * Behat custom steps and configuration for mod_bigbluebuttonbn.
 *
 * @package   mod_bigbluebuttonbn
 * @category  test
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Moodle\BehatExtension\Exception\SkippedException;

/**
 * Behat custom steps and configuration for mod_bigbluebuttonbn.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_bigbluebuttonbn extends behat_base {

    /**
     * BeforeScenario hook to reset the remote testpoint.
     *
     * @BeforeScenario @mod_bigbluebuttonbn
     */
    public function before_scenario(BeforeScenarioScope $scope) {
        if (!defined('TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER')) {
            throw new SkippedException(
                'The TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER constant must be defined to run mod_bigbluebuttonbn tests'
            );
        }

        $reseturl = self::get_mocked_server_url('backoffice/reset');

        $curl = new \curl();
        $curl->get($reseturl->out_omit_querystring(), $reseturl->params());
    }

    public static function get_mocked_server_url(string $endpoint = '', array $params = []): moodle_url {
        return new moodle_url(TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER . '/' . $endpoint, $params);
    }

    /**
     * Return the list of partial named selectors.
     *
     * @return array
     */
    public static function get_partial_named_selectors(): array {
        return [
            new behat_component_named_selector('Meeting identifier', [".//*[@data-identifier=%locator%]"]),
        ];
    }

    /**
     * Return the list of exact named selectors.
     *
     * @return array
     */
    public static function get_exact_named_selectors(): array {
        return [
            new behat_component_named_selector('Recording row',
                ["//*[@class='mod_bigbluebuttonbn_recordings_table']//tbody[@class='yui3-datatable-data']/tr[position()=%locator%]"]
            )
        ];
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * Recognised page names are:
     * | None so far!      |                                                              |
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch ($page) {
            default:
                throw new Exception("Unrecognised page type '{$page}'.");
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype | name meaning     | description                    |
     * | Index    | BBB Course Index | The bbb index page (index.php) |
     *
     * @param string $type identifies which type of page this is, e.g. 'Indez'.
     * @param string $identifier identifies the particular page, e.g. 'Mathematics 101'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch ($type) {
            case 'Index':
                $this->get_course_id($identifier);
                return new moodle_url('/mod/bigbluebuttonbn/index.php', [
                    'id' => $this->get_course_id($identifier),
                ]);

            default:
                throw new Exception("Unrecognised page type '{$type}'.");
        }
    }

    /**
     * Get course id from its identifier (shortname or fullname or idnumber)
     * @param string $identifier
     * @return int
     * @throws dml_exception
     */
    protected function get_course_id(string $identifier): int {
        global $DB;

        return $DB->get_field_select(
            'course',
            'id',
            "shortname = :shortname OR fullname = :fullname OR idnumber = :idnumber",
            [
                'shortname' => $identifier,
                'fullname' => $identifier,
                'idnumber' => $identifier,
            ],
            MUST_EXIST
        );
    }
}
