<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class JsonExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        
        // Intercepter uniquement les routes /photoAdmin
        if (!str_starts_with($path, '/photoAdmin')) {
            return;
        }
        
        $exception = $event->getThrowable();
        
        // Si c'est une exception d'accès refusé
        if ($exception instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse([
                'success' => false,
                'error' => 'Accès refusé'
            ], 403));
            return;
        }
        
        // Pour toutes les autres exceptions, retourner du JSON
        $statusCode = ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)
            ? $exception->getStatusCode() 
            : 500;
        
        $message = $exception->getMessage() ?: 'Erreur serveur';
        
        // En production, ne pas exposer les détails de l'erreur
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'dev';
        if ($env === 'prod') {
            $message = 'Erreur serveur';
        }
        
        error_log('Exception dans /photoAdmin: ' . $exception->getMessage() . ' - ' . $exception->getTraceAsString());
        
        $event->setResponse(new JsonResponse([
            'success' => false,
            'error' => $message
        ], $statusCode));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }
}

