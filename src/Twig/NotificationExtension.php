<?php

namespace App\Twig;

use App\Service\NotificationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    private $notifService;

    public function __construct(NotificationService $notifService)
    {
        $this->notifService = $notifService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('notifications', [$this->notifService, 'getUserNotifications']),
            new TwigFunction('notifNonLues', [$this->notifService, 'getUnreadCount']),
        ];
    }
}
