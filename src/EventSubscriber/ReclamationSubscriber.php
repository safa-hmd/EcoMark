<?php

namespace App\EventSubscriber;

use App\Repository\ReclamationRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class ReclamationSubscriber implements EventSubscriberInterface
{
    private ReclamationRepository $reclamationRepository;
    private Environment $twig;

    public function __construct(ReclamationRepository $reclamationRepository, Environment $twig)
    {
        $this->reclamationRepository = $reclamationRepository;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Compter toutes les réclamations
        $nbReclamations = $this->reclamationRepository->count([]);
        
        // Toutes les pages FrontOffice (FO) sauf admin
        if (!str_starts_with($path, '/admin')) {
            $this->twig->addGlobal('nbReclamations', $nbReclamations);
        }

        // Toutes les pages Admin (BO)
        if (str_starts_with($path, '/admin')) {
            $this->twig->addGlobal('nbReclamationsAdmin', $nbReclamations);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }
}
