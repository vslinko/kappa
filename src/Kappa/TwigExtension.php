<?php

namespace Kappa;

use Twig_Filter_Method;
use kyTicket;

class TwigExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'kappa';
    }

    public function getFilters()
    {
        return array(
            'slugify' => new Twig_Filter_Method($this, 'slugify'),
            'statuses' => new Twig_Filter_Method($this, 'statuses'),
        );
    }

    public function slugify($text)
    {
        return preg_replace(array('/[^a-z0-9-]/', '/-+/'), '-', strtolower(trim($text)));
    }

    public function statuses($ticket)
    {
        $statuses = array();

        switch ($ticket->getFlagType()) {
            case kyTicket::FLAG_PURPLE:
                $statuses[] = 'purple';
                break;
            case kyTicket::FLAG_ORANGE:
                $statuses[] = 'orange';
                break;
            case kyTicket::FLAG_GREEN:
                $statuses[] = 'green';
                break;
            case kyTicket::FLAG_YELLOW:
                $statuses[] = 'yellow';
                break;
            case kyTicket::FLAG_RED:
                $statuses[] = 'red';
                break;
            case kyTicket::FLAG_BLUE:
                $statuses[] = 'blue';
                break;
        }

        return implode(' ', $statuses);
    }
}
