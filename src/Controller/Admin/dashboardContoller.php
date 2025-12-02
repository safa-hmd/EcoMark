<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;  

class dashboardContoller extends AbstractController
{
    #[Route('/admin', name: 'admin_home')]
    public function index()
    {
        return $this->render('Admin/BO.html.twig');
    }
}