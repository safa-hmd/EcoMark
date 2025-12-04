<?php
namespace App\Controller\Vendeur;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;  

class indexController extends AbstractController
{
    #[Route('/vendeur', name: 'vendeur_home')]
    public function index()
    {
        return $this->render('Vendeur/BO.html.twig');
    }
}