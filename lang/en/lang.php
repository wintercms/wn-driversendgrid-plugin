<?php

$name = 'Sendgrid API key';

return [
    'plugin_description' => 'Sendgrid mail driver plugin',

    'fields' => [
        'sendgrid_api_key' => [
            'label' => $name,
            'comment' => 'Enter your ' . $name,
        ],
    ],
];
