<?php

return array(
    'twig.path' => __DIR__ . '/templates',
    'twig.class_path' => __DIR__ . '/../vendor/twig/lib',

    'kayako.base_url' => '',
    'kayako.api_key' => '',
    'kayako.secret_key' => '',
    'kayako.class_path' => __DIR__ . '/../vendor/kayako',

    'kappa' => array(
        'config_name' => array(
            'department' => 0,
            'statuses' => array(),
            'staff' => array(),
        )
    ),
    'kappa.ticket_url' => '/staff/index.php?/Tickets/Ticket/View/%s',
);
