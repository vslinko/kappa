<?php

namespace Kappa;

use Silex\Application;
use Silex\ServiceProviderInterface;

use Mongo;

class MongoProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['mongo'] = $app->share(function () use ($app) {
            return new Mongo($app['mongo.server'], $app['mongo.options']);
        });

        $app['mongo.db'] = $app->share(function () use ($app) {
            return $app['mongo']->selectDb($app['mongo.default_database']);
        });
    }
}
