<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use App\Form\EvenementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Participation;
use App\Repository\ParticipationRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use App\Service\HuggingFaceImageService;


#[Route('/event-admin')]
final class EventAdminController extends AbstractController
{
    #[Route('/event/admin', name: 'app_event_admin')]
    public function index(): Response
    {
        return $this->render('Admin/event_admin/index.html.twig', [
            'controller_name' => 'EventAdminController',
        ]);
    }
    //zedna el barre de recherche bel QL
    #[Route('/affiche', name: 'affiche_admin')]
public function Affiche(Request $request, EvenementRepository $repo)
{
    $search = $request->query->get('search', '');

    $evenements = $repo->searchEvents($search);

    $admin = $this->getUser();

    return $this->render('Admin/event_admin/affiche.html.twig', [
        'evenements' => $evenements,
        'admin' => $admin, 
        'search' => $search
    ]);

}
#[Route('/admin/search-ajax', name: 'search_ajax')]
public function searchAjax(Request $request, EvenementRepository $repo): Response
{
    $search = $request->query->get('search', '');
    $evenements = $repo->searchEvents($search);

    return $this->render('Admin/event_admin/events_list.html.twig', [
        'evenements' => $evenements
    ]);
}








    #[Route('/admin/ajout', name: 'admin_evenement_ajout')]
public function ajout(Request $request, EntityManagerInterface $em , HuggingFaceImageService $hfService): Response
{
    $evenement = new Evenement();

    $admin = $this->getUser();

    $evenement->setCreatedAt(new \DateTimeImmutable());

    $form = $this->createForm(EvenementType::class, $evenement);
    // ->add('Ajout',SubmitType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // Gestion de l'image (mazelt ma3raftech kifech bedhabt )
    //    $file = $form->get('image')->getData();
    //     if ($file) {
    //         $fileName = uniqid() . '.' . $file->guessExtension();
    //         $file->move($this->getParameter('uploads_directory'), $fileName);
    //         $evenement->setImage($fileName);
    //     }

        //Gestion image par huggungFace

       // nrécupèriw le prompt IA
        //$prompt = $form->get('promptIA')->getData();

        // Génération de l'image
        // if ($prompt) {
        //     $generatedFileName = $hfService->generateImage($prompt);
        //     $evenement->setImage($generatedFileName);
        // }



        $em->persist($evenement);
        $em->flush();

        $this->addFlash('success', 'Événement ajouté avec succès');

        return $this->redirectToRoute('affiche_admin');
    }

    return $this->render('Admin/event_admin/ajout.html.twig', [
        'form' => $form ,
         'admin' => $admin ,
        'is_edit' => false,
    ]);
}

 #[Route('/admin/update/{ii}', name: 'admin_evenement_update')]
public function update($ii,Request $request, EntityManagerInterface $em,EvenementRepository $repo, HuggingFaceImageService $hfService): Response
{
    $evenement = $repo->find($ii);
    $admin = $this->getUser();
    $evenement->setCreatedAt(new \DateTimeImmutable());

    $form = $this->createForm(EvenementType::class, $evenement) ;
    //->add('Update',SubmitType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

       /* // Gestion de l'image (mazelt ma3raftech kifech bedhabt )
        $file = $form->get('image')->getData();
        if ($file) {
            $fileName = uniqid() . '.' . $file->guessExtension();
            $file->move($this->getParameter('uploads_directory'), $fileName);
            $evenement->setImage($fileName);
        }*/

         //Gestion image par huggungFace

       // nrécupèriw le prompt IA
        $prompt = $form->get('promptIA')->getData();

        // Génération de l'image
        if ($prompt) {
            $generatedFileName = $hfService->generateImage($prompt);
            $evenement->setImage($generatedFileName);
        }

        
        $em->flush();

        $this->addFlash('success', 'Événement modifié avec succès');

        return $this->redirectToRoute('affiche_admin');
    }

    return $this->render('Admin/event_admin/ajout.html.twig', [
        'form' => $form ,
        'is_edit' => true,
        'admin' => $admin ,
    ]);
}


#[Route('/admin/delete/{ii}', name: 'admin_evenement_delete')]
function Delete(EvenementRepository $repo , $ii,EntityManagerInterface $em){
    $evenement=$repo->find($ii);
    


    $em->remove($evenement);
    $em->flush();
    return $this->redirectToRoute('affiche_admin');
    
}

// gerer les participation :
//bech n afichi se3a w mba3ed na3mil 2bouttons accepter ou refuser

//afficher les participations
#[Route('/admin/participations', name: 'admin_participations')]
public function participations(ParticipationRepository $repo): Response
{
    $list = $repo->findAll();
    $admin = $this->getUser();
    return $this->render('Admin/event_admin/participations.html.twig', [
        'participations' => $list ,
        'admin' => $admin ,
    ]);
}
//Confirmer
#[Route('/admin/participation/confirmer/{id}', name: 'admin_participation_confirmer')]
public function confirmer($id, ParticipationRepository $repo, EntityManagerInterface $em)
{
    $p = $repo->find($id);
    if (!$p) {
        throw $this->createNotFoundException("Participation introuvable");
    }

    $p->setStatut('confirmee');
    $em->flush();

    return $this->redirectToRoute('admin_participations');
}
//Annuler
#[Route('/admin/participation/annuler/{id}', name: 'admin_participation_annuler')]
public function annuler($id, ParticipationRepository $repo, EntityManagerInterface $em)
{
    $p = $repo->find($id);
    if (!$p) {
        throw $this->createNotFoundException("Participation introuvable");
    }

    $p->setStatut('annulee');
    $em->flush();

    return $this->redirectToRoute('admin_participations');
}

}
