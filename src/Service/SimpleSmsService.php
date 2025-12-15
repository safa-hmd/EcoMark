<?php

namespace App\Service;

use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SimpleSmsService
{
    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private ParameterBagInterface $params;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        ParameterBagInterface $params
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->params = $params;
        
        $this->logger->info('SimpleSmsService initialisé');
    }

    /**
     * Scanner tous les produits et envoyer les alertes
     */
    public function scannerEtAlerterStocksCritiques(): array
    {
        $produits = $this->em->getRepository(Produit::class)->findAll();
        
        $rapport = [
            'date_scan' => new \DateTime(),
            'mode' => 'SIMULATION',
            'total_produits' => count($produits),
            'produits_critiques' => [],
            'total_sms_envoyes' => 0
        ];

        foreach ($produits as $produit) {
            if ($produit->getQuantiteStock() <= 3) {
                $resultat = [
                    'produit' => $produit->getNomProduit(),
                    'stock' => $produit->getQuantiteStock(),
                    'sms_envoyes' => 2, // Simulation: 2 vendeurs
                    'destinataires' => ['Vendeur EcoMarket (simulé)', 'Administrateur (simulé)'],
                    'statut' => 'success',
                    'mode' => 'SIMULATION'
                ];
                
                $rapport['produits_critiques'][] = $resultat;
                $rapport['total_sms_envoyes'] += $resultat['sms_envoyes'];
                
                $this->logger->info("SIMULATION SMS: Alerte pour {$produit->getNomProduit()} - Stock: {$produit->getQuantiteStock()}");
            }
        }

        return $rapport;
    }
}