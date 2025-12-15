<?php

namespace App\Controller\Client;
use App\Service\ReactionReponseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Reponse;

use App\Service\BadWordDetectorService;
use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;


#[Route('/reclamation', name: 'reclamation_')]
final class ReclamationController extends AbstractController
{



    // #[Route('/reclamation', name: 'app_reclamation')]
    // public function index(): Response
    // {
    //     return $this->render('reclamation/index.html.twig', [
    //         'controller_name' => 'ReclamationController',
    //     ]);
    // }

#[Route('/Afficher', name: 'Afficher')]
public function index(Request $request, PaginatorInterface $paginator, ReclamationRepository $repo){
    $user = $this->getUser(); 
    //$nbReclamations = $repo->count(['client' => $user]); 
$query = $repo->findByClientQuery($user);

$reclamations = $paginator->paginate(
    $query,
    $request->query->getInt('page', 1),
    3
);
    return $this->render('Client/reclamation/afficherReclamation.html.twig', [
        'reclamations' => $reclamations,
     ]);
    }




   #[Route('/new', name: 'ajoutRec')]
public function AjoutRec(Request $request, EntityManagerInterface $em,BadWordDetectorService $badWordDetector): Response{
    $reclamation = new Reclamation();
    $form = $this->createForm(ReclamationType::class, $reclamation);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {

        $recaptchaResponse = $request->request->get('g-recaptcha-response');
        if (empty($recaptchaResponse)) {
            $form->addError(new \Symfony\Component\Form\FormError('Veuillez cocher "Je ne suis pas un robot"'));
        } else {
            $secret = $_ENV['GOOGLE_RECAPTCHA_SECRET'];
            $response = file_get_contents(
                "https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$recaptchaResponse}"
            );
            $result = json_decode($response, true);

            if (!$result['success']) {
                $form->addError(new \Symfony\Component\Form\FormError('reCAPTCHA invalide, veuillez réessayer.'));}
        }
        if ($form->isValid()) {
            $reclamation->setClient($this->getUser());
            $objetOriginal = $reclamation->getObjet();
            $descriptionOriginal = $reclamation->getDescription();
            $objetFiltre = $badWordDetector->censorBadWords($objetOriginal);
            $descriptionFiltre = $badWordDetector->censorBadWords($descriptionOriginal);
            $reclamation->setObjet($objetFiltre);
            $reclamation->setDescription($descriptionFiltre);
            $changed = false;
            $changes = [];
            
            if ($objetOriginal !== $objetFiltre) {
                $changed = true;
                $changes[] = "l'objet";
            }
            if ($descriptionOriginal !== $descriptionFiltre) {
                $changed = true;
                $changes[] = "la description";
            }
            
            if ($changed) {
                $message = "Des mots inappropriés ont été remplacés par '***' dans " . implode(' et ', $changes) . ".";
                $this->addFlash('info', $message);
            }

            $em->persist($reclamation);
            $em->flush();
            $this->addFlash('success', 'Votre réclamation a été enregistrée avec succès.');
            return $this->redirectToRoute('reclamation_Afficher');
        }}
    return $this->render('Client/reclamation/ajouterReclamation.html.twig', [
        'form' => $form->createView(),
        'update' => false,
        'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'],
    ]);}



#[Route('/modifierRec/{ii}', name: 'modifierRec')]
public function modifierRec(Request $request, ReclamationRepository $repo, $ii, EntityManagerInterface $em,BadWordDetectorService $badWordDetector): Response
{
    $user = $this->getUser(); 
    $reclamation = $repo->findOneBy([
        'id' => $ii,
        'client' => $user
    ]);

    if (!$reclamation) {
        throw $this->createNotFoundException('Réclamation introuvable ou accès non autorisé.');
    }
    $originalObjet = $reclamation->getObjet();
    $originalDescription = $reclamation->getDescription();

    $form = $this->createForm(ReclamationType::class, $reclamation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        
        $nouvelObjet = $reclamation->getObjet();
        $nouvelleDescription = $reclamation->getDescription();
                $objetFiltre = $badWordDetector->censorBadWords($nouvelObjet);
        $descriptionFiltre = $badWordDetector->censorBadWords($nouvelleDescription);
                $reclamation->setObjet($objetFiltre);
        $reclamation->setDescription($descriptionFiltre);
        
        $objetChanged = ($nouvelObjet !== $objetFiltre);
        $descriptionChanged = ($nouvelleDescription !== $descriptionFiltre);
        
        if ($objetChanged || $descriptionChanged) {
            $changes = [];
            if ($objetChanged) $changes[] = "l'objet";
            if ($descriptionChanged) $changes[] = "la description";
            
            $this->addFlash('info', 
                'Des mots inappropriés ont été remplacés par "***" dans ' . implode(' et ', $changes) . '.'
            );
        }
                $this->addFlash('success', 'Réclamation modifiée avec succès.');

        $em->flush();

        return $this->redirectToRoute('reclamation_Afficher');
    }

    return $this->render('Client/reclamation/modifierReclamation.html.twig', [
        'form' => $form->createView(),
        'update' => true,
        'reclamation' => $reclamation,
    ]);
}


#[Route('/Details/{ii}', name: 'det')]
public function Details(ReclamationRepository $repo, $ii): Response{
    $user = $this->getUser(); 
    $reclamation = $repo->findOneBy([
        'id' => $ii,
        'client' => $user
    ]);
    return $this->render('Client/reclamation/details.html.twig', [
        'reclamation' => $reclamation,
    ]);}




#[Route('/delete/{ii}', name: 'supprimerReclamation')]
public function delete(ReclamationRepository $repo, $ii, EntityManagerInterface $em): Response{
    $user = $this->getUser();

    $reclamation = $repo->findOneBy([
        'id' => $ii,
        'client' => $user
    ]);
    $em->remove($reclamation);
    $em->flush();
    return $this->redirectToRoute('reclamation_Afficher');}


    
#[Route('/search', name: 'reponse_search', methods: ['GET'])]
    public function search(Request $request, ReclamationRepository $repo): Response{
        $search = $request->query->get('q', '');
        $date = $request->query->get('date', '');
        
        $reclamations = $repo->searchByAllAttributes($search, $date);
        
        // Retourne seulement le partial du tableau pour l'AJAX
        return $this->render('Admin/reponse/table_reclamation.html.twig', [
            'reclamations' => $reclamations
        ]);
    }

  #[Route('/reaction/{id}/{type}', name: 'reaction_toggle', methods: ['POST'])]
public function toggle(int $id, string $type, ReactionReponseService $service, EntityManagerInterface $em): JsonResponse{
    try {
        $reponse = $em->getRepository(Reponse::class)->find($id);

        if (!$reponse) {
            return $this->json(['error' => 'Réponse introuvable'], 404);
        }

        // Toggle la réaction (like/dislike)
        $service->toggle($reponse, $type);

        // Compter likes et dislikes
        $likes = $reponse->getReactions()->filter(fn($r) => $r->getType() === 'like')->count();
        $dislikes = $reponse->getReactions()->filter(fn($r) => $r->getType() === 'dislike')->count();

        return $this->json([
            'likes' => $likes,
            'dislikes' => $dislikes,
        ]);
    } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()], 500);
    }
}
}
