<?php

namespace App\Controller\Admin;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ReponseType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReponseRepository;
use App\Entity\Reponse;
use App\Entity\Notification;

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
            'admin' => $admin,
            'search' => '',          
            'searchDate' => ''       
        ]);
    }




   #[Route('/reclamation/{id}/repondre', name: 'ajouterRep')]
    public function ajouterRep(Request $request, EntityManagerInterface $em, Reclamation $reclamation): Response
    {
        $admin = $this->getUser();

        $reponse = new Reponse();
        $reponse->setReclamation($reclamation);
        $reponse->setAdmin($admin);
        $reponse->setDateCreation(new \DateTime()); 

        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation->setYes($reponse);
            $reclamation->setStatut('traité');
            
            $em->persist($reponse);

           
            $notification = new Notification();
            $notification->setUser($reclamation->getClient()); 
            $notification->setReclamation($reclamation); 
            $notification->setMessage("Une réponse a été ajoutée à votre réclamation !");
            
            $lien = $this->generateUrl('reclamation_det', 
                ['ii' => $reclamation->getId()], 
                UrlGeneratorInterface::ABSOLUTE_PATH
            );
            $notification->setLien($lien);
            
            $em->persist($notification);
            // ==============================

            $em->flush();

            $this->addFlash('success', 'Réponse ajoutée avec succès !');

            return $this->redirectToRoute('reponseafficherReponse');
        }

        return $this->render('Admin/reponse/ajouterReponse.html.twig', [
            'form' => $form->createView(),
            'reclamation' => $reclamation,
            'admin' => $admin,
        ]);
    }


#[Route('/modifierRep/{id}', name: 'reponsemodifierRep')]
public function modifierRep(Request $request, EntityManagerInterface $em, Reponse $reponse): Response
{
    $admin = $this->getUser(); 

  

    $reclamation = $reponse->getReclamation(); 
    $form = $this->createForm(ReponseType::class, $reponse);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
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
    $reponse = $repo->find($ii);
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

    $em->remove($reponse);
    $em->flush();

    return $this->redirectToRoute('reponseafficherReponse');}




    #[Route('/search', name: 'reponse_search', methods: ['GET'])]
    public function search(Request $request, ReclamationRepository $repo): Response
    {
        $search = $request->query->get('q', '');
        $date = $request->query->get('date', '');
        
        $reclamations = $repo->searchByAllAttributes($search, $date);
        
        return $this->render('Admin/reponse/table_reclamation.html.twig', [
            'reclamations' => $reclamations,
            'search' => $search,
        ]);
    }


#[Route('/test-highlight', name: 'test_highlight')]
public function testHighlight(): Response
{
    return $this->render('test_highlight.html.twig');
}

}

 

    

