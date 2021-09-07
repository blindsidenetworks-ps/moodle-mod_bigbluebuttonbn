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

namespace mod_bigbluebuttonbn\local\proxy;

use mod_bigbluebuttonbn\test\testcase_helper_trait;

/**
 * Tests for proxy_base.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_bigbluebuttonbn\local\proxy\proxy_base
 * @coversDefaultClass \mod_bigbluebuttonbn\local\proxy\proxy_base
 */
class proxy_base_test extends \advanced_testcase {
    use testcase_helper_trait;

    public function setUp(): void {
        $this->require_mock_server();
    }

    /**
     * @dataProvider action_url_provider
     * @param string $action
     * @param array $params
     */
    public function test_action_url(array $params, string $expected): void {
        $uut = $this->getMockForAbstractClass(proxy_base::class);
        $rc = new \ReflectionClass(proxy_base::class);
        $rcp = $rc->getMethod('action_url');
        $rcp->setAccessible(true);

        $this->assertEquals($expected, $rcp->invokeArgs($uut, $params));
    }

    /**
     * Data provider for the action_url tests.
     *
     * @return array
     */
    public function action_url_provider(): array {
        $rc = new \ReflectionClass(proxy_base::class);

        $geturl = $rc->getMethod('sanitized_url');
        $geturl->setAccessible(true);
        $url = $geturl->invoke(null);

        $getsecret = $rc->getMethod('sanitized_secret');
        $getsecret->setAccessible(true);
        $secret = $getsecret->invoke(null);

        $buildurl = function($action, $paramlist) use ($url, $secret): string {
            $paramlist = array_map('rawurlencode', $paramlist);
            $params = http_build_query($paramlist, '', '&');

            $generatedurl = new \moodle_url($url . $action, $paramlist);
            $generatedurl->param('checksum', sha1($action .  $params .  $secret));

            return $generatedurl->out(false);
        };

        return [
            [
                ['create', ['name' => 'Penelope+Peabody'], []],
                $buildurl('create', ['name' => 'Penelope+Peabody'])
            ],
            [
                ['create', [], ['instancename' => 'Some+Instance']],
                $buildurl('create', ['meta_instancename' => 'Some+Instance'])
            ],
        ];
    }
}
