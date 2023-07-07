<?php

return [
    'whoops' => false,
    'exceptionsLog' => false,
    /** @see Monolog\Logger::NOTICE */
    'minExceptionsLogLevel' => 250,
    'stripWebroot' => true,
    'telegram' => [
        'enabled' => false,
        'token' => '',
        'recipients' => '',
    ],
    'slack' => [
        'enabled' => false,
        'token' => '',
        'channels' => '',
    ],
];
