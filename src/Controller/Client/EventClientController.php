<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Form\ParticipationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use App\Entity\Participation;
use App\Repository\ParticipationRepository;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\WeatherService;
use App\Service\QrCodeService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Bundle\SecurityBundle\Security;

#[Route('/event-client')]
final class EventClientController extends AbstractController
{
    #[Route('/event/client', name: 'app_event_client')]
    public function index(): Response
    {
        return $this->render('event_client/index.html.twig', [
            'controller_name' => 'EventClientController',
        ]);
    }
    //affichage de des evenements bech ne5tar we7id nparticipi fih 
    //hani j ai ajouter el qr code hnee
    
    #[Route('/client/evenements', name: 'client_affiche_evenements')]
public function afficherEvenements(
    EvenementRepository $repo,
    UrlGeneratorInterface $urlGenerator,
    QRCodeService $qrService) {
    $evenements = $repo->findAll();
    $qrCodes = [];

    foreach ($evenements as $ev) {
        $participationUrl = $urlGenerator->generate(
            'client_participer',
            ['ii' => $ev->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Utilisation du service pour générer le QR
        $qrCodes[$ev->getId()] = $qrService->generateQrCode($participationUrl, 60);

    }
    return $this->render('Client/event_client/afficheEvenements.html.twig', [
        'evenements' => $evenements,
        'qrCodes' => $qrCodes,
    ]);
}
    
    
    
    
    #[Route('/client/participer/{ii}', name:'client_participer')]
    public function participer(int $ii,Request $request,EntityManagerInterface $em,EvenementRepository $eventRepo,ParticipationRepository $participationRepo ,Security $security): Response {
        $user = $security->getUser(); 

        // Récupérit  l'événement
        $evenement = $eventRepo->find($ii);
        if (!$evenement) {
        throw $this->createNotFoundException("Événement introuvable");
        }

        // zedt ya3mil verification de la participation
        $existingParticipation = $participationRepo->findOneBy([
        'user' => $user,
        'evenement' => $evenement
        ]);

        if ($existingParticipation) {
        $this->addFlash('warning', 'Vous avez déjà participé à cet événement.');
        return $this->redirectToRoute('client_affiche_evenements');
    }

        // Création participation
        $participation = new Participation();
        $participation->setStatut(Participation::STATUT_EN_ATTENTE);
        $participation->setDateParticipation(new \DateTime());
        $participation->setEvenement($evenement);
        $participation->setUser($user);

         $em->persist($participation);
         $em->flush();


        $this->addFlash('success', 'Votre demande de participation a été envoyée !');

        return $this->redirectToRoute('affiche_client');

    }
    //yhezzek lil page hethi mta3 liste des participation
        #[Route('/client/affiche', name: 'affiche_client')]
    public function Affiche(ParticipationRepository $repo)
    {
        $user = $this->getUser();
        $participations = $repo->findBy(['user' => $user]);

        return $this->render('Client/event_client/affiche.html.twig', [
            'participations' => $participations,
        ]);
    }

//annuler la participation
    #[Route('/client/participation/cancel/{id}', name: 'client_cancel_participation')]
public function cancel($id, ParticipationRepository $repo, EntityManagerInterface $em)
{
    $p = $repo->find($id);

    if (!$p) {
        throw $this->createNotFoundException("Participation non trouvée");
    }
    

    
    $em->remove($p);

    $em->flush();

    return $this->redirectToRoute('affiche_client');
}

//API meteo 
#[Route('/client/meteo/{id}', name: 'client_meteo_event')]
public function meteo(
    int $id,
    EvenementRepository $repo,
    WeatherService $weather
): Response {
    $event = $repo->find($id);

    if (!$event) {
        throw $this->createNotFoundException("Événement introuvable");
    }

    // Récupération de la météo pour le lieu
    $data = $weather->getWeather($event->getLieu());

    return $this->render('Client/event_client/meteo_event.html.twig', [
        'event' => $event,
        'weather' => $data
    ]);
}

}
