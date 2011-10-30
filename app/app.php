<?php

if (@!include __DIR__ . '/../vendor/silex.phar') {
    require __DIR__ . '/../vendor/silex/autoload.php';
}

$app = new Silex\Application();

$app['autoloader']->registerNamespace('Kappa', __DIR__ . '/../src');

$config = require __DIR__ . '/config.php';
foreach ($config as $key => $value) {
    $app[$key] = $value;
}

$app->register(new Kappa\KayakoProvider());

$storage = function($key, $factory, $ttl = 3600) {
    $success = false;
    $result = apc_fetch($key, $success);

    if (!$success) {
        $result = $factory();

        apc_store($key, $result, $ttl);
    }

    return $result;
};

$app->get('/', function () use ($app, $storage) {
    $department = $storage('kappa:department', function () use ($app) {
        return kyDepartment::get($app['kappa.department']);
    });

    $statuses = $storage('kappa:statuses', function () use ($app) {
        $statuses = array();

        foreach ($app['kappa.statuses'] as $statusId) {
            $statuses[] = kyTicketStatus::get($statusId);
        }

        return new kyResultSet($statuses);
    });

    $staffs = $storage('kappa:staff', function () use ($app) {
        $staffs = array();

        foreach ($app['kappa.staff'] as $staffId) {
            $staffs[] = kyStaff::get($staffId);
        }

        return new kyResultSet($staffs);
    });

    $tickets = kyTicket::getAll($department, $statuses, $staffs);

    $text = print_r($department, true);
    $text .= print_r($statuses, true);
    $text .= print_r($staffs, true);
    $text .= print_r($tickets, true);

    return new Symfony\Component\HttpFoundation\Response($text, 200, array('Content-Type' => 'text/plain'));
});

return $app;
