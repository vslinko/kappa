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

$app->get('/', function () {
    $staff = kyStaff::getAll();
    $text = print_r($staff, true);

    return new Symfony\Component\HttpFoundation\Response($text, 200, array('Content-Type' => 'text/plain'));
});

return $app;
