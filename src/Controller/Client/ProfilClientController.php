<?php

namespace App\Controller\Client;

use Doctrine\ORM\EntityManagerInterface;
use App\Form\UserType;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; 
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfilClientController extends AbstractController
{
    #[Route('/profilClient', name: 'profil_client')]
    public function profil(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!in_array('ROLE_CLIENT', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès refusé : cet utilisateur n'est pas un client");
        }

        return $this->render('Client/GestionUser/profilClient.html.twig', [
            'client' => $user
        ]);
    }


    #[Route('/profilClient/edit', name: 'profilClient_edit')]
    public function editClient(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();    
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!in_array('ROLE_CLIENT', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès refusé : vous n'êtes pas un client.");
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            //PHOTO
            $file = $form->get('photo')->getData();
            /** @var \App\Entity\User $user */
            if ($file) {
                $newFilename = uniqid().'.'.$file->guessExtension();
                $file->move(
                    $this->getParameter('photos_directory'),
                    $newFilename
                );
                $user->setPhoto($newFilename);
            }

            // mdp
            $password = $form->get('password')->getData();
            if ($password) {
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }

            $em->persist($user);
            $em->flush();
            
            return $this->redirectToRoute('profil_client');
        }

        return $this->render('Client/GestionUser/modifierProfileClient.html.twig', [
            'form' => $form->createView(),
            'client' => $user,   
        ]);
    }


    

    #[Route('/profilClient/delete', name: 'profilClient_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $em, TokenStorageInterface $tokenStorage): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (!in_array('ROLE_CLIENT', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès refusé : vous n'êtes pas un client.");
        }
        // Vérifie le token CSRF
        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete-profil', $submittedToken)) {
            
            // Déconnecte l'utilisateur avant la suppression
            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();
            
        
            $em->remove($user);
            $em->flush();
            return $this->redirectToRoute('app_login');
        }
        
        return $this->redirectToRoute('profil_client');
    }
}