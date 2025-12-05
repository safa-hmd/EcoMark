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
        // Vérifier que le profil appartient à un ADMIN
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès refusé : cet utilisateur n'est pas un administrateur");
        }

        return $this->render('Admin/GestionUser/profilAdmin.html.twig', [
            'admin' => $user
        ]);
    }


#[Route('/profilAdmin/{id}/edit', name: 'profilAdmin_edit')]
public function edit(User $user,Request $request,EntityManagerInterface $em,UserPasswordHasherInterface $passwordHasher): Response
{
if (!in_array('ROLE_ADMIN', $user->getRoles())) {
throw $this->createAccessDeniedException("Cet utilisateur n'est pas un admin.");
}


$form = $this->createForm(UserType::class, $user);  
$form->handleRequest($request);  

if ($form->isSubmitted() && $form->isValid()) {

    // --- PHOTO ---
    $file = $form->get('photo')->getData();
    if ($file) {
        $newFilename = uniqid().'.'.$file->guessExtension();
        $file->move($this->getParameter('photos_directory'), $newFilename);
        $user->setPhoto($newFilename);
    }

    // --- PASSWORD ---
    $password = $form->get('password')->getData();
    if ($password) {
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
    }

    $em->persist($user);
    $em->flush();
    return $this->redirectToRoute('profilAdmin', ['id' => $user->getId()]);
}

return $this->render('Admin/GestionUser/modifierProfilAdmin.html.twig', [
    'form' => $form->createView(),
    'admin' => $user,
]);


}





#[Route('/profilAdmin/{id}/delete', name: 'profilAdmin_delete', methods: ['POST'])]
public function delete(User $user, Request $request, EntityManagerInterface $em, TokenStorageInterface $tokenStorage): Response
{
    // Vérifie que c'est un admin
    if (!in_array('ROLE_ADMIN', $user->getRoles())) {
        throw $this->createAccessDeniedException("Cet utilisateur n'est pas un admin.");
    }

    // Vérifie le token CSRF
    $submittedToken = $request->request->get('_token');
    if ($this->isCsrfTokenValid('delete-profil'.$user->getId(), $submittedToken)) {

        // Déconnecte l'utilisateur s'il supprime son propre compte
        $currentUser = $tokenStorage->getToken()?->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();
        }

        // Supprime l'utilisateur
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('app_login');
    }

    return $this->redirectToRoute('app_login');
}



#[Route('/profilAdmin/{id}/mes-clients', name: 'admin_mes_clients')]
public function myClients(UserRepository $userRepository, Request $request, $id): Response
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


 #[Route('/profilAdmin/{id}/mes-vendeurs', name: 'admin_mes_vendeurs')]
public function myVendeurs(UserRepository $userRepository, Request $request, $id): Response
{
    $admin = $this->getUser();

    if (!in_array('ROLE_ADMIN', $admin->getRoles())) {
        throw $this->createAccessDeniedException("Cet utilisateur n'est pas un admin.");
    }

    $searchTerm = $request->query->get('search', '');
    $vendeurs = $userRepository->searchVendeurs($searchTerm);
    $totalVendeurs = count($vendeurs);

    // Si c'est un appel AJAX, renvoyer seulement le HTML des résultats
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


}




