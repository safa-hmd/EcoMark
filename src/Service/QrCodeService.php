<?php
// src/Service/QrCodeService.php
namespace App\Service;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    public function generateQrCode(string $url, int $size = 200): string
    {
        $qrCode = new QrCode($url);
        $qrCode->setSize($size);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Retourne un Data URI prêt à être utilisé dans <img src="...">
        return $result->getDataUri();
    }
}
