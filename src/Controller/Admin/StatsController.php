<?php

namespace App\Controller\Admin;

use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/stats')]
class StatsController extends AbstractController
{
    #[Route('/', name: 'admin_stats')]
    public function index(CommandeRepository $repo): Response
    {
        $stats = $repo->countByMonth();
        $revenues = $repo->revenueByMonth();
        $paiements = $repo->countByMethodePaiement();

        return $this->render('admin/stats/index.html.twig', [
            'stats'     => $stats,
            'revenues'  => $revenues,
            'paiements' => $paiements,
        ]);
    }
}
