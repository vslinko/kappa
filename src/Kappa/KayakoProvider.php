<?php

namespace Kappa;

use Silex\Application;
use Silex\ServiceProviderInterface;

use kyBase;
use kyDepartment;
use kyTicketStatus;
use kyTicket;

class KayakoProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (isset($app['kayako.class_path'])) {
            $app['autoloader']->registerPrefix('ky', $app['kayako.class_path']);
            require $app['kayako.class_path'] . '/ky_functions.php';
        }

        kyBase::init($app['kayako.base_url'], $app['kayako.api_key'], $app['kayako.secret_key']);

        $app['kappa.department'] = $app->share(function () use ($app) {
            return kyDepartment::get($app['kappa.department_id']);
        });

        $app['kappa.statuses'] = $app->share(function () {
            $statuses = kyTicketStatus::getAll()->filterByMarkAsResolved(false)->orderByDisplayOrder();

            return new ResultSet($statuses);
        });

        $app['kappa.tickets'] = $app->share(function () use ($app) {
            $tickets = kyTicket::getAll($app['kappa.department'], $app['kappa.statuses'])->orderByOwnerStaffName();

            return new ResultSet($tickets);
        });

        if (isset($app['twig'])) {
            $app['twig']->addExtension(new TwigExtension());
        }
    }
}
