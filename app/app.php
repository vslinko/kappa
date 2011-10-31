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

$app->get('/', function () use ($app, $storage, $each) {
    $department = $storage('kappa:department', function () use ($app) {
        return kyDepartment::get($app['kappa.department']);
    });

    $statuses = $storage('kappa:statuses', function () use ($app, $each) {
        $statuses = array();

        foreach ($app['kappa.statuses'] as $statusId) {
            $statuses[] = kyTicketStatus::get($statusId);
        }

        uasort($statuses, function ($a, $b) {
             return $a->getDisplayOrder() > $b->getDisplayOrder();
        });

        return new kyResultSet($statuses);
    });

    $staffs = $storage('kappa:staff', function () use ($app) {
        $staffs = array();

        foreach ($app['kappa.staff'] as $staffId) {
            $staffs[] = kyStaff::get($staffId);
        }

        uasort($staffs, function ($a, $b) {
             return $a->getFullname() > $b->getFullName();
        });

        return new kyResultSet($staffs);
    });

    $tickets = kyTicket::getAll($department, $statuses, $staffs)->orderByStatusId();

    $statuses = $each($statuses, function ($status) {
        return $status->getId();
    });

    $staffs = $each($staffs, function ($staffs) {
        return $staffs->getId();
    });

    $tickets = $each($tickets, function (&$ticket) use ($app) {
        $lastActivity = new DateTime($ticket->getLastActivity());

        $ticket->new = time() - $lastActivity->getTimestamp() < 600;

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
    foreach ($staffs as $staffId => $staff) {
        $table[$staffId] = array();

        foreach ($statuses as $statusId => $status) {
            $table[$staffId][$statusId] = array_filter($tickets, function ($ticket) use ($staffId, $statusId) {
                return $ticket->getOwnerStaffId() == $staffId && $ticket->getStatusId() == $statusId;
            });
        }
    }

    return $app['twig']->render('kappa.twig', array(
        'statuses' => $statuses,
        'staffs' => $staffs,
        'table' => $table,
        'column_size' => 100/(count($statuses)+1),
        'row_size' => 95/count($staffs)
    ));
});

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
