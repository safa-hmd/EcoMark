<?php

namespace App\Controller\Vendeur;

use App\Service\SmsAlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/vendeur/sms')]
class SmsAlerteController extends AbstractController
{
    #[Route('/alertes', name: 'app_vendeur_sms_alertes')] // FIXED: Changed from 'app_vendeur_test_sms'
    public function alertes(SmsAlertService $smsService): Response
    {
        $rapport = $smsService->scannerEtAlerterStocksCritiques();
        
        // Get the logged-in vendeur (adjust this based on your authentication system)
        $user = $this->getUser();
        $vendeur = null;
        
        // If your user is a Vendeur entity or has a getVendeur() method
        if ($user && method_exists($user, 'getVendeur')) {
            $vendeur = $user->getVendeur();
        } elseif ($user && $user instanceof \App\Entity\Vendeur) {
            $vendeur = $user;
        }
        
        return $this->render('vendeur/produit/alertes/sms_rapport.html.twig', [ // Changed to correct path
    'rapport' => $rapport,
    'service_actif' => $smsService->isActive(),
    'date_now' => new \DateTime(),
    'vendeur' => $vendeur
]);
    }
    
    #[Route('/test', name: 'app_vendeur_test_sms')] // This is correct
    public function test(SmsAlertService $smsService): Response
    {
        $resultatTest = $smsService->testerService();
        
        $this->addFlash(
            $resultatTest['service_actif'] ? 'success' : 'warning',
            $resultatTest['service_actif'] ? 
                'Test SMS envoyé avec succès!' : 
                'Mode simulation - SMS non envoyés réellement'
        );
        
        // Get the logged-in vendeur
        $user = $this->getUser();
        $vendeur = null;
        
        if ($user && method_exists($user, 'getVendeur')) {
            $vendeur = $user->getVendeur();
        } elseif ($user && $user instanceof \App\Entity\Vendeur) {
            $vendeur = $user;
        }
        
        return $this->render('vendeur/sms/test.html.twig', [
            'resultat' => $resultatTest,
            'vendeur' => $vendeur
        ]);
    }
    
    #[Route('/config', name: 'app_vendeur_sms_config')]
    public function config(SmsAlertService $smsService): Response
    {
        // Get the logged-in vendeur
        $user = $this->getUser();
        $vendeur = null;
        
        if ($user && method_exists($user, 'getVendeur')) {
            $vendeur = $user->getVendeur();
        } elseif ($user && $user instanceof \App\Entity\Vendeur) {
            $vendeur = $user;
        }
        
        return $this->render('vendeur/sms/config.html.twig', [
            'config' => $smsService->getConfiguration(),
            'twilio_configure' => !empty($_ENV['TWILIO_SID']),
            'vendeur' => $vendeur
        ]);
    }
}