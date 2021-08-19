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
 * The abstract proxy base class.
 *
 * This class provides common and shared functions used when interacting with
 * the BigBlueButton API server.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local\proxy;

use Exception;
use SimpleXMLElement;
use curl;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\exceptions\bigbluebutton_exception;
use mod_bigbluebuttonbn\local\exceptions\server_not_available_exception;
use mod_bigbluebuttonbn\plugin;
use moodle_url;

abstract class proxy_base {

    /**
     * Sometimes the server sends back some error and errorKeys that
     * can be converted to Moodle error messages
     */
    const MEETING_ERROR = [
        'checksumError' => 'index_error_checksum',
        'notFound' => 'general_error_not_found',
        'maxConcurrent' => 'view_error_max_concurrent',
    ];

    /**
     * Returns the right URL for the action specified.
     *
     * @param string $action
     * @param array $data
     * @param array $metadata
     * @return string
     */
    protected static function action_url(string $action = '', array $data = [], array $metadata = []): string {
        $baseurl = self::sanitized_url() . $action . '?';
        $metadata = array_combine(array_map(function($k) {
            return 'meta_' . $k;
        }, array_keys($metadata)), $metadata);

        $params = http_build_query($data + $metadata, '', '&');
        return $baseurl . $params . '&checksum=' . sha1($action . $params . self::sanitized_secret());
    }

    /**
     * Makes sure the url used doesn't is in the format required.
     *
     * @return string
     */
    protected static function sanitized_url() {
        $serverurl = trim(config::get('server_url'));
        if (defined('BEHAT_SITE_RUNNING') || PHPUNIT_TEST) {
            $serverurl = (new moodle_url(TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER))->out(false);
        }
        if (substr($serverurl, -1) == '/') {
            $serverurl = rtrim($serverurl, '/');
        }
        if (substr($serverurl, -4) == '/api') {
            $serverurl = rtrim($serverurl, '/api');
        }
        return $serverurl . '/api/';
    }

    /**
     * Makes sure the shared_secret used doesn't have trailing white characters.
     *
     * @return string
     */
    protected static function sanitized_secret(): string {
        return trim(config::get('shared_secret'));
    }

    /**
     * Perform api request on BBB and wraps the response in an XML object
     *
     * @param string $url
     * @param string $method
     * @param string $data
     * @param string $contenttype
     *
     * @return object
     */
    public static function wrap_xml_load_file($url, $method = 'GET', $data = null, $contenttype = 'text/xml') {
        if (extension_loaded('curl')) {
            $response = self::wrap_xml_load_file_curl_request($url, $method, $data, $contenttype);
            if (!$response) {
                debugging('No response on wrap_simplexml_load_file', DEBUG_DEVELOPER);
                return null;
            }
            $previous = libxml_use_internal_errors(true);
            try {
                $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
                return $xml;
            } catch (Exception $e) {
                libxml_use_internal_errors($previous);
                $error = 'Caught exception: ' . $e->getMessage();
                debugging($error, DEBUG_DEVELOPER);
                return null;
            }
        }
        // Alternative request non CURL based.
        $previous = libxml_use_internal_errors(true);
        try {
            $response = simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
            return $response;
        } catch (Exception $e) {
            $error = 'Caught exception: ' . $e->getMessage();
            debugging($error, DEBUG_DEVELOPER);
            libxml_use_internal_errors($previous);
            return null;
        }
    }

    /**
     * Perform api request on BBB using CURL and wraps the response in an XML object
     *
     * @param string $url
     * @param string $method
     * @param string $data
     * @param string $contenttype
     *
     * @return object|bool|string
     */
    public static function wrap_xml_load_file_curl_request($url, $method = 'GET', $data = null,
        $contenttype = 'text/xml') {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $c = new curl();
        $c->setopt(array('SSL_VERIFYPEER' => true));
        if ($method == 'POST') {
            if (is_null($data) || is_array($data)) {
                return $c->post($url);
            }
            $options = array();
            $options['CURLOPT_HTTPHEADER'] = array(
                'Content-Type: ' . $contenttype,
                'Content-Length: ' . strlen($data),
                'Content-Language: en-US',
            );

            return $c->post($url, $data, $options);
        }
        if ($method == 'HEAD') {
            $c->head($url, array('followlocation' => true, 'timeout' => 1));
            return $c->get_info();
        }
        return $c->get($url);
    }

    /**
     * Throw an exception if there is a problem in the returned XML value
     *
     * @param null|SimpleXMLElement $xml
     * @param string $additionaldetails
     * @throws bigbluebutton_exception
     * @throws server_not_available_exception
     */
    protected static function assert_returned_xml(?SimpleXMLElement $xml, string $additionaldetails = ''): void {
        if (empty($xml)) {
            throw new server_not_available_exception(
                'general_error_no_answer',
                plugin::COMPONENT,
                (new moodle_url('/admin/settings.php?section=modsettingbigbluebuttonbn'))->out()
            );
        }

        if ((string) $xml->returncode == 'FAILED') {
            $messagekey = (string) $xml->messageKey ?? '';
            $messagedetails = (string) $xml->message ?? '';
            $messagedetails .= $additionaldetails ? " ($additionaldetails) " : '';

            if (empty($messagekey) || empty(self::MEETING_ERROR[$messagekey])) {
                $messagekey = 'general_error_unable_connect';
            }

            throw new bigbluebutton_exception($messagekey, plugin::COMPONENT, '', $messagedetails);
        }
    }

    /**
     * Fetch the XML from an endpoint and test for success.
     *
     * If the result could not be loaded, or the returncode was not 'SUCCESS', a null value is returned.
     *
     * @param string $action
     * @param array $data
     * @param array $metadata
     * @param bool $assertresult
     * @return null|SimpleXMLElement
     */
    protected static function fetch_endpoint_xml(
        string $action,
        array $data = [],
        array $metadata = []
    ): ?SimpleXMLElement {
        return self::wrap_xml_load_file(self::action_url($action, $data, $metadata));
    }
}
