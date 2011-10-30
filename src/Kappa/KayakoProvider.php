<?php

namespace Kappa;

use Silex\Application;
use Silex\ServiceProviderInterface;

use kyBase;

class KayakoProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (isset($app['kayako.class_path'])) {
            $app['autoloader']->registerPrefix('ky', $app['kayako.class_path']);
            require $app['kayako.class_path'] . '/ky_functions.php';
        }

        kyBase::init($app['kayako.base_url'], $app['kayako.api_key'], $app['kayako.secret_key']);
    }
}
