<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; 
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfilAdminController extends AbstractController
{
    #[Route('/profilAdmin', name: 'profilAdmin')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès refusé");
        }

        return $this->render('Admin/GestionUser/profilAdmin.html.twig', [
            'admin' => $user
        ]);
    }



#[Route('/profilAdmin/edit', name: 'profilAdmin_edit')]
public function edit(Request $request,EntityManagerInterface $em,UserPasswordHasherInterface $passwordHasher): Response {
   
    $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès refusé : vous n'êtes pas un admin.");
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
            
        //  Mettre à jour l’activité
        //$user->setLastActivity(new \DateTime());

            $em->persist($user);
            $em->flush();

        return $this->redirectToRoute('profilAdmin');
    }

    return $this->render('Admin/GestionUser/modifierProfilAdmin.html.twig', [
        'form' => $form->createView(),
        'admin' => $user,
    ]);
}




    #[Route('/profilAdmin/delete', name: 'profilAdmin_delete', methods: ['POST'])]
    public function delete(Request $request,EntityManagerInterface $em,TokenStorageInterface $tokenStorage): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès refusé");
        }

        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete-profil'.$user->getId(), $submittedToken)) {
            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();
            
            $em->remove($user);
            $em->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('profilAdmin');
    }



    #[Route('/profilAdmin/mes-clients', name: 'admin_mes_clients')]
    public function myClients(UserRepository $userRepository, Request $request): Response
    {
        $admin = $this->getUser();

       if (!in_array('ROLE_ADMIN', $admin->getRoles())) {
        throw $this->createAccessDeniedException("Cet utilisateur n'est pas un admin.");
    }

        $searchTerm = $request->query->get('search', '');
        $clients = $userRepository->searchClients($searchTerm);
        $totalClients = count($clients);

         // Si c'est un appel AJAX, renvoyer seulement le HTML des résultats
        if ($request->isXmlHttpRequest()) {
            return $this->render('Admin/GestionUser/_clients_results.html.twig', [
                'clients' => $clients
            ]);
        }

        return $this->render('Admin/GestionUser/ListeClient.html.twig', [
            'admin' => $admin,
            'clients' => $clients,
            'totalClients' => $totalClients,
            'searchTerm' => $searchTerm,
            'ajax' => false
        ]);
    }

    #[Route('/profilAdmin/mes-vendeurs', name: 'admin_mes_vendeurs')]
    public function myVendeurs(UserRepository $userRepository, Request $request): Response
    {
        $admin = $this->getUser();

        if (!in_array('ROLE_ADMIN', $admin->getRoles())) {
        throw $this->createAccessDeniedException("Cet utilisateur n'est pas un admin.");
        }

        $searchTerm = $request->query->get('search', '');
        $vendeurs = $userRepository->searchVendeurs($searchTerm);
        $totalVendeurs = count($vendeurs);

        if ($request->isXmlHttpRequest()) {
            return $this->render('Admin/GestionUser/_vendeurs_results.html.twig', [
                'vendeurs' => $vendeurs
            ]);
        }

        return $this->render('Admin/GestionUser/ListeVendeur.html.twig', [
            'admin' => $admin,
            'totalVendeurs' => $totalVendeurs,
            'vendeurs' => $vendeurs,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/admin/statistiques', name: 'admin_statistiques')]
    public function statistiques(UserRepository $userRepository): Response
    {
        $admin = $this->getUser();
        
        if (!$admin instanceof User || !in_array('ROLE_ADMIN', $admin->getRoles())) {
            throw $this->createAccessDeniedException("Accès refusé");
        }
        
        $stats = $userRepository->getSimpleStatistics();
        
        return $this->render('Admin/GestionUser/statistiques.html.twig', [
            'admin' => $admin,
            'stats' => $stats
        ]);
    }
}