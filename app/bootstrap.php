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
$app->register(new Kappa\MongoProvider());

return $app;
