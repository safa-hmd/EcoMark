<?php

namespace App\Controller\Vendeur;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; 
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfilVendeurController extends AbstractController
{
    #[Route('/profilVendeur', name: 'profilVendeur')]
    public function index(): Response
    {
          $user = $this->getUser();
        if (!in_array('ROLE_VENDEUR', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès refusé : cet utilisateur n'est pas un vendeuristrateur");
        }

        return $this->render('Vendeur/GestionUser/profilVendeur.html.twig', [
            'vendeur' => $user
        ]);
    }



#[Route('/profilVendeur/{id}/edit', name: 'profilVendeur_edit')]
public function editVendeur(User $user,Request $request,EntityManagerInterface $em,UserPasswordHasherInterface $passwordHasher): Response
{
// Vérifier que c'est un vendeur
if (!in_array('ROLE_VENDEUR', $user->getRoles())) {
throw $this->createAccessDeniedException("Cet utilisateur n'est pas un vendeur.");
}


$form = $this->createForm(UserType::class, $user);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {

    // PHOTO 
    $file = $form->get('photo')->getData();
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

    $this->addFlash('success', 'Profil mis à jour avec succès !');
    return $this->redirectToRoute('profilVendeur', ['id' => $user->getId()]);
}

return $this->render('Vendeur/GestionUser/modifierProfilVendeur.html.twig', [
    'form' => $form->createView(),
    'vendeur' => $user,
]);


}


#[Route('/profilVendeur/{id}/delete', name: 'profilVendeur_delete', methods: ['POST'])]
public function delete(User $user, Request $request, EntityManagerInterface $em, TokenStorageInterface $tokenStorage): Response
{
    // Vérifie que c'est un admin
    if (!in_array('ROLE_VENDEUR', $user->getRoles())) {
        throw $this->createAccessDeniedException("Cet utilisateur n'est pas un Vendeur.");
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

        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('app_login');
    }

    return $this->redirectToRoute('app_login');
}



}
