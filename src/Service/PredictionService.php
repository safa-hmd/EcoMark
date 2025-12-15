<?php

namespace App\Service;

use App\Entity\Produit;
use App\Entity\Achat; // Si vous avez une entité Achat
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PredictionService
{
    private EntityManagerInterface $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    public function predireDemande(Produit $produit, int $joursHistorique = 30): array
    {
        // 1. Récupérer l'historique des ventes
        $historique = $this->getHistoriqueVentes($produit, $joursHistorique);
        
        // 2. Analyser la moyenne journalière
        $moyenneJournaliere = $this->calculerMoyenneJournaliere($historique);
        
        // 3. Détecter la saisonnalité
        $saisonnalite = $this->analyserSaisonnalite($historique);
        
        // 4. Calculer la tendance
        $tendance = $this->calculerTendance($historique);
        
        // 5. Prendre en compte les événements spéciaux
        $facteurEvenement = $this->getFacteurEvenement();
        
        // 6. Calculer la prédiction
        $prediction = $this->calculerPrediction(
            $moyenneJournaliere,
            $saisonnalite,
            $tendance,
            $facteurEvenement
        );
        
        return [
            'demande_journaliere_moyenne' => $moyenneJournaliere,
            'demande_journaliere_min' => $prediction['min'],
            'demande_journaliere_max' => $prediction['max'],
            'facteur_saison' => $saisonnalite,
            'tendance_mensuelle_pourcent' => $tendance,
            'facteur_evenement' => $facteurEvenement,
            'prediction_7jours' => $prediction['7jours'],
            'prediction_30jours' => $prediction['30jours'],
            'intervalle_confiance_95' => $prediction['intervalle'],
            'score_confiance' => $prediction['score'],
        ];
    }
    
    private function getHistoriqueVentes(Produit $produit, int $jours): array
    {
        // Si vous avez une entité Achat/Commande
        $dateDebut = new \DateTime("-$jours days");
        
        // Exemple de requête - à adapter à votre structure
        $query = $this->em->createQuery(
            "SELECT COUNT(a) as quantite, DATE(a.dateAchat) as date
             FROM App\Entity\Achat a
             WHERE a.produit = :produit
             AND a.dateAchat >= :dateDebut
             GROUP BY DATE(a.dateAchat)
             ORDER BY date DESC"
        );
        
        $query->setParameters([
            'produit' => $produit,
            'dateDebut' => $dateDebut
        ]);
        
        return $query->getResult();
    }
    
    private function analyserSaisonnalite(array $historique): array
    {
        $analyse = [
            'weekend_vs_semaine' => 1.0,
            'jours_semaine' => [
                'lundi' => 1.0, 'mardi' => 1.0, 'mercredi' => 1.0,
                'jeudi' => 1.0, 'vendredi' => 1.0, 
                'samedi' => 1.3, // +30% le samedi
                'dimanche' => 1.2  // +20% le dimanche
            ],
            'mois' => [
                'janvier' => 0.8, 'fevrier' => 0.9, 'mars' => 1.0,
                'avril' => 1.1, 'mai' => 1.2, 'juin' => 1.3,
                'juillet' => 1.4, 'aout' => 1.5, 'septembre' => 1.2,
                'octobre' => 1.1, 'novembre' => 0.9, 'decembre' => 1.4
            ]
        ];
        
        return $analyse;
    }
    
    public function estimerRupture(Produit $produit): array
    {
        $stockActuel = $produit->getQuantiteStock();
        $prediction = $this->predireDemande($produit);
        $demandeMoyenne = $prediction['demande_journaliere_moyenne'];
        
        if ($demandeMoyenne <= 0) {
            return [
                'jours_avant_rupture' => 999,
                'date_rupture' => null,
                'risque' => 'faible',
                'message' => 'Demande nulle ou très faible'
            ];
        }
        
        $joursAvantRupture = floor($stockActuel / $demandeMoyenne);
        $dateRupture = (new \DateTime())->modify("+$joursAvantRupture days");
        
        // Niveau de risque
        if ($joursAvantRupture <= 3) {
            $risque = 'critique';
        } elseif ($joursAvantRupture <= 7) {
            $risque = 'eleve';
        } elseif ($joursAvantRupture <= 14) {
            $risque = 'moyen';
        } else {
            $risque = 'faible';
        }
        
        return [
            'jours_avant_rupture' => $joursAvantRupture,
            'date_rupture' => $dateRupture,
            'risque' => $risque,
            'stock_actuel' => $stockActuel,
            'demande_moyenne' => $demandeMoyenne,
            'recommandation' => $this->genererRecommandation($joursAvantRupture, $risque)
        ];
    }
    
    private function genererRecommandation(int $jours, string $risque): string
    {
        switch($risque) {
            case 'critique':
                return "🚨 COMMANDER IMMÉDIATEMENT ! Rupture dans moins de 3 jours.";
            case 'eleve':
                return "⚠️ Commander cette semaine. Rupture prévue dans 4-7 jours.";
            case 'moyen':
                return "📋 Planifier la commande. Rupture dans 8-14 jours.";
            default:
                return "✅ Stock suffisant pour plus de 2 semaines.";
        }
    }
}