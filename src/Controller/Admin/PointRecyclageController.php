<?php

namespace App\Controller\Admin;

use App\Entity\PointRecyclage;
use App\Form\PointRecyclageType;
use App\Repository\PointRecyclageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/point-recyclage')]
class PointRecyclageController extends AbstractController
{
    #[Route('/', name: 'app_admin_point_recyclage_index', methods: ['GET'])]
    public function index(PointRecyclageRepository $pointRecyclageRepository): Response
    {
        $admin = $this->getUser();
        
        return $this->render('Admin/point_recyclage/index.html.twig', [
            'point_recyclages' => $pointRecyclageRepository->findAll(),
            'admin' => $admin,
        ]);
    }

    #[Route('/new', name: 'app_admin_point_recyclage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $admin = $this->getUser();
        $pointRecyclage = new PointRecyclage();
        $form = $this->createForm(PointRecyclageType::class, $pointRecyclage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pointRecyclage);
            $entityManager->flush();

            $this->addFlash('success', 'Point de recyclage créé avec succès!');
            return $this->redirectToRoute('app_admin_point_recyclage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/point_recyclage/new.html.twig', [
            'point_recyclage' => $pointRecyclage,
            'form' => $form->createView(),
            'admin' => $admin,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_point_recyclage_show', methods: ['GET'])]
    public function show(PointRecyclage $pointRecyclage): Response
    {
        $admin = $this->getUser();
        
        return $this->render('Admin/point_recyclage/show.html.twig', [
            'point_recyclage' => $pointRecyclage,
            'admin' => $admin,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_point_recyclage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PointRecyclage $pointRecyclage, EntityManagerInterface $entityManager): Response
    {
        $admin = $this->getUser();
        $form = $this->createForm(PointRecyclageType::class, $pointRecyclage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Point de recyclage modifié avec succès!');
            return $this->redirectToRoute('app_admin_point_recyclage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Admin/point_recyclage/edit.html.twig', [
            'point_recyclage' => $pointRecyclage,
            'form' => $form->createView(),
            'admin' => $admin,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_point_recyclage_delete', methods: ['POST'])]
    public function delete(Request $request, PointRecyclage $pointRecyclage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pointRecyclage->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pointRecyclage);
            $entityManager->flush();
            
            $this->addFlash('success', 'Point de recyclage supprimé avec succès!');
        }

        return $this->redirectToRoute('app_admin_point_recyclage_index', [], Response::HTTP_SEE_OTHER);
    }
}