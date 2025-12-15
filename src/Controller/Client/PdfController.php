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

        $logoPath = $this->getParameter('kernel.project_dir') . '/public/BO/images/logo.svg';
        $logoBase64 = '';
        
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode($imageData);
        }

        $cachePath = $this->getParameter('kernel.project_dir') . '/public/FO/img/stamp.svg';
$cacheBase64 = '';
if (file_exists($cachePath)) {
    $imageData = file_get_contents($cachePath);
    $cacheBase64 = 'data:image/png;base64,' . base64_encode($imageData);
}

$signaturePath = $this->getParameter('kernel.project_dir') . '/public/FO/img/signature.svg';
$signatureBase64 = '';
if (file_exists($signaturePath)) {
    $imageData = file_get_contents($signaturePath);
    $signatureBase64 = 'data:image/svg+xml;base64,' . base64_encode($imageData);
}

        $html = $this->renderView('Client/pdf/reclamation.html.twig', [
            'reclamation' => $reclamation,
            'logoBase64' => $logoBase64, 
            'cacheBase64' => $cacheBase64, 
            'signatureBase64' => $signatureBase64,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

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