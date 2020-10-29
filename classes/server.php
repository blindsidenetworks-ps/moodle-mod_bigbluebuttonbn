<?php
/**
 * This script is owned by CBlue SPRL, please contact CBlue regarding any licences issues.
 *
 * @date :       30/10/2020
 * @author:      rlemaire@cblue.be
 * @copyright:   CBlue SPRL, 2020
 */

namespace mod_bigbluebuttonbn;

use core\persistent;

class server extends persistent
{

    const TABLE = 'bigbluebuttonbn_servers';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties()
    {
        return [
            'name' => [
                'type' => PARAM_TEXT,
                'default' => '',
            ],
            'url' => [
                'type' => PARAM_TEXT,
                'default' => '',
            ],
            'secret' => [
                'type' => PARAM_TEXT,
                'default' => '',
            ],
            'weight' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'enabled' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'participants' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }
}
