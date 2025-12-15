<?php

namespace App\EventSubscriber;

use App\Repository\ReclamationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class ReclamationSubscriber implements EventSubscriberInterface
{
    private ReclamationRepository $reclamationRepository;
    private Environment $twig;
    private Security $security;

    public function __construct(
        ReclamationRepository $reclamationRepository, 
        Environment $twig,
        Security $security
    ) {
        $this->reclamationRepository = $reclamationRepository;
        $this->twig = $twig;
        $this->security = $security;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Ne traiter que les requêtes principales (pas les sous-requêtes)
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        
        // Pour les pages ADMIN (Back-Office) : compter TOUTES les réclamations
        if (str_starts_with($path, '/reponse')) {
            $nbReclamations = $this->reclamationRepository->count([]);
            $this->twig->addGlobal('nbReclamations', $nbReclamations);
        }
        // Pour les pages CLIENT (Front-Office) : compter seulement les réclamations du client
        else {
            $nbReclamations = 0;
            if ($user) {
                $nbReclamations = $this->reclamationRepository->count(['client' => $user]);
            }
            $this->twig->addGlobal('nbReclamations', $nbReclamations);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }
}