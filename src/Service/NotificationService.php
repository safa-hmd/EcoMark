<?php

namespace App\Service;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;

class NotificationService
{
    private $notifRepo;
    private $security;

    public function __construct(NotificationRepository $notifRepo, Security $security)
    {
        $this->notifRepo = $notifRepo;
        $this->security = $security;
    }

    public function getUserNotifications()
    {
        $user = $this->security->getUser();
        if (!$user) return [];
        return $this->notifRepo->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    public function getUnreadCount()
    {
        $user = $this->security->getUser();
        if (!$user) return 0;
        return $this->notifRepo->count(['user' => $user, 'isRead' => false]);
    }
}
