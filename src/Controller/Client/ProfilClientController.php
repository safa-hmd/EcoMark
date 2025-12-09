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
        // Vérifier que l'utilisateur a le rôle Client
        if (!in_array('ROLE_CLIENT', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès refusé : cet utilisateur n'est pas un client");
        }

        return $this->render('Client/GestionUser/profilClient.html.twig', [
            'client' => $user
        ]);
    }



#[Route('/profilClient/{id}/edit', name: 'profilClient_edit')]
public function editClient(User $user,Request $request,EntityManagerInterface $em,UserPasswordHasherInterface $passwordHasher): Response
{
// Vérifier que c'est un client
if (!in_array('ROLE_CLIENT', $user->getRoles())) {
throw $this->createAccessDeniedException("Cet utilisateur n'est pas un client.");
}


$form = $this->createForm(UserType::class, $user);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {

    // --- TRAITEMENT DE LA PHOTO ---
    $file = $form->get('photo')->getData();
    if ($file) {
        $newFilename = uniqid().'.'.$file->guessExtension();
        $file->move(
            $this->getParameter('photos_directory'),
            $newFilename
        );
        $user->setPhoto($newFilename);
    }

    // --- TRAITEMENT DU MOT DE PASSE ---
    $password = $form->get('password')->getData();
    if ($password) {
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
    }

    $em->persist($user);
    $em->flush();
    
    return $this->redirectToRoute('profil_client', ['id' => $user->getId()]);
}

return $this->render('Client/GestionUser/modifierProfileClient.html.twig', [
    'form' => $form->createView(),
    'client' => $user,   
]);


}





#[Route('/profilClient/{id}/delete', name: 'profilClient_delete', methods: ['POST'])]
public function delete(User $user, Request $request, EntityManagerInterface $em, TokenStorageInterface $tokenStorage): Response
{
    // Vérifie que c'est un admin
    if (!in_array('ROLE_CLIENT', $user->getRoles())) {
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



}
