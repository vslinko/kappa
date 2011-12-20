<?php

return array(
    'twig.path' => __DIR__ . '/templates',
    'twig.class_path' => __DIR__ . '/../vendor/twig/lib',

    'kayako.base_url' => '',
    'kayako.api_key' => '',
    'kayako.secret_key' => '',
    'kayako.class_path' => __DIR__ . '/../vendor/kayako',

    'mongo.server' => 'mongodb://localhost:27017',
    'mongo.options' => null,
    'mongo.default_database' => 'kappa',

    'kappa.department_id' => 3,
    'kappa.in_progress_status_id' => 2,
    'kappa.ticket_url' => '/staff/index.php?/Tickets/Ticket/View/%s',
);
