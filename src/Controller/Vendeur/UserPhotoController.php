<?php

namespace App\Controller\Vendeur;

use App\Entity\User;
use App\Service\ImageAnalysisService;
use App\Service\ImageEnhancementService;
use App\Service\AvatarGenerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/user/photo')]
class UserPhotoController extends AbstractController
{
        #[Route('', name: 'user_photo_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        // Si c'est pour un admin, utilisez 'admin' comme variable
        return $this->render('Vendeur/photo_upload.html.twig', [
            'vendeur' => $this->getUser()  // Changez 'user' en 'admin'
        ]);
    }
    

    #[Route('/upload', name: 'user_photo_upload', methods: ['POST'])]
    public function uploadPhoto(
        Request $request,
        ImageAnalysisService $analysisService,
        ImageEnhancementService $enhancementService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var UploadedFile $file */
        $file = $request->files->get('photo');
        
        if (!$file) {
            return $this->json([
                'success' => false,
                'error' => 'Aucun fichier téléchargé'
            ], 400);
        }

        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return $this->json([
                'success' => false,
                'error' => 'Type de fichier invalide. Veuillez télécharger une image JPEG, PNG, GIF ou WebP.'
            ], 400);
        }

        // Vérifier la taille (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json([
                'success' => false,
                'error' => 'Le fichier est trop volumineux. Taille maximale : 5MB.'
            ], 400);
        }

        // Convertir en base64 pour l'analyse
        $imageContent = file_get_contents($file->getPathname());
        $base64Image = base64_encode($imageContent);

        // 1. Modération rapide
        $isSafe = $analysisService->moderateImage($base64Image);
        
        if (!$isSafe) {
            return $this->json([
                'success' => false,
                'error' => "L'image contient du contenu inapproprié"
            ], 400);
        }

        // 2. Analyse complète
        $analysis = $analysisService->analyzeProfilePhoto($base64Image);

        // 3. Si qualité faible, suggérer amélioration
        if ($analysis['quality']['score'] < 5) {
            return $this->json([
                'success' => false,
                'need_improvement' => true,
                'analysis' => $analysis,
                'message' => 'La qualité de la photo est trop faible. Veuillez télécharger une image de meilleure qualité.'
            ], 400);
        }

        // 4. Sauvegarder l'image
        $filename = uniqid() . '.' . $file->guessExtension();
        $photosDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/photos/';
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($photosDirectory)) {
            mkdir($photosDirectory, 0777, true);
        }
        
        $file->move($photosDirectory, $filename);

        // 5. Mettre à jour la photo de l'utilisateur
        $user = $this->getUser();
        
        /** @var \App\Entity\User $user */
        if ($user) {
            // CORRECTION ICI : utiliser setPhoto() au lieu de setPhotoUrl()
            $user->setPhoto('/uploads/photos/' . $filename);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->json([
            'success' => true,
            'filename' => $filename,
            'photo_url' => '/uploads/photos/' . $filename,
            'analysis' => $analysis,
            'message' => 'Photo téléchargée avec succès !'
        ]);
    }

    #[Route('/generate', name: 'user_photo_generate', methods: ['POST'])]
    public function generateAvatar(
        Request $request,
        AvatarGenerationService $generationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $description = $data['description'] ?? 'professional person';
        $style = $data['style'] ?? 'eco';

        if (empty($description)) {
            return $this->json([
                'success' => false,
                'error' => 'La description est requise'
            ], 400);
        }

        try {
            $result = $generationService->generateAvatar($description, $style);

            return $this->json([
                'success' => true,
                'avatar_url' => $result['image_url'],
                'prompt_used' => $result['revised_prompt']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la génération de l\'avatar: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/generate-options', name: 'user_photo_generate_options', methods: ['POST'])]
    public function generateMultipleOptions(
        Request $request,
        AvatarGenerationService $generationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $description = $data['description'] ?? 'professional eco-conscious person';
        $count = $data['count'] ?? 3;

        if (empty($description)) {
            return $this->json([
                'success' => false,
                'error' => 'La description est requise'
            ], 400);
        }

        try {
            $options = $generationService->generateMultipleOptions($description, $count);

            return $this->json([
                'success' => true,
                'options' => $options
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la génération des options d\'avatar: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/analyze', name: 'user_photo_analyze', methods: ['POST'])]
    public function analyzeExistingPhoto(
        Request $request,
        ImageAnalysisService $analysisService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $imageUrl = $data['image_url'] ?? null;

        if (!$imageUrl) {
            return $this->json([
                'success' => false,
                'error' => 'Aucune URL d\'image fournie'
            ], 400);
        }

        try {
            // Télécharger et convertir en base64
            $imageContent = @file_get_contents($imageUrl);
            if ($imageContent === false) {
                return $this->json([
                    'success' => false,
                    'error' => 'Impossible de télécharger l\'image depuis l\'URL'
                ], 400);
            }

            $base64Image = base64_encode($imageContent);
            $analysis = $analysisService->analyzeProfilePhoto($base64Image);

            return $this->json([
                'success' => true,
                'analysis' => $analysis
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de l\'analyse de l\'image: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/enhance', name: 'user_photo_enhance', methods: ['POST'])]
    public function enhancePhoto(
        Request $request,
        ImageEnhancementService $enhancementService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $imageUrl = $data['image_url'] ?? null;
        $action = $data['action'] ?? 'remove_bg'; // remove_bg, eco_filter, resize

        if (!$imageUrl) {
            return $this->json([
                'success' => false,
                'error' => 'Aucune URL d\'image fournie'
            ], 400);
        }

        try {
            $result = match($action) {
                'remove_bg' => $enhancementService->removeBackground($imageUrl),
                'eco_filter' => ['success' => true, 'path' => $enhancementService->applyEcoFilter($imageUrl)],
                'resize' => ['success' => true, 'path' => $enhancementService->smartResize($imageUrl)],
                default => ['success' => false, 'error' => 'Action inconnue']
            };

            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de l\'amélioration de l\'image: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/save-avatar', name: 'user_photo_save_avatar', methods: ['POST'])]
    public function saveAvatar(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $avatarUrl = $data['avatar_url'] ?? null;

        if (!$avatarUrl) {
            return $this->json([
                'success' => false,
                'error' => 'Aucune URL d\'avatar fournie'
            ], 400);
        }

        try {
            // Télécharger l'avatar
            $imageContent = @file_get_contents($avatarUrl);
            if ($imageContent === false) {
                return $this->json([
                    'success' => false,
                    'error' => 'Impossible de télécharger l\'image de l\'avatar'
                ], 400);
            }

            // Sauvegarder l'image
            $filename = 'avatar_' . uniqid() . '.png';
            $photosDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/';
            
            // Créer le dossier s'il n'existe pas
            if (!file_exists($photosDirectory)) {
                mkdir($photosDirectory, 0777, true);
            }
            
            file_put_contents($photosDirectory . $filename, $imageContent);

            // Mettre à jour la photo de l'utilisateur
            $user = $this->getUser();
            
        /** @var \App\Entity\User $user */
            if ($user) {
                // CORRECTION ICI : utiliser setPhoto() au lieu de setPhotoUrl()
                $user->setPhoto('/uploads/avatars/' . $filename);
                $entityManager->persist($user);
                $entityManager->flush();
            }

            return $this->json([
                'success' => true,
                'photo_url' => '/uploads/avatars/' . $filename,
                'message' => 'Avatar sauvegardé avec succès !'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la sauvegarde de l\'avatar: ' . $e->getMessage()
            ], 500);
        }
    }
}