<?php

$app = require __DIR__ . '/bootstrap.php';

foreach ($app['kappa.in_progress_tickets'] as $ticket) {
    // update ticket
    $ticketRow = array(
        '_id' => $ticket->getId(),
        'subject' => $ticket->getSubject(),
        'creator' => $ticket->getFullname(),
        'last_replier' => $ticket->getLastReplier(),
    );
    $app['mongo.db']->tickets->save($ticketRow);

    // update owner
    $ownerRow = array(
        '_id' => $ticket->getOwnerStaffId(),
        'name' => $ticket->getOwnerStaffName(),
    );
    $app['mongo.db']->owners->save($ownerRow);

    // update status
    $lastStatus = $app['mongo.db']->statuses->find(array(
        'ticket' => $ticketRow['_id']
    ))->sort(array(
        'start' => -1
    ))->getNext();

    if ($lastStatus) {
        $lastStatus['end'] = new MongoDate();
    }

    if ($lastStatus && $lastStatus['owner'] != $ownerRow['_id']) {
        $app['mongo.db']->statuses->save($lastStatus);
        $lastStatus = null;
    }

    if ($lastStatus === null) {
        $lastStatus = array(
            'ticket' => $ticketRow['_id'],
            'start' => new MongoDate(),
            'end' => new MongoDate(),
            'owner' => $ownerRow['_id']
        );
    }

    $app['mongo.db']->statuses->save($lastStatus);
}
