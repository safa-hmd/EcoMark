<?php

namespace App\Controller\Vendeur;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/vendeur/produit')]
class ProduitController extends AbstractController
{
    #[Route('/', name: 'app_vendeur_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        // Récupérer l'utilisateur connecté (vendeur)
        $vendeur = $this->getUser();
        
        return $this->render('vendeur/produit/index.html.twig', [
            'produits' => $produitRepository->findAll(),
            'vendeur' => $vendeur,
        ]);
    }

    #[Route('/new', name: 'app_vendeur_produit_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository // AJOUTER CE PARAMÈTRE
    ): Response
    {
        // Récupérer l'utilisateur connecté (vendeur)
        $vendeur = $this->getUser();
        
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // === VÉRIFICATION DU NOM EXISTANT ===
            $nomProduit = $produit->getNomProduit();
            $produitExist = $produitRepository->findOneBy(['nomProduit' => $nomProduit]);
            
            if ($produitExist) {
                // Option 1: Afficher une erreur
                $this->addFlash('error', 'Un produit avec le nom "' . $nomProduit . '" existe déjà!');
                return $this->redirectToRoute('app_vendeur_produit_new', [], Response::HTTP_SEE_OTHER);
                
                // OU Option 2: Proposer de modifier le produit existant
                // return $this->redirectToRoute('app_vendeur_produit_edit', ['id' => $produitExist->getId()], Response::HTTP_SEE_OTHER);
            }
            
            // --- TRAITEMENT DE LA PHOTO ---
            $photoFile = $form->get('photo')->getData();
            
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                
                try {
                    $photoFile->move(
                        $this->getParameter('produits_photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
                
                $produit->setPhoto($newFilename);
            }

            // Définir la date d'ajout automatiquement
            $produit->setDateAjout(new \DateTime());
            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès!');
            return $this->redirectToRoute('app_vendeur_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vendeur/produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
            'vendeur' => $vendeur,
        ]);
    }

    #[Route('/{id}', name: 'app_vendeur_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        // Récupérer l'utilisateur connecté (vendeur)
        $vendeur = $this->getUser();
        
        return $this->render('vendeur/produit/show.html.twig', [
            'produit' => $produit,
            'vendeur' => $vendeur,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vendeur_produit_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Produit $produit, 
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository // AJOUTER CE PARAMÈTRE
    ): Response
    {
        // Récupérer l'utilisateur connecté (vendeur)
        $vendeur = $this->getUser();
        
        // Sauvegarder l'ancien nom pour la vérification
        $ancienNom = $produit->getNomProduit();
        
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // === VÉRIFICATION DU NOM EXISTANT (seulement si le nom a changé) ===
            $nouveauNom = $produit->getNomProduit();
            
            if ($nouveauNom !== $ancienNom) {
                $produitExist = $produitRepository->findOneBy(['nomProduit' => $nouveauNom]);
                
                if ($produitExist) {
                    $this->addFlash('error', 'Un autre produit avec le nom "' . $nouveauNom . '" existe déjà!');
                    return $this->redirectToRoute('app_vendeur_produit_edit', ['id' => $produit->getId()], Response::HTTP_SEE_OTHER);
                }
            }
            
            // --- TRAITEMENT DE LA PHOTO ---
            $photoFile = $form->get('photo')->getData();
            
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                
                try {
                    $photoFile->move(
                        $this->getParameter('produits_photos_directory'),
                        $newFilename
                    );
                    
                    // Supprimez l'ancienne photo si elle existe
                    if ($produit->getPhoto()) {
                        $oldPhotoPath = $this->getParameter('produits_photos_directory').'/'.$produit->getPhoto();
                        if (file_exists($oldPhotoPath)) {
                            unlink($oldPhotoPath);
                        }
                    }
                    
                    $produit->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Produit modifié avec succès!');
            return $this->redirectToRoute('app_vendeur_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vendeur/produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
            'vendeur' => $vendeur,
        ]);
    }

    #[Route('/{id}', name: 'app_vendeur_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            // Supprimer aussi la photo du serveur
            if ($produit->getPhoto()) {
                $photoPath = $this->getParameter('produits_photos_directory').'/'.$produit->getPhoto();
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            
            $entityManager->remove($produit);
            $entityManager->flush();
            
            $this->addFlash('success', 'Produit supprimé avec succès!');
        }

        return $this->redirectToRoute('app_vendeur_produit_index', [], Response::HTTP_SEE_OTHER);
    }
    
    // AJOUTER CETTE MÉTHODE POUR VÉRIFIER LE NOM EN AJAX
    #[Route('/check-name/{nom}', name: 'app_vendeur_produit_check_name', methods: ['GET'])]
    public function checkName(string $nom, ProduitRepository $produitRepository): Response
    {
        $produit = $produitRepository->findOneBy(['nomProduit' => $nom]);
        
        return $this->json([
            'exists' => $produit !== null,
            'produit' => $produit ? [
                'id' => $produit->getId(),
                'nom' => $produit->getNomProduit(),
                'quantite' => $produit->getQuantiteStock(),
            ] : null
        ]);
    }
}