<?php
// src/Controller/Vendeur/StatsController.php

namespace App\Controller\Vendeur;

use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatsController extends AbstractController
{
    private $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    #[Route('/vendeur/stats', name: 'app_vendeur_stats')]
    public function index(): Response
    {
        $vendeur = $this->getUser();
        
        // Récupérer TOUS les produits
        $produits = $this->entityManager->getRepository(Produit::class)->findAll();
        if (empty($produits)) {
        $this->addFlash('warning', 'Aucun produit trouvé dans la base de données.');
    }
        
        // Calcul des statistiques
        $totalProduits = count($produits);
        $produitsCritiques = 0;
        $produitsFaibles = 0;
        $produitsSuffisants = 0;
        $totalStock = 0;
        $valeurTotaleStock = 0;
        $produitsParEtat = [];
        
        foreach ($produits as $produit) {
            $stock = $produit->getQuantiteStock();
            $totalStock += $stock;
            
            // Calculer la valeur du stock
            if (method_exists($produit, 'getPrix')) {
                $valeurTotaleStock += ($produit->getPrix() * $stock);
            }
            
            // Compter par niveau de stock
            if ($stock <= 3) {
                $produitsCritiques++;
            } elseif ($stock <= 10) {
                $produitsFaibles++;
            } else {
                $produitsSuffisants++;
            }
            
            // Compter par état du produit
            $etat = $produit->getEtatProduit() ?? 'Non défini';
            if (!isset($produitsParEtat[$etat])) {
                $produitsParEtat[$etat] = 0;
            }
            $produitsParEtat[$etat]++;
        }
        
        $moyenneStock = $totalProduits > 0 ? round($totalStock / $totalProduits, 1) : 0;
        
        // Top 10 produits avec le plus faible stock
        $produitsTries = $produits;
        usort($produitsTries, function($a, $b) {
            return $a->getQuantiteStock() - $b->getQuantiteStock();
        });
        $top10Critiques = array_slice($produitsTries, 0, min(10, count($produitsTries)));
        
        // Top 15 produits pour le graphique (par défaut les 15 premiers par stock)
        $top15Produits = array_slice($produits, 0, min(15, count($produits)));
        
        // Formatage des produits pour le JavaScript
        $produitsFormatted = [];
        foreach ($top15Produits as $produit) {
            $produitsFormatted[] = [
                'nom' => $produit->getNomProduit() ?? $produit->getNom() ?? 'Produit sans nom',
                'stock' => $produit->getQuantiteStock(),
                'etat' => $produit->getEtatProduit() ?? 'Non défini',
                'nomComplet' => $produit->getNomProduit() ?? $produit->getNom() ?? 'Produit sans nom',
                'id' => $produit->getId(),
            ];
        }
        
        // Formatage des états pour le JavaScript
        $etatsFormatted = [];
        foreach ($produitsParEtat as $etat => $count) {
            $etatsFormatted[] = [
                'etat' => $etat,
                'count' => $count,
            ];
        }
         dump([
        'total_produits' => count($produits),
        'produits_formatted' => count($produitsFormatted),
        'etats' => $produitsParEtat
    ]);
        // RETOURNER TOUTES LES VARIABLES NÉCESSAIRES
        return $this->render('vendeur/stats/index.html.twig', [
            'vendeur' => $vendeur,
            'produits' => $produits,
            'top15Produits' => $top15Produits,
            'produitsFormatted' => $produitsFormatted,
            'etatsFormatted' => $etatsFormatted,
            'top10Critiques' => $top10Critiques,
            'produitsParEtat' => $produitsParEtat,
            'stats' => [
                'totalProduits' => $totalProduits,
                'produitsCritiques' => $produitsCritiques,
                'produitsFaibles' => $produitsFaibles,
                'produitsSuffisants' => $produitsSuffisants,
                'totalStock' => $totalStock,
                'moyenneStock' => $moyenneStock,
                'valeurTotaleStock' => $valeurTotaleStock,
            ]
        ]);
    }
    
    #[Route('/vendeur/stats/export', name: 'app_vendeur_stats_export')]
    public function export(): Response
    {
        // Pour une future fonctionnalité d'export PDF/Excel
        $this->addFlash('info', 'Fonctionnalité d\'export à venir bientôt!');
        return $this->redirectToRoute('app_vendeur_stats');
    }
    
    #[Route('/vendeur/stats/etat', name: 'app_vendeur_stats_etat')]
    public function etat(): Response
    {
        // Page dédiée aux statistiques par état
        $produits = $this->entityManager->getRepository(Produit::class)->findAll();
        
        $produitsParEtat = [];
        foreach ($produits as $produit) {
            $etat = $produit->getEtatProduit() ?? 'Non défini';
            if (!isset($produitsParEtat[$etat])) {
                $produitsParEtat[$etat] = 0;
            }
            $produitsParEtat[$etat]++;
        }
        
        return $this->render('vendeur/stats/etat.html.twig', [
            'produitsParEtat' => $produitsParEtat,
            'totalProduits' => count($produits),
        ]);
    }
    
    #[Route('/vendeur/stats/critiques', name: 'app_vendeur_stats_critiques')]
    public function critiques(): Response
    {
        // Page dédiée aux produits critiques
        $produits = $this->entityManager->getRepository(Produit::class)->findAll();
        
        $produitsCritiques = [];
        foreach ($produits as $produit) {
            if ($produit->getQuantiteStock() <= 3) {
                $produitsCritiques[] = $produit;
            }
        }
        
        return $this->render('vendeur/stats/critiques.html.twig', [
            'produitsCritiques' => $produitsCritiques,
            'totalCritiques' => count($produitsCritiques),
        ]);
    }
}