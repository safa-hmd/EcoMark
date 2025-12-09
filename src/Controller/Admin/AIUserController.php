<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Service\UserActivityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
//use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/admin/ia-users')]
#[IsGranted('ROLE_ADMIN')]
class AIUserController extends AbstractController
{
    #[Route('/', name: 'admin_ia_users_index')]
    public function index(
        UserRepository $userRepository, 
        UserActivityService $activityService,
        Security $security
    ): Response
    {
        // Récupère l'utilisateur connecté (admin)
        $admin = $security->getUser();
        
        $allUsers = $userRepository->findAll();
        $inactiveUsers = [];
        $activeUsers = [];

        foreach ($allUsers as $user) {
            if ($activityService->isUserInactive($user, 15)) {
                $inactiveUsers[] = $user;
            } else {
                $activeUsers[] = $user;
            }
        }

        return $this->render('admin/GestionUser/ia_users/index.html.twig', [
            'inactiveUsers' => $inactiveUsers,
            'activeUsers' => $activeUsers,
            'inactiveCount' => count($inactiveUsers),
            'activeCount' => count($activeUsers),
            'admin' => $admin, // Ajoute l'admin ici
        ]);
    }

    #[Route('/send-notifications', name: 'admin_ia_send_notifications')]
    public function sendNotifications(
        UserActivityService $activityService,
        Security $security
    ): Response
    {
        $admin = $security->getUser();
        
        // Envoie des emails aux utilisateurs inactifs
        $activityService->checkInactiveUsers(15);
        
        $this->addFlash('success', 'Notifications envoyées aux utilisateurs inactifs !');
        
        return $this->redirectToRoute('admin_ia_users_index', [
            'admin' => $admin,
        ]);
    }
}