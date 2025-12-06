<?php
namespace App\Controller\Client;

use App\Entity\Reclamation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/client/pdf')]
class PdfController extends AbstractController
{
    #[Route('/reclamation/{id}', name: 'client_reclamation_pdf')]
    public function reclamationPdf(Reclamation $reclamation): Response
    {
        // Options Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        // Convertir le logo en Base64
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/BO/images/logo.svg';
        $logoBase64 = '';
        
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            // Pour SVG
            $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode($imageData);
        }

        // Préparer le HTML
        $html = $this->renderView('Client/pdf/reclamation.html.twig', [
            'reclamation' => $reclamation,
            'logoBase64' => $logoBase64,  // On passe logoBase64
        ]);

        // Générer le PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Retourner le PDF
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reclamation_' . $reclamation->getId() . '.pdf"'
            ]
        );
    }
}