<?php

namespace App\Controller\Vendeur;

use App\Entity\Produit;
use App\Entity\User;
use App\Service\AlerteStockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/vendeur/alertes')]
class AlerteStockController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_vendeur_alertes_index', methods: ['GET'])]
    public function index(AlerteStockService $alerteStockService): Response
    {
        // Version simplifiée sans authentification
        // Récupère tous les produits pour l'analyse
        $produits = $this->entityManager->getRepository(Produit::class)->findAll();
        
        // Crée un objet vendeur simulé COMPLET pour éviter les erreurs de template
        $vendeurSimule = new class($produits) {
            private $produits;
            private $id = 1;
            private $nom = 'Administrateur';
            private $prenom = 'Système'; // Important pour le template BO.html.twig
            private $email = 'admin@ecomarket.com';
            private $roles = ['ROLE_VENDEUR', 'ROLE_ADMIN'];
            private $pointsFidelite = 200;

            public function __construct($produits)
            {
                $this->produits = $produits;
            }

            // Méthode requise par AlerteStockService
            public function getProduits()
            {
                return $this->produits;
            }

            // Getters standards
            public function getId(): int { return $this->id; }
            public function getNom(): string { return $this->nom; }
            public function getPrenom(): string { return $this->prenom; }
            public function getEmail(): string { return $this->email; }
            public function getRoles(): array { return $this->roles; }
            public function getPointsFidelite(): int { return $this->pointsFidelite; }
            
            // Méthodes utilitaires
            public function getNomComplet(): string { 
                return $this->prenom . ' ' . $this->nom; 
            }
            
            public function getUsername(): string { 
                return $this->email; 
            }
            
            public function getUserIdentifier(): string { 
                return $this->email; 
            }
            
            public function __toString(): string { 
                return $this->getNomComplet(); 
            }
            
            // Pour compatibilité avec l'accès aux propriétés dans Twig
            public function __get($name)
            {
                $method = 'get' . ucfirst($name);
                if (method_exists($this, $method)) {
                    return $this->$method();
                }
                
                // Tentative alternative
                $method = 'is' . ucfirst($name);
                if (method_exists($this, $method)) {
                    return $this->$method();
                }
                
                return null;
            }
            
            public function __isset($name): bool
            {
                $method = 'get' . ucfirst($name);
                if (method_exists($this, $method)) {
                    return true;
                }
                
                $method = 'is' . ucfirst($name);
                if (method_exists($this, $method)) {
                    return true;
                }
                
                return property_exists($this, $name);
            }
        };
        
        try {
            $rapport = $alerteStockService->genererRapportStock($vendeurSimule);
        } catch (\Exception $e) {
            // En cas d'erreur, affiche un message d'erreur
            return $this->render('vendeur/produit/alertes/error.html.twig', [
                'message' => 'Erreur lors de l\'analyse du stock: ' . $e->getMessage(),
                'erreur_details' => $this->getParameter('kernel.debug') ? $e->getTraceAsString() : null,
            ]);
        }
        
        return $this->render('vendeur/produit/alertes/index.html.twig', [
            'rapport' => $rapport,
            'vendeur' => $vendeurSimule,
        ]);
    }

    #[Route('/api/statut', name: 'app_vendeur_alertes_api', methods: ['GET'])]
    public function apiStatut(AlerteStockService $alerteStockService): JsonResponse
    {
        // Version API sans authentification
        $produits = $this->entityManager->getRepository(Produit::class)->findAll();
        
        // Vendeur simulé minimal pour l'API
        $vendeurSimule = new class($produits) {
            private $produits;
            public function __construct($produits) { 
                $this->produits = $produits; 
            }
            public function getProduits() { 
                return $this->produits; 
            }
        };

        try {
            $rapport = $alerteStockService->genererRapportStock($vendeurSimule);
            
            return new JsonResponse([
                'alertes_urgentes' => $rapport['statistiques']['alertes_urgentes'] ?? 0,
                'alertes_moyennes' => $rapport['statistiques']['alertes_moyennes'] ?? 0,
                'alertes_faibles' => $rapport['statistiques']['alertes_faibles'] ?? 0,
                'total_produits' => $rapport['statistiques']['total_produits'] ?? 0,
                'risque_total' => $rapport['statistiques']['risque_total'] ?? 0,
                'stock_total_valeur' => $rapport['statistiques']['stock_total_valeur'] ?? 0,
                'stock_moyen_valeur' => $rapport['statistiques']['stock_moyen_valeur'] ?? 0,
                'derniere_mise_a_jour' => new \DateTime(),
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur d\'analyse',
                'message' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    #[Route('/produit/{id}/analyse', name: 'app_vendeur_produit_analyse', methods: ['GET'])]
    public function analyseProduit(int $id, AlerteStockService $alerteStockService): Response
    {
        // Récupère le produit par ID
        $produit = $this->entityManager->getRepository(Produit::class)->find($id);
        
        if (!$produit) {
            return $this->render('vendeur/produit/alertes/error.html.twig', [
                'message' => 'Produit non trouvé avec l\'ID: ' . $id,
                'suggestion' => 'Vérifiez que le produit existe dans la base de données.',
            ]);
        }

        try {
            $analyse = $alerteStockService->analyserProduit($produit);
            $recommandation = $alerteStockService->recommanderReapprovisionnement($produit);
        } catch (\Exception $e) {
            return $this->render('vendeur/produit/alertes/error.html.twig', [
                'message' => 'Erreur lors de l\'analyse du produit: ' . $e->getMessage(),
                'produit_nom' => $produit->getNomProduit(),
                'produit_id' => $produit->getId(),
            ]);
        }
        
        return $this->render('vendeur/produit/alertes/analyse_produit.html.twig', [
            'produit' => $produit,
            'analyse' => $analyse,
            'recommandation' => $recommandation,
            'vendeur' => $this->createVendeurSimule([$produit]),
        ]);
    }

    #[Route('/test', name: 'app_vendeur_alertes_test', methods: ['GET'])]
    public function testService(AlerteStockService $alerteStockService): Response
    {
        // Page de test pour vérifier le fonctionnement du service
        $produits = $this->entityManager->getRepository(Produit::class)->findAll();
        
        if (empty($produits)) {
            return $this->render('vendeur/produit/alertes/test.html.twig', [
                'message' => '⚠️ Aucun produit trouvé dans la base de données.',
                'produits' => [],
                'tests' => [],
                'vendeur' => $this->createVendeurSimule([]),
            ]);
        }

        $tests = [];
        $produitTest = $produits[0]; // Premier produit pour les tests
        
        // Test 1: Analyse basique d'un produit
        try {
            $analyse = $alerteStockService->analyserProduit($produitTest);
            $tests[] = [
                'nom' => '✅ Analyse produit',
                'statut' => 'success',
                'details' => [
                    'produit' => $produitTest->getNomProduit(),
                    'stock' => $produitTest->getQuantiteStock(),
                    'niveau_alerte' => $analyse['niveau_alerte'] ?? 'inconnu',
                    'jours_avant_rupture' => $analyse['jours_avant_rupture'] ?? 0,
                ]
            ];
        } catch (\Exception $e) {
            $tests[] = [
                'nom' => '❌ Analyse produit',
                'statut' => 'error',
                'message' => $e->getMessage()
            ];
        }
        
        // Test 2: Recommandation de réapprovisionnement
        try {
            $recommandation = $alerteStockService->recommanderReapprovisionnement($produitTest);
            $tests[] = [
                'nom' => '✅ Recommandation réapprovisionnement',
                'statut' => 'success',
                'details' => [
                    'quantite_recommandee' => $recommandation['quantite_recommandee'] ?? 0,
                    'investissement' => $recommandation['investissement_estime'] ?? 0,
                ]
            ];
        } catch (\Exception $e) {
            $tests[] = [
                'nom' => '❌ Recommandation réapprovisionnement',
                'statut' => 'error',
                'message' => $e->getMessage()
            ];
        }
        
        // Test 3: Génération de rapport complet
        $vendeurTest = $this->createVendeurSimule($produits);
        try {
            $rapport = $alerteStockService->genererRapportStock($vendeurTest);
            $tests[] = [
                'nom' => '✅ Génération rapport complet',
                'statut' => 'success',
                'details' => [
                    'total_produits' => $rapport['statistiques']['total_produits'] ?? 0,
                    'alertes_urgentes' => $rapport['statistiques']['alertes_urgentes'] ?? 0,
                    'risque_total' => $rapport['statistiques']['risque_total'] ?? 0,
                ]
            ];
        } catch (\Exception $e) {
            $tests[] = [
                'nom' => '❌ Génération rapport complet',
                'statut' => 'error',
                'message' => $e->getMessage()
            ];
        }

        return $this->render('vendeur/produit/alertes/test.html.twig', [
            'message' => '🧪 Tests du service d\'alertes de stock',
            'produits' => array_slice($produits, 0, 10), // Limite à 10 produits pour l'affichage
            'tests' => $tests,
            'produit_test' => $produitTest,
            'vendeur' => $vendeurTest,
        ]);
    }

    #[Route('/export/json', name: 'app_vendeur_alertes_export_json', methods: ['GET'])]
    public function exportJson(AlerteStockService $alerteStockService): JsonResponse
    {
        // Export des données en JSON
        $produits = $this->entityManager->getRepository(Produit::class)->findAll();
        $vendeurSimule = $this->createVendeurSimule($produits);
        
        try {
            $rapport = $alerteStockService->genererRapportStock($vendeurSimule);
            
            return new JsonResponse([
                'status' => 'success',
                'export_date' => new \DateTime(),
                'data' => $rapport,
                'metadata' => [
                    'total_produits' => count($produits),
                    'service_version' => '1.0',
                    'format' => 'JSON'
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
                'export_date' => new \DateTime(),
            ], 500);
        }
    }

    /**
     * Crée un objet vendeur simulé avec toutes les propriétés nécessaires
     */
    private function createVendeurSimule(array $produits): object
    {
        return new class($produits) {
            private $produits;
            private $id = 1;
            private $nom = 'Vendeur';
            private $prenom = 'Test';
            private $email = 'vendeur@ecomarket.com';
            private $roles = ['ROLE_VENDEUR'];
            private $pointsFidelite = 150;

            public function __construct($produits)
            {
                $this->produits = $produits;
            }

            public function getProduits() { return $this->produits; }
            public function getId(): int { return $this->id; }
            public function getNom(): string { return $this->nom; }
            public function getPrenom(): string { return $this->prenom; }
            public function getEmail(): string { return $this->email; }
            public function getRoles(): array { return $this->roles; }
            public function getPointsFidelite(): int { return $this->pointsFidelite; }
            public function getNomComplet(): string { return $this->prenom . ' ' . $this->nom; }
            public function getUsername(): string { return $this->email; }
            public function getUserIdentifier(): string { return $this->email; }
            public function __toString(): string { return $this->getNomComplet(); }
            
            // Pour compatibilité Twig
            public function __get($name)
            {
                $method = 'get' . ucfirst($name);
                return method_exists($this, $method) ? $this->$method() : null;
            }
            
            public function __isset($name): bool
            {
                $method = 'get' . ucfirst($name);
                return method_exists($this, $method) || property_exists($this, $name);
            }
        };
    }
    /*#[Route('/sms/alertes', name: 'app_vendeur_sms_alertes', methods: ['GET', 'POST'])]
public function smsAlertes(SimpleSmsService $smsService): Response
{
    $rapport = $smsService->scannerEtAlerterStocksCritiques();
    
    return $this->render('vendeur/alertes/sms_rapport.html.twig', [
        'rapport' => $rapport,
        'service_actif' => $smsService->isActive(),
        'vendeurs' => $smsService->getVendeursConfig()
    ]);
}*/
}