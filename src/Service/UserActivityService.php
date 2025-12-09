<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class UserActivityService
{
    public function __construct(
        private UserRepository $userRepository,
        private MailerInterface $mailer
    ) {
    }

    public function isUserInactive(User $user, int $minutes = 10): bool
    {
        $lastActivity = $user->getLastActivity();
        
        if (!$lastActivity) {
            return true;
        }

        $now = new \DateTime();
        $interval = $now->getTimestamp() - $lastActivity->getTimestamp();

        return $interval > ($minutes * 60);
    }

    public function checkInactiveUsers(int $minutes = 10): void
    {
        $inactiveUsers = $this->userRepository->findInactiveUsers($minutes);

        foreach ($inactiveUsers as $user) {
            $email = (new Email())
                ->from('no-reply@ecomarket.com')
                ->to($user->getEmail())
                ->subject('Vous êtes inactif sur Ecomarket')
                ->text("Bonjour {$user->getNom()}, vous n'avez pas été actif depuis plus de $minutes minutes.");

            $this->mailer->send($email);
        }
    }
}