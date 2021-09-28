<?php
defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    'crashed' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'email' => MESSAGE_PERMITTED
        ],
    ],
];