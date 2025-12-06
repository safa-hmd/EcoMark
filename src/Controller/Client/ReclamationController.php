<?php

namespace App\Controller\Client;

use App\Service\BadWordAIService;
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
public function index(Request $request, PaginatorInterface $paginator, ReclamationRepository $repo)
{
    $user = $this->getUser(); // client connecté

    $nbReclamations = $repo->count(['client' => $user]); // seulement ses réclamations

    $query = $repo->createQueryBuilder('r')
                  ->where('r.client = :user')
                  ->setParameter('user', $user)
                  ->orderBy('r.dateCreation', 'DESC')
                  ->getQuery();

    $reclamations = $paginator->paginate(
        $query, 
        $request->query->getInt('page', 1), 
        3
    );

    return $this->render('Client/reclamation/afficherReclamation.html.twig', [
        'reclamations' => $reclamations,
        'nbReclamations' => $nbReclamations
    ]);
}




    #[Route('/new', name: 'ajoutRec', methods: ['GET','POST'])]
public function AjoutRec(Request $request, EntityManagerInterface $em): Response
{
    $reclamation = new Reclamation();

    $form = $this->createForm(ReclamationType::class, $reclamation);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {

        // 🔹 Vérification reCAPTCHA
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
                $form->addError(new \Symfony\Component\Form\FormError('reCAPTCHA invalide, veuillez réessayer.'));
            }
        }

        // ✅ Si le formulaire est valide
        if ($form->isValid()) {

            // 🔹 Mettre le client connecté automatiquement
            $reclamation->setClient($this->getUser());

            $em->persist($reclamation);
            $em->flush();

            return $this->redirectToRoute('reclamation_Afficher');
        }
    }

    return $this->render('Client/reclamation/ajouterReclamation.html.twig', [
        'form' => $form->createView(),
        'update' => false,
        'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'],
    ]);
}



#[Route('/modifierRec/{ii}', name: 'modifierRec')]
public function modifierRec(Request $request, ReclamationRepository $repo, $ii, EntityManagerInterface $em): Response
{
    $user = $this->getUser(); // client connecté

    // 🔹 Récupère uniquement la réclamation du client connecté
    $reclamation = $repo->findOneBy([
        'id' => $ii,
        'client' => $user
    ]);

    if (!$reclamation) {
        throw $this->createNotFoundException('Réclamation introuvable ou accès non autorisé.');
    }

    $form = $this->createForm(ReclamationType::class, $reclamation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();

        return $this->redirectToRoute('Afficher'); // ou 'reclamation_Afficher' selon ton nom
    }

    return $this->render('Client/reclamation/modifierReclamation.html.twig', [
        'form' => $form->createView(),
        'update' => true,
        'reclamation' => $reclamation,
    ]);
}


#[Route('/Details/{ii}', name: 'det')]
public function Details(ReclamationRepository $repo, $ii): Response
{
    $user = $this->getUser(); // client connecté

    // 🔹 Récupère uniquement la réclamation du client connecté
    $reclamation = $repo->findOneBy([
        'id' => $ii,
        'client' => $user
    ]);

    if (!$reclamation) {
        throw $this->createNotFoundException('Réclamation introuvable ou accès non autorisé.');
    }

    return $this->render('Client/reclamation/details.html.twig', [
        'reclamation' => $reclamation,
    ]);
}




#[Route('/delete/{ii}', name: 'supprimerReclamation')]
public function delete(ReclamationRepository $repo, $ii, EntityManagerInterface $em): Response
{
    $user = $this->getUser(); // client connecté

    // 🔹 Récupère uniquement la réclamation du client connecté
    $reclamation = $repo->findOneBy([
        'id' => $ii,
        'client' => $user
    ]);

    if (!$reclamation) {
        throw $this->createNotFoundException('Réclamation introuvable ou accès non autorisé.');
    }

    $em->remove($reclamation);
    $em->flush();

    return $this->redirectToRoute('Afficher'); // ou 'reclamation_Afficher' selon ton nom
}


}
