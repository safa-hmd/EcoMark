<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class UserActivityService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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

        // Transport Gmail forcé (pas besoin de .env)
        $dsn = 'gmail://hamdisafa235@gmail.com:pjquzybnpxwotwed@default';
        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);

        foreach ($inactiveUsers as $user) {
            $email = (new Email())
                ->from('Ecomarket@gmail.com')
                ->to($user->getEmail())
                ->subject('Vous êtes inactif sur Ecomarket')
                ->text("Bonjour {$user->getNom()}, vous n'avez pas été actif depuis plus de $minutes minutes.");

            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                // Log ou debug si problème
                dump("Erreur envoi mail : " . $e->getMessage());
            }
        }
    }
}
