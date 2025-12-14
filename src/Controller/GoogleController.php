<?php

namespace App\Controller;
use Symfony\Component\HttpFoundation\Response;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{
#[Route('/connect/google', name: 'connect_google')]
public function connect(ClientRegistry $clientRegistry): RedirectResponse
{
    return $clientRegistry->getClient('google')
        ->redirect(['email', 'profile'], []);
}



#[Route('/connect/google/check', name: 'connect_google_check')]
public function connectCheck(): Response
{
    // Cette fonction sera gérée automatiquement par l’authenticator
    return new Response("Google callback");
}
}




