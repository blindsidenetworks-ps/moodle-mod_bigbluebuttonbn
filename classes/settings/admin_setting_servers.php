<?php

namespace mod_bigbluebuttonbn\settings;
require_once $GLOBALS['CFG']->dirroot . '/lib/adminlib.php';
require_once __DIR__ . "/../../db/upgrade.php";

use admin_setting_configtextarea;

/**
 * This abstract class intend to be an input form to get list of bigbluebuttonbn servers
 *
 * @package  mod_bigbluebuttonbn
 * @copyright 2022, Sajad Khosravani S. sajadkhosravani1@gmail.com
 * @author Sajad Khosravani S.
 */
class admin_setting_servers extends admin_setting_configtextarea
{
    protected $servers;

    public function __construct($name)
    {
        parent::__construct("bigbluebuttonbn_$name",
            get_string('config_servers', 'bigbluebuttonbn'),
            get_string('config_servers_description', 'bigbluebuttonbn'),
            get_string('config_servers_default', 'bigbluebuttonbn'),
            PARAM_RAW,35,10);
    }

    public static function instance($name){
        return new self($name);
    }

    /**
     * Trim values of array
     * @param array $server
     */
    public static function clean_up(array &$server)
    {
        foreach ($server as $key => &$value) {
            $value = trim($value);
        }
    }

    /**
     * Validate content after submit
     *
     * @param string $data
     * @return bool|\lang_string|mixed|string
     */
    public function validate($data)
    {
        $validated = parent::validate($data);
        if ($validated !== true)
            return $validated;

        $validated = $this->validate_servers($data);
        if ($validated !== true)
            return $validated;

        return true;
    }

    /**
     * Validate and parse servers data and initialize $this->servers
     *
     * @param string $data
     * @return bool|\lang_string|string
     * @throws \coding_exception
     */
    public function validate_servers($data)
    {
        // To json format
        $servers = json_decode($data, true);
        if (is_null($servers))
            return get_string('error_format_json', 'bigbluebuttonbn');

        // Validate each server
        $this->servers = array();
        foreach($servers as $server){
            self::clean_up($server);
            $validated = self::validate_server($server);
            if ($validated !== true)
                return $validated;
            $this->servers[] = (object) $server;
        }

        // Check if default server is not set or is set more than one time
        $defaultsCount = 0;
        foreach ($servers as $server) {
            if (key_exists('default', $server) and $server['default'] === true)
                $defaultsCount++;
        }
        if ($defaultsCount > 1) {
            return get_string('error_default_server_count_more_than_one',
                'bigbluebuttonbn');
        }else if ($defaultsCount < 1) {
            return get_string('error_no_default_server',
                'bigbluebuttonbn');
        }

        // Check if on of servers' name is repetitive
        $names = array();
        foreach ($servers as $server) {
            @$names[$server['servername']]++;
        }
        foreach ($names as $servername => $count) {
            if ($count > 1)
                return sprintf(get_string('error_repetitive_servername', 'bigbluebuttonbn'),
                    $servername, $count);
        }
        return true;
    }

    /**
     * Validate a single server array. Called only by 'validate_servers' function.
     *
     * @param array $server
     * @return bool|string
     * @throws \coding_exception
     */
    public static function validate_server(array $server)
    {
        // Make sure all fields have been defined
        $requiredFields = ['servername', 'url', 'secret', 'cap_users', 'cap_sessions'];
        foreach ($requiredFields as $field)
            if (!key_exists($field, $server))
                return sprintf(get_string('error_field_required','bigbluebuttonbn'),
                    $field);

        // Check if servername is not allowed
        if (trim($server['servername']) === FILLING_SERVER_NAME)
            return sprintf(get_string('error_forbidden_servername', 'bigbluebuttonbn'),
                FILLING_SERVER_NAME);

        // Check if server is available
        $version = bigbluebuttonbn_get_server_version((object)$server);
        if (is_null($version)){
            return sprintf(get_string('error_server_unavailable', 'bigbluebuttonbn'),
                $server['servername']);
        }

        return true;
    }

    /**
     * Sync the table 'bigbluebuttonbn_servers' with newly-defined servers
     *
     * @throws \dml_exception
     */
    public function update_servers(){
        global $DB;
        // Delete unmentioned servers
        $servernames = array();
        foreach ($this->servers as $server) {
            $servernames[] = $server->servername;
        }
        foreach ($DB->get_records('bigbluebuttonbn_servers') as $server) {
            if (!in_array($server->servername, $servernames)) {
                $DB->delete_records('bigbluebuttonbn_servers', ['servername' => $server->servername]);
            }
        }

        // Insert or update servers
        foreach ($this->servers as $server){
            if ($server->default)
                $defaultServerName = $server->servername;
            if ($server->id = $DB->get_field('bigbluebuttonbn_servers', 'id', ['servername' => $server->servername])){
                $DB->update_record('bigbluebuttonbn_servers', $server);
            } else {
                $DB->insert_record('bigbluebuttonbn_servers', $server);
            }
        }

        // Set default server
        set_config('bigbluebuttonbn_default_server_name', $defaultServerName);

        // Substitute filing servername with default server if exist
        $DB->execute("
            UPDATE {`bigbluebuttonbn`} SET servername=\"$defaultServerName\" WHERE servername=\"".FILLING_SERVER_NAME."\""
        );
    }

    /**
     * Inherited from parent class
     *
     * @param mixed $data
     * @return bool|\lang_string|mixed|string
     * @throws \coding_exception
     */
    public function write_setting($data) {
        try {
            $validated = $this->validate($data);
            if ($validated !== true) {
                return $validated;
            }
            $this->update_servers();
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
        return ($this->config_write($this->name, $data) ? '' : get_string('errorsetting', 'admin'));
    }
}