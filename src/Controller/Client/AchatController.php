<?php
// src/Controller/Client/AchatController.php

namespace App\Controller\Client;

use App\Entity\Produit;
use App\Entity\User;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/client/achat')]
class AchatController extends AbstractController
{
    #[Route('/acheter/{id}', name: 'app_client_achat_acheter', methods: ['POST'])]
    public function acheter(Produit $produit, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour acheter un produit.');
            return $this->redirectToRoute('app_client_produit_index');
        }

        /** @var User $user */
        $user = $this->getUser();

        // Vérifier si le produit est disponible
        if ($produit->getQuantiteStock() <= 0 || $produit->getEtatProduit() !== 'Disponible') {
            $this->addFlash('error', 'Ce produit n\'est plus disponible.');
            return $this->redirectToRoute('app_client_produit_show', ['id' => $produit->getId()]);
        }

        // Solution temporaire : marquer l'achat dans la description
        $ancienneDescription = $produit->getDescription();
        $produit->setDescription($ancienneDescription . " [ACHETE_PAR:" . $user->getId() . "_LE:" . date('Y-m-d') . "]");
        
        // Réduire le stock
        $produit->setQuantiteStock($produit->getQuantiteStock() - 1);
        
        if ($produit->getQuantiteStock() <= 0) {
            $produit->setEtatProduit('Vendu');
        }

        $entityManager->flush();

        $this->addFlash('success', 'Produit acheté avec succès ! Merci pour votre achat écologique !');
        return $this->redirectToRoute('app_client_mes_achats');
    }

    #[Route('/mes-achats', name: 'app_client_mes_achats', methods: ['GET'])]
    public function mesAchats(ProduitRepository $produitRepository): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            return $this->render('Client/achat/mes_achats.html.twig', [
                'produitsAchetes' => [],
                'userNotConnected' => true
            ]);
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les produits achetés par cet utilisateur
        $produitsAchetes = $produitRepository->createQueryBuilder('p')
            ->where('p.description LIKE :pattern')
            ->setParameter('pattern', '%ACHETE_PAR:' . $user->getId() . '%')
            ->getQuery()
            ->getResult();

        return $this->render('Client/achat/mes_achats.html.twig', [
            'produitsAchetes' => $produitsAchetes,
            'userNotConnected' => false
        ]);
    }

    #[Route('/annuler/{id}', name: 'app_client_achat_annuler', methods: ['POST'])]
    public function annuler(Produit $produit, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour annuler un achat.');
            return $this->redirectToRoute('app_client_produit_index');
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a bien acheté ce produit
        $pattern = 'ACHETE_PAR:' . $user->getId();
        if (strpos($produit->getDescription(), $pattern) === false) {
            $this->addFlash('error', 'Vous ne pouvez pas annuler cet achat.');
            return $this->redirectToRoute('app_client_mes_achats');
        }

        // Restaurer la description originale
        $description = preg_replace('/\s*\[ACHETE_PAR:\d+_LE:\d{4}-\d{2}-\d{2}\]\s*/', '', $produit->getDescription());
        $produit->setDescription($description);
        
        // Remettre le stock
        $produit->setQuantiteStock($produit->getQuantiteStock() + 1);
        $produit->setEtatProduit('Disponible');

        $entityManager->flush();

        $this->addFlash('success', 'Achat annulé avec succès !');
        return $this->redirectToRoute('app_client_mes_achats');
    }
}