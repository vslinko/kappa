<?php

if (@!include __DIR__ . '/../vendor/silex.phar') {
    require __DIR__ . '/../vendor/silex/autoload.php';
}

$app = new Silex\Application();

$app->get('/', function () {
    return 'Hello world!';
});

return $app;
