<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; 
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, le rediriger
        if ($this->getUser()) {
            return $this->redirectBasedOnRole();
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode peut être vide - elle sera interceptée par le firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/redirect_based_on_role', name: 'redirect_based_on_role')]
    public function redirectBasedOnRole(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
  
       if (in_array('ROLE_ADMIN', $user->getRoles())) {
        return $this->redirectToRoute('profilAdmin');
    } elseif (in_array('ROLE_VENDEUR', $user->getRoles())) {
        return $this->redirectToRoute('profilVendeur');
    } elseif (in_array('ROLE_CLIENT', $user->getRoles())) {
        return $this->redirectToRoute('profil_client');
    }
        
        // Redirection par défaut
        return $this->redirectToRoute('app_login');
    }
}