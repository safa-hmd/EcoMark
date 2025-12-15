<?php

namespace App\Service;

use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


use App\Service\PredictionService;
class AlerteStockService
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
     private PredictionService $predictionService;
 private SimpleSmsService $smsService;

    // Constantes configurables
    private const SEUIL_URGENT = 5;     // Alerte rouge : < 5 unités
    private const SEUIL_MOYEN = 15;     // Alerte orange : < 15 unités  
    private const SEUIL_FAIBLE = 30;    // Alerte jaune : < 30 unités
    private const DELAI_LIVRAISON = 7;  // Délai moyen de réapprovisionnement


  
    
    public function __construct(
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        PredictionService $predictionService,
        SimpleSmsService $smsService
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->predictionService = $predictionService;
    }

public function analyserProduitAvecPrediction(Produit $produit): array
    {
        $analyseBase = $this->analyserProduit($produit);
        $prediction = $this->predictionService->predireDemande($produit);
        $rupture = $this->predictionService->estimerRupture($produit);
        
        return array_merge($analyseBase, [
            'prediction' => $prediction,
            'rupture' => $rupture,
            'intelligence_ajoutee' => true
        ]);
    }

    /**
     * Analyse un produit et prédit le risque de rupture
     */
    public function analyserProduit(Produit $produit): array
    {
        $stockActuel = $produit->getQuantiteStock();
        $tendance = $this->analyserTendanceStock($produit);
        
        // 1. Évaluation du risque immédiat
        $risqueImmediat = $this->evaluerRisqueImmediat($stockActuel);
        
        // 2. Prédiction basée sur la popularité du produit
        $tauxConsommation = $this->estimerTauxConsommation($produit);
        
        // 3. Calcul des jours avant rupture
        $joursAvantRupture = $tauxConsommation > 0 ? 
            floor($stockActuel / $tauxConsommation) : 999;
        
        // 4. Date de rupture prévue
        $dateRupture = (new \DateTime())->modify("+{$joursAvantRupture} days");
        
        // 5. Niveau d'alerte
        $niveauAlerte = $this->determinerNiveauAlerte($stockActuel, $joursAvantRupture);
        
        // 6. Score de confiance (basé sur la cohérence des données)
        $scoreConfiance = $this->calculerScoreConfiance($produit, $tendance);
        
        return [
            'produit_id' => $produit->getId(),
            'produit_nom' => $produit->getNomProduit(),
            'stock_actuel' => $stockActuel,
            'prix_unitaire' => $produit->getPrix(),
            'niveau_alerte' => $niveauAlerte,
            'icone_alerte' => $this->getIconeAlerte($niveauAlerte),
            'couleur_alerte' => $this->getCouleurAlerte($niveauAlerte),
            'message_alerte' => $this->genererMessageAlerte($niveauAlerte, $joursAvantRupture, $stockActuel),
            'jours_avant_rupture' => $joursAvantRupture,
            'date_rupture_predite' => $dateRupture,
            'taux_consommation_estime' => round($tauxConsommation, 2),
            'score_confiance' => $scoreConfiance,
            'tendance_stock' => $tendance,
            'recommandation_urgence' => $this->genererRecommandationUrgence($niveauAlerte),
            'analyse_detaille' => $this->genererAnalyseDetaillee($produit, $stockActuel, $tauxConsommation),
        ];
    }

    /**
     * Analyse tous les produits d'un vendeur
     */
    public function analyserTousProduitsVendeur($vendeur): array
    {
        $produits = $vendeur->getProduits();
        $analyses = [];
        $alertesUrgentes = [];
        $statistiques = [
            'total_produits' => 0,
            'alertes_urgentes' => 0,
            'alertes_moyennes' => 0,
            'alertes_faibles' => 0,
            'stock_total_valeur' => 0,
            'risque_total' => 0,
        ];

        foreach ($produits as $produit) {
            $analyse = $this->analyserProduit($produit);
            $analyses[] = $analyse;
            
            $statistiques['total_produits']++;
            $statistiques['stock_total_valeur'] += $produit->getPrix() * $produit->getQuantiteStock();
            
            switch ($analyse['niveau_alerte']) {
                case 'urgent':
                    $statistiques['alertes_urgentes']++;
                    $alertesUrgentes[] = $analyse;
                    break;
                case 'moyen':
                    $statistiques['alertes_moyennes']++;
                    break;
                case 'faible':
                    $statistiques['alertes_faibles']++;
                    break;
            }
            
            // Calcul du risque (0-100)
            $statistiques['risque_total'] += $this->calculerScoreRisque($analyse);
        }

        if ($statistiques['total_produits'] > 0) {
            $statistiques['risque_total'] = round($statistiques['risque_total'] / $statistiques['total_produits'], 2);
            $statistiques['stock_moyen_valeur'] = round($statistiques['stock_total_valeur'] / $statistiques['total_produits'], 2);
        }

        // Envoyer notification si alertes urgentes
        if (!empty($alertesUrgentes) && $vendeur->getEmail()) {
            $this->envoyerNotificationAlertesUrgentes($vendeur, $alertesUrgentes);
        }

        return [
            'analyses' => $analyses,
            'statistiques' => $statistiques,
            'alertes_urgentes' => $alertesUrgentes,
        ];
    }

    /**
     * Recommande une quantité à commander
     */
    public function recommanderReapprovisionnement(Produit $produit): array
    {
        $analyse = $this->analyserProduit($produit);
        $tauxConsommation = $analyse['taux_consommation_estime'];
        
        // Formule simplifiée : couvrir 30 jours + sécurité
        $joursCouvertureSouhaitee = 30;
        $stockSecurite = max($tauxConsommation * 7, 10); // 1 semaine ou min 10 unités
        
        $quantiteRecommandee = ceil(
            ($tauxConsommation * $joursCouvertureSouhaitee) + $stockSecurite
        );

        // Ajuster selon le point de recyclage
        if ($point = $produit->getPointRecyclage()) {
            $capaciteDisponible = $point->getCapaciteMax() * 0.4; // Utiliser max 40% de capacité
            $quantiteRecommandee = min($quantiteRecommandee, $capaciteDisponible);
        }

        // Calcul des coûts
        $coutStockageUnitaire = $produit->getPrix() * 0.15 / 365; // 15% par an
        $coutCommandeFixe = 25; // Coût administratif par commande
        
        $coutTotalStockage = $quantiteRecommandee * $coutStockageUnitaire * 30;
        $economiePotentielle = max(0, $coutCommandeFixe - $coutTotalStockage);

        return [
            'quantite_recommandee' => (int) $quantiteRecommandee,
            'stock_securite' => (int) $stockSecurite,
            'jours_couverture' => $joursCouvertureSouhaitee,
            'delai_reappro' => self::DELAI_LIVRAISON,
            'date_commande_optimale' => $this->calculerDateCommandeOptimale($analyse),
            'investissement_estime' => round($quantiteRecommandee * $produit->getPrix(), 2),
            'economie_potentielle' => round($economiePotentielle, 2),
            'ratio_stock_securite' => round(($stockSecurite / $quantiteRecommandee) * 100, 1),
            'conseils' => $this->genererConseilsReappro($produit, $quantiteRecommandee),
        ];
    }

    /**
     * Génère un rapport complet pour le vendeur
     */
    public function genererRapportStock($vendeur): array
    {
        $analyseComplete = $this->analyserTousProduitsVendeur($vendeur);
        
        $produitsParCategorie = [
            'urgent' => [],
            'moyen' => [],
            'faible' => [],
            'bon' => [],
        ];

        foreach ($analyseComplete['analyses'] as $analyse) {
            $produitsParCategorie[$analyse['niveau_alerte']][] = $analyse;
        }

        // Calculer les priorités d'action
        $priorites = $this->calculerPrioritesAction($produitsParCategorie);
        
        // Estimation des coûts de réapprovisionnement
        $coutTotalReappro = 0;
        foreach ($produitsParCategorie['urgent'] as $produitUrgent) {
            $produitEntity = $this->entityManager->getRepository(Produit::class)
                ->find($produitUrgent['produit_id']);
            $recommandation = $this->recommanderReapprovisionnement($produitEntity);
            $coutTotalReappro += $recommandation['investissement_estime'];
        }

        return [
            'date_generation' => new \DateTime(),
            'vendeur' => $vendeur->getNom(),
            'statistiques' => $analyseComplete['statistiques'],
            'produits_par_categorie' => $produitsParCategorie,
            'priorites_action' => $priorites,
            'estimation_couts' => [
                'reappro_urgent' => round($coutTotalReappro, 2),
                'economie_potentielle' => round($coutTotalReappro * 0.15, 2), // 15% d'économie estimée
                'retour_investissement' => '2-3 mois',
            ],
            'recommandations_strategiques' => $this->genererRecommandationsStrategiques($analyseComplete['statistiques']),
            'indicateurs_performance' => $this->calculerIndicateursPerformance($analyseComplete),
        ];
    }

    // ============= MÉTHODES PRIVÉES =============

    private function analyserTendanceStock(Produit $produit): string
    {
        // Analyse basée sur la date d'ajout et modifications
        $dateAjout = $produit->getDateAjout();
        $aujourdhui = new \DateTime();
        $differenceJours = $aujourdhui->diff($dateAjout)->days;

        if ($differenceJours < 7) {
            return 'nouveau';
        } elseif ($differenceJours < 30) {
            return 'recent';
        } else {
            // Vérifier si le stock a beaucoup baissé récemment
            $stockInitial = 100; // Valeur par défaut
            $pourcentageStock = ($produit->getQuantiteStock() / $stockInitial) * 100;
            
            if ($pourcentageStock < 20) {
                return 'baisse_rapide';
            } elseif ($pourcentageStock < 50) {
                return 'baisse_moderee';
            } else {
                return 'stable';
            }
        }
    }

    private function estimerTauxConsommation(Produit $produit): float
    {
        // Estimation basée sur plusieurs facteurs
        $facteurs = [];
        
        // 1. Facteur prix (produits chers se vendent moins vite)
        $facteurPrix = max(0.1, 1 - ($produit->getPrix() / 1000));
        $facteurs[] = $facteurPrix;
        
        // 2. Facteur état (produits disponibles se vendent plus)
        $facteurEtat = match($produit->getEtatProduit()) {
            'Disponible' => 1.5,
            'Bientôt disponible' => 0.5,
            'En rupture' => 0.1,
            default => 1.0,
        };
        $facteurs[] = $facteurEtat;
        
        // 3. Facteur description (produits bien décrits se vendent mieux)
        $longueurDescription = strlen($produit->getDescription());
        $facteurDescription = min(2.0, $longueurDescription / 100);
        $facteurs[] = $facteurDescription;
        
        // 4. Facteur point de recyclage (produits associés à un point se vendent mieux)
        $facteurPoint = $produit->getPointRecyclage() ? 1.2 : 1.0;
        $facteurs[] = $facteurPoint;
        
        // 5. Facteur saison (estimation simple)
        $mois = (int) date('m');
        $facteurSaison = in_array($mois, [12, 1, 6, 7]) ? 1.3 : 1.0; // Noël et été
        $facteurs[] = $facteurSaison;
        
        // Moyenne des facteurs * base de consommation
        $moyenneFacteurs = array_sum($facteurs) / count($facteurs);
        $baseConsommation = 2.0; // 2 unités par jour en moyenne
        
        return $baseConsommation * $moyenneFacteurs;
    }

    private function evaluerRisqueImmediat(int $stock): string
    {
        if ($stock <= 0) return 'rupture';
        if ($stock <= self::SEUIL_URGENT) return 'tres_eleve';
        if ($stock <= self::SEUIL_MOYEN) return 'eleve';
        if ($stock <= self::SEUIL_FAIBLE) return 'modere';
        return 'faible';
    }

    private function determinerNiveauAlerte(int $stock, int $joursAvantRupture): string
    {
        if ($stock <= self::SEUIL_URGENT || $joursAvantRupture <= 3) {
            return 'urgent';
        }
        
        if ($stock <= self::SEUIL_MOYEN || $joursAvantRupture <= 7) {
            return 'moyen';
        }
        
        if ($stock <= self::SEUIL_FAIBLE || $joursAvantRupture <= 14) {
            return 'faible';
        }
        
        return 'bon';
    }

    private function genererMessageAlerte(string $niveau, int $jours, int $stock): string
    {
        return match($niveau) {
            'urgent' => "🚨 CRITIQUE - Stock: $stock unités - Rupture dans $jours jours",
            'moyen' => "⚠️ ATTENTION - Stock: $stock unités - Rupture dans $jours jours",
            'faible' => "📉 SURVEILLANCE - Stock: $stock unités - Rupture dans $jours jours",
            default => "✅ OK - Stock: $stock unités - Situation stable"
        };
    }

    private function getIconeAlerte(string $niveau): string
    {
        return match($niveau) {
            'urgent' => '🚨',
            'moyen' => '⚠️',
            'faible' => '📉',
            default => '✅'
        };
    }

    private function getCouleurAlerte(string $niveau): string
    {
        return match($niveau) {
            'urgent' => 'danger',
            'moyen' => 'warning',
            'faible' => 'info',
            default => 'success'
        };
    }

    private function genererRecommandationUrgence(string $niveau): string
    {
        return match($niveau) {
            'urgent' => 'Commander immédiatement',
            'moyen' => 'Planifier une commande cette semaine',
            'faible' => 'Surveiller et commander dans 2 semaines',
            default => 'Maintenir le niveau actuel'
        };
    }

    private function calculerScoreConfiance(Produit $produit, string $tendance): int
    {
        $score = 70; // Score de base
        
        // Ajouter points pour données complètes
        if (!empty($produit->getDescription())) $score += 10;
        if ($produit->getPointRecyclage()) $score += 10;
        if ($produit->getPhoto()) $score += 5;
        
        // Ajuster selon tendance
        if ($tendance === 'stable') $score += 15;
        if ($tendance === 'baisse_rapide') $score -= 10;
        
        return min(max($score, 30), 95); // Limiter entre 30% et 95%
    }

    private function calculerScoreRisque(array $analyse): float
    {
        $score = 0;
        
        switch ($analyse['niveau_alerte']) {
            case 'urgent': $score = 90; break;
            case 'moyen': $score = 60; break;
            case 'faible': $score = 30; break;
            default: $score = 10;
        }
        
        // Ajuster selon la confiance
        $ajustementConfiance = (100 - $analyse['score_confiance']) / 2;
        
        return min($score + $ajustementConfiance, 100);
    }

    private function calculerDateCommandeOptimale(array $analyse): \DateTime
    {
        $joursAvantRupture = $analyse['jours_avant_rupture'];
        
        // Commander quand il reste 1.5x le délai de livraison
        $joursOptimum = $joursAvantRupture - (self::DELAI_LIVRAISON * 1.5);
        
        if ($joursOptimum < 0) {
            $joursOptimum = 0; // Commander aujourd'hui
        }
        
        return (new \DateTime())->modify("+{$joursOptimum} days");
    }

    private function genererConseilsReappro(Produit $produit, int $quantite): array
    {
        $conseils = [];
        
        if ($quantite > 100) {
            $conseils[] = "Considérer une livraison échelonnée pour réduire les coûts de stockage";
        }
        
        if ($produit->getPrix() > 50) {
            $conseils[] = "Négocier des remises pour commandes volumineuses";
        }
        
        if ($point = $produit->getPointRecyclage()) {
            $conseils[] = "Coordonner avec le point {$point->getNomPoint()} pour la logistique";
        }
        
        $conseils[] = "Vérifier les dates de péremption si applicable";
        $conseils[] = "Étiqueter clairement le nouveau stock";
        
        return $conseils;
    }

    private function calculerPrioritesAction(array $produitsParCategorie): array
    {
        $priorites = [];
        
        // Priorité 1 : Produits urgents
        foreach ($produitsParCategorie['urgent'] as $produit) {
            $priorites[] = [
                'produit' => $produit['produit_nom'],
                'action' => 'COMMANDER IMMÉDIATEMENT',
                'delai' => '24h',
                'impact' => 'Élevé',
            ];
        }
        
        // Priorité 2 : Regrouper commandes
        if (count($produitsParCategorie['moyen']) >= 3) {
            $nomsProduits = array_column($produitsParCategorie['moyen'], 'produit_nom');
            $priorites[] = [
                'produit' => implode(', ', array_slice($nomsProduits, 0, 3)) . '...',
                'action' => 'REGROUPER COMMANDE',
                'delai' => '7 jours',
                'impact' => 'Moyen',
            ];
        }
        
        // Priorité 3 : Analyser tendances
        $priorites[] = [
            'produit' => 'Tous les produits',
            'action' => 'ANALYSER TENDANCES MENSUELLES',
            'delai' => '30 jours',
            'impact' => 'Prévention',
        ];
        
        return $priorites;
    }

    private function genererRecommandationsStrategiques(array $statistiques): array
    {
        $recommandations = [];
        
        if ($statistiques['alertes_urgentes'] > 0) {
            $recommandations[] = [
                'type' => 'urgence',
                'titre' => 'Créer un fonds d\'urgence pour réapprovisionnement',
                'description' => 'Allouer ' . ($statistiques['alertes_urgentes'] * 500) . '€ pour couvrir les réapprovisionnements urgents',
            ];
        }
        
        if ($statistiques['risque_total'] > 50) {
            $recommandations[] = [
                'type' => 'optimisation',
                'titre' => 'Optimiser les niveaux de stock',
                'description' => 'Augmenter le stock de sécurité de 20% sur les produits à risque',
            ];
        }
        
        if ($statistiques['stock_moyen_valeur'] > 1000) {
            $recommandations[] = [
                'type' => 'financier',
                'titre' => 'Négocier des délais de paiement',
                'description' => 'Demander 60 jours de délai de paiement aux fournisseurs',
            ];
        }
        
        $recommandations[] = [
            'type' => 'general',
            'titre' => 'Automatiser les commandes',
            'description' => 'Mettre en place un système de réapprovisionnement automatique pour les produits stables',
        ];
        
        return $recommandations;
    }

    private function calculerIndicateursPerformance(array $analyseComplete): array
    {
        $stat = $analyseComplete['statistiques'];
        
        return [
            'taux_service' => round(100 - ($stat['alertes_urgentes'] / max($stat['total_produits'], 1) * 100), 1),
            'rotation_stock' => round($stat['stock_total_valeur'] / max($stat['total_produits'], 1), 2),
            'efficacite_gestion' => round(100 - min($stat['risque_total'], 100), 1),
            'niveau_securite' => round((100 - ($stat['alertes_urgentes'] * 10 + $stat['alertes_moyennes'] * 5)), 1),
        ];
    }

    private function genererAnalyseDetaillee(Produit $produit, int $stock, float $taux): array
    {
        return [
            'points_forts' => [
                $stock > 0 ? 'Stock disponible' : 'À réapprovisionner',
                $produit->getEtatProduit() === 'Disponible' ? 'Statut disponible' : 'Statut à vérifier',
                $produit->getPointRecyclage() ? 'Point de recyclage associé' : 'Point de recyclage à associer',
            ],
            'points_faibles' => [
                $stock < 10 ? 'Stock très faible' : 'Stock suffisant',
                $taux < 1 ? 'Faible taux de rotation estimé' : 'Taux de rotation correct',
            ],
            'opportunites' => [
                'Augmenter le stock pour éviter les ruptures',
                'Promouvoir le produit si éco-responsable',
                'Négocier avec le point de recyclage',
            ],
            'menaces' => [
                'Rupture de stock imminente si non traité',
                'Perte de clients insatisfaits',
                'Coûts de réapprovisionnement urgents',
            ],
        ];
    }

    private function envoyerNotificationAlertesUrgentes($vendeur, array $alertes): void
    {
        if (!$vendeur->getEmail()) {
            return;
        }

        $listeAlertes = '';
        foreach ($alertes as $alerte) {
            $listeAlertes .= "<li>{$alerte['produit_nom']} - {$alerte['message_alerte']}</li>";
        }

        $email = (new Email())
            ->from('alertes@ecomarket.com')
            ->to($vendeur->getEmail())
            ->subject('🚨 ' . count($alertes) . ' ALERTES STOCK URGENTES - EcoMarket')
            ->html("
                <h2>Alertes de Stock Urgentes</h2>
                <p>Bonjour {$vendeur->getNom()},</p>
                <p>Vous avez <strong>" . count($alertes) . " produits</strong> nécessitant une attention immédiate :</p>
                <ul>{$listeAlertes}</ul>
                <p>Connectez-vous à votre espace vendeur pour gérer ces alertes :</p>
                <p><a href='https://ecomarket.com/vendeur/alertes'>Gérer mes alertes</a></p>
                <p>Cordialement,<br>L'équipe EcoMarket</p>
            ");

        $this->mailer->send($email);
    }

// Ajoutez cette nouvelle méthode dans AlerteStockService
public function analyserProduitAvecSMS(Produit $produit): array
{
    // 1. Utilisez votre fonction existante
    $analyse = $this->analyserProduit($produit);
    
    // 2. Ajoutez les SMS si niveau urgent
    if ($analyse['niveau_alerte'] === 'urgent') {
        $smsResultat = $this->smsService->envoyerAlerteProduitCritique($produit);
        
        $analyse['sms_envoye'] = true;
        $analyse['sms_resultat'] = $smsResultat;
        $analyse['notification_type'] = 'sms_urgence';
        
        // Ajouter un log spécial
        $this->logger->warning('SMS d\'urgence envoyé', [
            'produit' => $produit->getId(),
            'stock' => $produit->getQuantiteStock(),
            'sms_destinataires' => $smsResultat['destinataires'] ?? []
        ]);
    }
    
    return $analyse;
}
}