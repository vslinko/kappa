<?php

if (@!include __DIR__ . '/../vendor/silex.phar') {
    require __DIR__ . '/../vendor/silex/autoload.php';
}

$app = new Silex\Application();

$app['autoloader']->registerNamespaceFallbacks(array(__DIR__ . '/../src'));

$config = require __DIR__ . '/config.php';
foreach ($config as $key => $value) {
    $app[$key] = $value;
}

$app->register(new Silex\Provider\TwigServiceProvider());
$app->register(new Kappa\KayakoProvider());

$app->get('/', function () use ($app) {

    $table = array();
    foreach ($app['kappa.tickets'] as $ticket) {
        $staffName = $ticket->getOwnerStaffName();

        if (empty($staffName)) {
            $staffName = "Unassigned";
        }

        if (!isset($table[$staffName])) {
            $table[$staffName] = array();

            foreach ($app['kappa.statuses'] as $statusId => $status) {
                $table[$staffName][$statusId] = array();
            }
        }

        $table[$staffName][$ticket->getStatusId()][] = $ticket;
    }

    return $app['twig']->render('department.twig', array(
        'table' => $table
    ));
});

$app->error(function (Exception $e, $code) use ($app) {
    switch ($code) {
        case 404:
            $content = $app['twig']->render('not_found.twig');
            break;
        default:
            $content = $app['twig']->render('error.twig', array('exception' => $e));
    }

    return new Symfony\Component\HttpFoundation\Response($content, $code);
});

return $app;
