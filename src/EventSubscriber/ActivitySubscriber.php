<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;

class ActivitySubscriber implements EventSubscriberInterface
{
    private Security $security;
    private EntityManagerInterface $em;

    public function __construct(Security $security, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
    }

    /**
     * Met à jour la date d'activité en mémoire au début de la requête.
     * Pas de flush ici pour éviter la fermeture de l'EntityManager.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $user->setLastActivity(new \DateTime());

            //  pas de persist/flush ici
        }
    }

    /**
     * Flushe les changements en base à la fin de la requête,
     * quand tout est stable et que l'EntityManager est encore ouvert.
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user instanceof User && $this->em->isOpen()) {
            try {
                $this->em->flush();
            } catch (\Throwable $e) {
                // On capture l'erreur pour éviter de fermer l'EntityManager
                // Tu peux logger l'erreur si besoin
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request'   => 'onKernelRequest',
            'kernel.terminate' => 'onKernelTerminate',
        ];
    }
}
