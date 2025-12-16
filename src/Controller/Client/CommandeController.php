<?php
namespace App\Controller\Client;

use App\Entity\Commande;
use App\Entity\User;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        return $this->render('client/commande/index.html.twig', [
            'commandes' => $commandeRepository->findBy(['user' => $user], ['dateCommande' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ProduitRepository $produitRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }
        
        // 🔥 RÉCUPÉRER LES PRODUITS ACHETÉS (dans "Mes Achats")
        $produitsAchetes = $produitRepository->createQueryBuilder('p')
            ->where('p.description LIKE :pattern')
            ->setParameter('pattern', '%ACHETE_PAR:' . $user->getId() . '%')
            ->getQuery()
            ->getResult();
        
        // ⚠️ VÉRIFIER SI L'UTILISATEUR A DES ACHATS
        if (empty($produitsAchetes)) {
            $this->addFlash('error', 'Vous devez d\'abord acheter des produits avant de créer une commande.');
            return $this->redirectToRoute('app_client_mes_achats');
        }
        
        $commande = new Commande();
        $commande->setUser($user);
        $commande->setDateCommande(new \DateTime());
        $commande->setStatut('en_attente');
        
        // 💰 AJOUTER LES PRODUITS ACHETÉS À LA COMMANDE
        foreach ($produitsAchetes as $produit) {
            $commande->addProduit($produit);
        }
        
        // 💵 CALCULER LE MONTANT TOTAL
        $montantTotal = 0;
        foreach ($produitsAchetes as $produit) {
            $montantTotal += $produit->getPrix();
        }
        $commande->setMontantTotal($montantTotal);

        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commande);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commande a été créée avec succès !');
            
            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/commande/new.html.twig', [
            'commande' => $commande,
            'form' => $form->createView(),
            'produitsAchetes' => $produitsAchetes,
            'montantTotal' => $montantTotal,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        if ($commande->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette commande.');
        }
        
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre commande a été modifiée avec succès !');
            
            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        if ($commande->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette commande.');
        }

        if ($this->isCsrfTokenValid('delete' . $commande->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($commande);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre commande a été supprimée avec succès !');
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/commande/{id}/facture', name: 'commande_facture')]
    public function facture(Commande $commande, Pdf $pdf): Response
    {
        $html = $this->renderView('commande/facture.html.twig', [
            'commande' => $commande,
        ]);

        return new PdfResponse(
            $pdf->getOutputFromHtml($html),
            'facture-commande-' . $commande->getId() . '.pdf'
        );
    }

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

    #[Route('/commande/{id}/update-adresse', name: 'client_commande_update_adresse', methods: ['POST'])]
    public function updateAdresse(Request $request, Commande $commande, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($commande->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }

        $commande->setAdresseLivraison($request->getPayload()->getString('adresse'));

        $em->flush();

        $this->addFlash("success", "Adresse mise à jour ✔️");

        return $this->redirectToRoute('app_commande_edit', [
            'id' => $commande->getId()
        ]);
    }

    #[Route('/mes-commandes/new/modifier-adresse', name: 'client_commande_modifier_adresse_new')]
    public function modifierAdresseNew(): Response
    {
        return $this->render('client/commande/modifier_adresse.html.twig', [
            'commande' => null,
            'mode' => 'new'
        ]);
    }

    #[Route('/mes-commandes/new/update-adresse', name: 'client_commande_update_adresse_new', methods: ['POST'])]
    public function updateAdresseNew(Request $request): Response
    {
        $adresse = $request->getPayload()->getString('adresse');

        $request->getSession()->set('adresse_new_commande', $adresse);

        return $this->redirectToRoute('app_commande_new');
    }
}