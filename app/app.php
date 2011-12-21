<?php

$app = require __DIR__ . '/bootstrap.php';

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

$app->get('/statistics', function () use ($app) {
    return $app['twig']->render('statistics.twig');
});

$app->get('/statistics.json', function () use ($app) {
    $makeArray = function($it) {
        $array = array();
        foreach ($it as $object) {
            $array[$object['_id']] = $object;
        }
        return $array;
    };

    $start = time() - 8 * 60 * 60;
    $end = time();

    $statuses = $app['mongo.db']->statuses->find(array(
        'start' => array('$lt' => new MongoDate($end)),
        'end' => array('$gt' => new MongoDate($start)),
    ));

    $ticketIds = array();
    $ownersIds = array();
    foreach ($statuses as $status) {
        if (!in_array($status['ticket'], $ticketIds)) {
            $ticketIds[] = $status['ticket'];
            $ownersIds[] = $status['owner'];
        }
    }

    $tickets = $makeArray($app['mongo.db']->tickets->find(array(
        '_id' => array('$in' => $ticketIds)
    )));

    $owners = $makeArray($app['mongo.db']->owners->find(array(
        '_id' => array('$in' => $ownersIds)
    )));

    $statistics = array(
        'start' => $start,
        'end' => $end,
        'staffs' => array()
    );

    foreach ($statuses as $status) {
        $ownerName = $owners[$status['owner']]['name'];

        isset($statistics['staffs'][$ownerName])
            or $statistics['staffs'][$ownerName] = array();

        $row = array(
            'start' => $status['start']->sec > $start ? $status['start']->sec : $start,
            'end' => $status['end']->sec < $end ? $status['end']->sec : $end,
            'title' => $tickets[$status['ticket']]['subject']
        );

        $statistics['staffs'][$ownerName][] = $row;
    }

    ksort($statistics['staffs']);

    return new Symfony\Component\HttpFoundation\Response(json_encode($statistics), 200, array('Content-Type' => 'application/json'));
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
