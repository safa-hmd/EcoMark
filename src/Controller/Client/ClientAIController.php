<?php

namespace App\Controller\Client;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Service\AIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientAIController extends AbstractController
{
    
    #[Route('/client/assistant-ia', name: 'client_assistant_ia')]
    public function assistantIA(CommandeRepository $commandeRepository): Response
    {
        $user = $this->getUser();

        $commandes = [];
        if ($user) {
            $commandes = $commandeRepository->findBy(
                ['user' => $user],
                ['dateCommande' => 'DESC']
            );
        }

        return $this->render('Client/commande/assistant_ia.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    // ===============================
    //  QUESTION → RÉPONSE IA
    // ===============================
    #[Route('/client/assistant-ia/ask', name: 'client_assistant_ia_ask', methods: ['POST'])]
    public function askQuestion(
        Request $request,
        AIService $aiService,
        CommandeRepository $commandeRepository
    ): JsonResponse {
        $question = trim($request->request->get('question', ''));

        if ($question === '') {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Veuillez poser une question.',
            ]);
        }

        $user = $this->getUser();

        $commandes = [];
        if ($user) {
            $commandes = $commandeRepository->findBy(
                ['user' => $user],
                ['dateCommande' => 'DESC']
            );
        }

        // 🧠 CONTEXTE RÉEL (ID EXACTS)
        $context = $this->buildContext($commandes);

        // 🔥 Appel IA
        $responseText = $aiService->ask($question, $context);

        return new JsonResponse([
            'error'    => false,
            'response' => $responseText,
        ]);
    }

    // ===============================
    //  CONTEXTE IA (CORRECT, SANS DÉCALAGE)
    // ===============================
    private function buildContext(array $commandes): string
    {
        if (empty($commandes)) {
            return "L'utilisateur n'a aucune commande pour le moment.";
        }

        $context  = "Voici les commandes réelles de l'utilisateur.\n";
        $context .= "IMPORTANT : les numéros correspondent aux ID exacts affichés sur la page Mes Commandes.\n\n";

        foreach ($commandes as $commande) {
            $context .= "Commande ID #" . $commande->getId() . " :\n";
            $context .= "- Date : " . $commande->getDateCommande()->format('d/m/Y H:i') . "\n";
            $context .= "- Statut : " . $commande->getStatut() . "\n";
            $context .= "- Montant total : " . number_format($commande->getMontantTotal(), 2, ',', ' ') . " €\n";

            if ($commande->getProduits()) {
                $context .= "- Produits : " . $commande->getProduits() . "\n";
            } else {
                $context .= "- Produits : Aucun\n";
            }

            $context .= "\n";
        }

        return $context;
    }

    // ===============================
    // 🗺️ CARTE DE LIVRAISON (OPTIONNEL)
    // ===============================
    #[Route('/client/commande/{id}/carte-livraison', name: 'client_commande_carte_livraison')]
    public function carteLivraison(Commande $commande): Response
    {
        return $this->render('Client/commande/carte_livraison.html.twig', [
            'commande' => $commande,
        ]);
    }
}
