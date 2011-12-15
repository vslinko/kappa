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

$app->register(new Silex\Provider\TwigServiceProvider());
$app->register(new Kappa\KayakoProvider());
$app->register(new \Silex\Provider\UrlGeneratorServiceProvider());

$storage = function ($key, $factory, $ttl = 3600) {
    $success = false;
    $result = apc_fetch($key, $success);

    if (!$success) {
        $result = $factory();

        apc_store($key, $result, $ttl);
    }

    return $result;
};

$each = function ($array, $callback) {
    $result = array();

    foreach ($array as $value) {
        $key = $callback($value);

        if ($key) {
            $result[$key] = $value;
        } else {
            $result[] = $value;
        }
    }

    return $result;
};

$app->get('/', function () use ($app, $storage) {
    $result = $storage('kappa:departments', function () {
        return array('departments' => kyDepartment::getAll()->orderByTitle());
    });

    return $app['twig']->render('departments.twig', $result);
});

$app->get('/{id}', function ($id) use ($app, $storage, $each) {
    $result = $storage('kappa:department:' . $id, function () use ($id, $app, $each) {
        $department = kyDepartment::get($id);

        if (!$department) {
            $app->abort(404);
        }

        $statuses = kyTicketStatus::getAll()->filterByMarkAsResolved(false)->orderByDisplayOrder();

        $statuses = $each($statuses, function ($status) {
            $class = strtolower(trim($status->getTitle()));
            $class = preg_replace('/[^a-z0-9-]/', '-', $class);
            $class = preg_replace('/-+/', '-', $class);

            $status->class = $class;

            return $status->getId();
        });

        $tickets = kyTicket::getAll($department, $statuses)->orderByOwnerStaffName();

        $tickets = $each($tickets, function (&$ticket) use ($app) {
            $ticket->classes = array();

            switch ($ticket->getFlagType()) {
                case kyTicket::FLAG_PURPLE:
                    $ticket->classes[] = 'purple';
                    break;
                case kyTicket::FLAG_ORANGE:
                    $ticket->classes[] = 'orange';
                    break;
                case kyTicket::FLAG_GREEN:
                    $ticket->classes[] = 'green';
                    break;
                case kyTicket::FLAG_YELLOW:
                    $ticket->classes[] = 'yellow';
                    break;
                case kyTicket::FLAG_RED:
                    $ticket->classes[] = 'red';
                    break;
                case kyTicket::FLAG_BLUE:
                    $ticket->classes[] = 'blue';
                    break;
            }

            $ticket->classes = implode(' ', $ticket->classes);

            $ticket->url = sprintf($app['kappa.ticket_url'], $ticket->getId());

            return $ticket->getId();
        });

        $table = array();

        foreach ($tickets as $ticket) {
            $staffName = $ticket->getOwnerStaffName();

            if (empty($staffName)) {
                $staffName = "Unassigned";
            }

            if (!isset($table[$staffName])) {
                $table[$staffName] = array();

                foreach ($statuses as $statusId => $status) {
                    $table[$staffName][$statusId] = array();
                }
            }

            $table[$staffName][$ticket->getStatusId()][] = $ticket;
        }

        return array(
            'department' => $department,
            'statuses' => $statuses,
            'table' => $table
        );
    }, 50);

    return $app['twig']->render('department.twig', $result);
})->bind('department');


$app->error(function (Exception $e, $code) use ($app) {
    switch ($code) {
        case 404:
            $template = 'not_found.twig';
            break;
        default:
            $template = 'error.twig';
    }

    $content = $app['twig']->render($template);

    return new Symfony\Component\HttpFoundation\Response($content, $code);
});

return $app;
