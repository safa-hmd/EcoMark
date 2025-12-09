<?php

namespace App\Controller;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\Admin\ProfilAdminController;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{

//     //Route pour lancer la connexion Google
//     #[Route('/connect/google', name: 'connect_google')]
//     public function connect(ClientRegistry $clientRegistry): RedirectResponse
//     {
//         /** @var OAuth2ClientInterface $client */
//         $client = $clientRegistry->getClient('google');

//         // Redirige l'utilisateur vers Google
//         return $client->redirect(
//             ['email', 'profile'], // scopes (optionnel)
//             []                     // options supplémentaires (optionnel)
//         );
//     }

// #[Route('/connect/google/check', name: 'connect_google_check')]
// public function connectCheck(): Response
// {
//     /** @var \App\Entity\User $user */
//     $user = $this->getUser();

//     if (!$user) {
//         throw $this->createAccessDeniedException('Utilisateur non trouvé.');
//     }

//     return $this->redirectToRoute('profilAdmin', [
//         'id' => $user->getId(),
//     ]);
// }





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


//3. Route de callback après connexion
// #[Route('/connect/google/check', name: 'connect_google_check')]
// public function connectCheck(): Response
// {
//     $user = $this->getUser(); // L'utilisateur connecté via OAuth
//     if (!$user) {
//         throw $this->createAccessDeniedException('Utilisateur non trouvé.');
//     }

//     // Redirection selon le rôle
//     /** @var \App\Entity\User $user */
//   if (in_array('ROLE_ADMIN', $user->getRoles())) {
//     return $this->redirectToRoute('profilAdmin', ['id' => $user->getId()]);
// } elseif (in_array('ROLE_VENDEUR', $user->getRoles())) {
//     return $this->redirectToRoute('profilVendeur', ['id' => $user->getId()]);
// } elseif (in_array('ROLE_CLIENT', $user->getRoles())) {
//     return $this->redirectToRoute('profil_client', ['id' => $user->getId()]);
// }
        
//         // Redirection par défaut
//         return $this->redirectToRoute('app_login');
//     }

}




