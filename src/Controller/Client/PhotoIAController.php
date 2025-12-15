<?php
// src/Controller/Client/PhotoIAController.php

namespace App\Controller\Client;

use App\Service\GeminiPhotoService;
use App\Service\PollinationsImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/photoClient')]
class PhotoIAController extends AbstractController
{
    private EntityManagerInterface $em;
    private GeminiPhotoService $gemini;
    private PollinationsImageService $pollinations;

    public function __construct(
        EntityManagerInterface $em,
        GeminiPhotoService $gemini,
        PollinationsImageService $pollinations
    ) {
        $this->em = $em;
        $this->gemini = $gemini;
        $this->pollinations = $pollinations;
    }

    #[Route('/dashboard', name: 'client_photo_test')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        
        return $this->render('Client/GestionUser/dashboard.html.twig', [
            'client' => $this->getUser()
        ]);
    }

    #[Route('/upload', name: 'client_photo_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_CLIENT')) {
                return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
            }
            
            $user = $this->getUser();
            
            if (!$user) {
                return $this->json(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
            }
            
            /** @var UploadedFile $file */
            $file = $request->files->get('photo');
            
            if (!$file) {
                return $this->json(['success' => false, 'error' => 'Aucun fichier'], 400);
            }

            $imageContent = file_get_contents($file->getPathname());
            $base64Image = base64_encode($imageContent);
            $mimeType = $file->getMimeType();

            // Modération (optionnelle - si elle échoue, on continue quand même)
            try {
                $isSafe = $this->gemini->quickModeration($base64Image, $mimeType);
                if (!$isSafe) {
                    return $this->json([
                        'success' => false,
                        'error' => 'Photo inappropriée'
                    ], 400);
                }
            } catch (\Exception $e) {
                // Si la modération échoue (API indisponible), on continue quand même
                error_log('Modération échouée, continuation: ' . $e->getMessage());
            }

            // Analyse
            $analysis = $this->gemini->analyzePhoto($base64Image, $mimeType);

            if (($analysis['quality']['score'] ?? 0) < 4) {
                return $this->json([
                    'success' => false,
                    'error' => 'Qualité trop faible',
                    'analysis' => $analysis
                ], 400);
            }

            /** @var \App\Entity\User $user */
            // Supprimer ancienne photo
            if ($user->getPhoto()) {
                $projectDir = $this->getParameter('kernel.project_dir');
                $oldPath = $projectDir . '/public/uploads/photos/' . $user->getPhoto();
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Sauvegarder
            $filename = uniqid('client_') . '.' . $file->guessExtension();
            $projectDir = $this->getParameter('kernel.project_dir');
            $uploadDir = $projectDir . '/public/uploads/photos';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $file->move($uploadDir, $filename);

            /** @var \App\Entity\User $user */
            // Mettre à jour
            $user->setPhoto($filename);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'filename' => $filename,
                'url' => '/uploads/photos/' . $filename,
                'analysis' => $analysis
            ]);

        } catch (AccessDeniedException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Accès refusé'
            ], 403);
        } catch (\Exception $e) {
            error_log('Erreur upload photo: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return $this->json([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            error_log('Erreur fatale upload photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur'
            ], 500);
        }
    }

    #[Route('/analyze', name: 'client_photo_analyze', methods: ['GET'])]
    public function analyze(): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_CLIENT')) {
                return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
            }
            
            $user = $this->getUser();
            
            if (!$user) {
                return $this->json(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
            }
            /** @var \App\Entity\User $user */
            if (!$user->getPhoto()) {
                return $this->json(['success' => false, 'error' => 'Pas de photo'], 404);
            }

            $projectDir = $this->getParameter('kernel.project_dir');
            $photoPath = $projectDir . '/public/uploads/photos/' . $user->getPhoto();
            
            if (!file_exists($photoPath)) {
                return $this->json(['success' => false, 'error' => 'Photo non trouvée'], 404);
            }

            $imageContent = file_get_contents($photoPath);
            $base64Image = base64_encode($imageContent);
            
            $analysis = $this->gemini->analyzePhoto($base64Image);

            return $this->json([
                'success' => true,
                'analysis' => $analysis
            ]);

        } catch (AccessDeniedException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Accès refusé'
            ], 403);
        } catch (\Exception $e) {
            error_log('Erreur analyse photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            error_log('Erreur fatale analyse photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur'
            ], 500);
        }
    }

    #[Route('/generate-from-description', name: 'client_photo_generate_from_description', methods: ['POST'])]
    public function generateFromDescription(Request $request): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_CLIENT')) {
                return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
            }
            $data = json_decode($request->getContent(), true);
            $description = $data['description'] ?? '';

            if (empty(trim($description))) {
                return $this->json([
                    'success' => false,
                    'error' => 'Description vide'
                ], 400);
            }

            // Optimiser avec Gemini
            try {
                $optimizedPrompt = $this->gemini->generateAvatarPrompt($description);
            } catch (\Exception $e) {
                error_log('Erreur génération prompt: ' . $e->getMessage());
                // Fallback: utiliser la description directement
                $optimizedPrompt = "professional portrait, " . $description;
            }

            // Générer avec Pollinations
            try {
                $result = $this->pollinations->generateImage($optimizedPrompt);
            } catch (\Exception $e) {
                error_log('Erreur génération image Pollinations: ' . $e->getMessage());
                return $this->json([
                    'success' => false,
                    'error' => 'Erreur lors de la génération de l\'image. Veuillez réessayer.'
                ], 500);
            }

            if (!$result['success']) {
                return $this->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur génération'
                ], 500);
            }

            return $this->json([
                'success' => true,
                'image_base64' => $result['image_base64'],
                'prompt_used' => $optimizedPrompt,
                'original_description' => $description,
                'note' => $result['note'] ?? null
            ]);

        } catch (AccessDeniedException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Accès refusé'
            ], 403);
        } catch (\Exception $e) {
            error_log('Erreur génération photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            error_log('Erreur fatale génération photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur'
            ], 500);
        }
    }

    #[Route('/save-generated', name: 'client_photo_save_generated', methods: ['POST'])]
    public function saveGenerated(Request $request): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_CLIENT')) {
                return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
            }
            $data = json_decode($request->getContent(), true);
            $imageBase64 = $data['image_base64'] ?? '';

            if (empty($imageBase64)) {
                return $this->json(['success' => false, 'error' => 'Image manquante'], 400);
            }

            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageBase64));
            $user = $this->getUser();

            /** @var \App\Entity\User $user */
            // Supprimer ancienne
            if ($user->getPhoto()) {
                $projectDir = $this->getParameter('kernel.project_dir');
                $oldPath = $projectDir . '/public/uploads/photos/' . $user->getPhoto();
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Sauvegarder
            $filename = uniqid('client_ai_') . '.jpg';
            $saved = $this->pollinations->saveImage($imageData, $filename);

            if (!$saved) {
                return $this->json(['success' => false, 'error' => 'Erreur sauvegarde'], 500);
            }

            /** @var \App\Entity\User $user */
            $user->setPhoto($filename);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'filename' => $filename,
                'url' => '/uploads/photos/' . $filename
            ]);

        } catch (AccessDeniedException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Accès refusé'
            ], 403);
        } catch (\Exception $e) {
            error_log('Erreur sauvegarde photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            error_log('Erreur fatale sauvegarde photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur'
            ], 500);
        }
    }

    #[Route('/enhance-recreate', name: 'client_photo_enhance_recreate', methods: ['POST'])]
    public function enhanceRecreate(): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_CLIENT')) {
                return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
            }
            $user = $this->getUser();
            /** @var \App\Entity\User $user */
            if (!$user->getPhoto()) {
                return $this->json(['success' => false, 'error' => 'Pas de photo'], 404);
            }

            $projectDir = $this->getParameter('kernel.project_dir');
            $photoPath = $projectDir . '/public/uploads/photos/' . $user->getPhoto();
            
            if (!file_exists($photoPath)) {
                return $this->json(['success' => false, 'error' => 'Photo non trouvée'], 404);
            }

            $imageContent = file_get_contents($photoPath);
            $base64Image = base64_encode($imageContent);
            
            // Détecter le type MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $photoPath) ?: 'image/jpeg';
            finfo_close($finfo);
            
            try {
                $enhancedPrompt = $this->gemini->enhanceImageDescription($base64Image, $mimeType);
            } catch (\Exception $e) {
                error_log('Erreur amélioration description: ' . $e->getMessage());
                // Fallback: prompt générique
                $enhancedPrompt = "professional portrait, high quality, detailed, studio lighting";
            }
            
            try {
                $result = $this->pollinations->generateImage($enhancedPrompt);
            } catch (\Exception $e) {
                error_log('Erreur génération image améliorée: ' . $e->getMessage());
                return $this->json([
                    'success' => false,
                    'error' => 'Erreur lors de la génération de l\'image améliorée. Veuillez réessayer.'
                ], 500);
            }

            if (!$result['success']) {
                return $this->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur génération'
                ], 500);
            }

            return $this->json([
                'success' => true,
                'image_base64' => $result['image_base64'],
                'prompt_used' => $enhancedPrompt
            ]);

        } catch (AccessDeniedException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Accès refusé'
            ], 403);
        } catch (\Exception $e) {
            error_log('Erreur amélioration photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            error_log('Erreur fatale amélioration photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur'
            ], 500);
        }
    }

    #[Route('/generate-variants', name: 'client_photo_generate_variants', methods: ['POST'])]
    public function generateVariants(Request $request): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_CLIENT')) {
                return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
            }
            $data = json_decode($request->getContent(), true);
            $description = $data['description'] ?? '';

            if (empty($description)) {
                return $this->json(['success' => false, 'error' => 'Description manquante'], 400);
            }

            $optimizedPrompt = $this->gemini->generateAvatarPrompt($description);
            $variants = $this->pollinations->generateVariants($optimizedPrompt, 3);

            return $this->json([
                'success' => true,
                'variants' => $variants,
                'prompt_used' => $optimizedPrompt
            ]);

        } catch (AccessDeniedException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Accès refusé'
            ], 403);
        } catch (\Exception $e) {
            error_log('Erreur variantes photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            error_log('Erreur fatale variantes photo: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur'
            ], 500);
        }
    }
}