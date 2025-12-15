<?php
namespace App\Controller\Client;

use App\Entity\Commande;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Snappy\Pdf;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;

#[Route('/mes-commandes')]
final class CommandeController extends AbstractController
{
    #[Route(name: 'app_commande_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): Response
    {
        //  OPTION 1 : Afficher toutes les commandes (si admin)
        // return $this->render('client/commande/index.html.twig', [
        //     'commandes' => $commandeRepository->findAll(),
        // ]);

        //  OPTION 2 : Afficher seulement les commandes de l'utilisateur connecté (recommandé)
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        return $this->render('client/commande/index.html.twig', [
            'commandes' => $commandeRepository->findBy(['user' => $user], ['dateCommande' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        // Vérifier que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $commande = new Commande();
        
        // Associer automatiquement l'utilisateur connecté à la commande
        $commande->setUser($this->getUser());
        $commande->setDateCommande(new \DateTime());
        $commande->setStatut('en_attente');
        
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commande);
            $entityManager->flush();
            
            //  Message flash de succès (optionnel)
            $this->addFlash('success', 'Votre commande a été créée avec succès !');
            
            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/commande/new.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        // Vérifier que l'utilisateur connecté est bien le propriétaire de la commande
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        if ($commande->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette commande.');
        }
        
        return $this->render('client/commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_commande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur connecté est bien le propriétaire de la commande
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        if ($commande->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette commande.');
        }
        
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            //  Message flash de succès (optionnel)
            $this->addFlash('success', 'Votre commande a été modifiée avec succès !');
            
            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        //  Vérifier que l'utilisateur connecté est bien le propriétaire de la commande
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        if ($commande->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette commande.');
        }
        
        if ($this->isCsrfTokenValid('delete'.$commande->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($commande);
            $entityManager->flush();
            
            //  Message flash de succès (optionnel)
            $this->addFlash('success', 'Votre commande a été supprimée avec succès !');
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/commande/{id}/facture', name: 'commande_facture')]
public function facture(Commande $commande, Pdf $pdf)
{
    $html = $this->renderView('commande/facture.html.twig', [
        'commande' => $commande,
    ]);

    return new PdfResponse(
        $pdf->getOutputFromHtml($html),
        'facture-commande-'.$commande->getId().'.pdf'
    );
} 





// 🟣 Page pour sélectionner l'adresse sur la carte (EDIT)
#[Route('/{id}/modifier-adresse', name: 'client_commande_modifier_adresse')]
public function modifierAdresse(Commande $commande): Response
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

    if ($commande->getUser() !== $this->getUser()) {
        throw $this->createAccessDeniedException("Accès refusé.");
    }

    return $this->render('client/commande/modifier_adresse.html.twig', [
        'commande' => $commande,
        'mode' => 'edit'
    ]);
}



// Enregistrement de la nouvelle adresse (EDIT)
#[Route('/commande/{id}/update-adresse', name: 'client_commande_update_adresse', methods: ['POST'])]
public function updateAdresse(Request $request, Commande $commande, EntityManagerInterface $em): Response
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

    if ($commande->getUser() !== $this->getUser()) {
        throw $this->createAccessDeniedException("Accès refusé.");
    }

    //  Mettre à jour SEULEMENT l'adresse complète
    $commande->setAdresseLivraison($request->get('adresse'));

    $em->flush();

    $this->addFlash("success", "Adresse mise à jour ✔️");

    return $this->redirectToRoute('app_commande_edit', [
        'id' => $commande->getId()
    ]);
}



// Sélection de l'adresse sur la carte (NEW)
#[Route('/mes-commandes/new/modifier-adresse', name: 'client_commande_modifier_adresse_new')]
public function modifierAdresseNew(): Response
{
    return $this->render('client/commande/modifier_adresse.html.twig', [
        'commande' => null,
        'mode' => 'new'
    ]);
}



//  Enregistrement de l'adresse sélectionnée (NEW)
#[Route('/mes-commandes/new/update-adresse', name: 'client_commande_update_adresse_new', methods: ['POST'])]
public function updateAdresseNew(Request $request): Response
{
    $adresse = $request->get('adresse');

    // 🔥 Stocker l'adresse dans la session pour pré-remplir le formulaire
    $request->getSession()->set('adresse_new_commande', $adresse);

    return $this->redirectToRoute('app_commande_new');
}




}
