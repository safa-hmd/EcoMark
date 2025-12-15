<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ReponseType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReponseRepository;
use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Repository\ReclamationRepository; 
use Symfony\Component\HttpFoundation\Request;

#[Route('/reponse', name: 'reponse')] 
final class ReponseController extends AbstractController


{
    #[Route('/afficherRep', name: 'afficherReponse')]
    public function index(ReclamationRepository $reclamationRepo): Response
{
    $admin = $this->getUser(); 
    return $this->render('Admin/reponse/afficherReponse.html.twig', [
        'reclamations' => $reclamationRepo->findAll(),
            'admin' => $admin

    ]);
}




#[Route('/reclamation/{id}/repondre', name: 'ajouterRep')]
public function ajouterRep(Request $request, EntityManagerInterface $em, Reclamation $reclamation): Response
{
    $admin = $this->getUser(); // l'admin connecté

    // Vérifier que c'est bien un admin
    if (!in_array('ROLE_ADMIN', $admin->getRoles())) {
        throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à répondre aux réclamations");
    }

    $reponse = new Reponse();
    $reponse->setReclamation($reclamation);
    $reponse->setAdmin($admin); // si tu as une relation Reponse->admin

    $form = $this->createForm(ReponseType::class, $reponse);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $reclamation->setStatut('traité');
        $em->persist($reponse);
        $em->flush();

        $this->addFlash('success', 'Réponse ajoutée avec succès !');

        return $this->redirectToRoute('reponseafficherReponse');
    }

    return $this->render('Admin/reponse/ajouterReponse.html.twig', [
        'form' => $form->createView(),
        'reclamation' => $reclamation,
        'admin' => $admin, // on envoie aussi l'admin au template
    ]);
}



#[Route('/modifierRep/{id}', name: 'reponsemodifierRep')]
public function modifierRep(Request $request, EntityManagerInterface $em, Reponse $reponse): Response
{
    $admin = $this->getUser(); // admin connecté

    // Vérifier que c'est bien un admin
    if (!in_array('ROLE_ADMIN', $admin->getRoles())) {
        throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à modifier cette réponse.");
    }

    $reclamation = $reponse->getReclamation(); 
    $form = $this->createForm(ReponseType::class, $reponse);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Optionnel : enregistrer qui a modifié la réponse
        $reponse->setAdmin($admin);

        $em->flush();
        $this->addFlash('success', 'Réponse mise à jour avec succès !');
        return $this->redirectToRoute('reponseafficherReponse');
    }

    return $this->render('Admin/reponse/modifierReponse.html.twig', [
        'form' => $form->createView(),
        'reclamation' => $reclamation,
        'update' => true,
        'admin' => $admin, // envoyer l'admin au template
    ]);
}



#[Route('/Details/{ii}', name: 'det')]
public function Details(ReponseRepository $repo, $ii): Response
{
    $admin = $this->getUser();

    // Vérifie que c'est bien un admin
    if (!in_array('ROLE_ADMIN', $admin->getRoles())) {
        throw $this->createAccessDeniedException("Accès refusé : vous n'êtes pas un admin.");
    }

    // Récupère la réponse
    $reponse = $repo->find($ii);

    // Vérifie que la réponse existe et appartient à une réclamation de cet admin
    if (!$reponse || $reponse->getReclamation()->getAdmin() !== $admin) {
        throw $this->createNotFoundException("Cette réponse n'existe pas ou ne vous appartient pas.");
    }

    return $this->render('Admin/reponse/details.html.twig', [
        'reponse' => $reponse,
        'admin' => $admin
    ]);
}





#[Route('/delete/{ii}', name: 'supprimerReponse')]
public function delete(ReponseRepository $repo, $ii, EntityManagerInterface $em): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $reponse = $repo->find($ii);

    if (!$reponse) {
        throw $this->createNotFoundException("Réponse introuvable.");
    }

    $em->remove($reponse);
    $em->flush();

    return $this->redirectToRoute('reponseafficherReponse');
}



 
}
    

