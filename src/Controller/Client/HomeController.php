<?php
namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;  

use Symfony\Component\HttpFoundation\Response;

#[Route('/')]
class HomeController extends AbstractController
{
    #[Route('/client', name: 'client_home')]
    public function index()
    {
        return $this->render('Client/FO.html.twig');
    }
}
