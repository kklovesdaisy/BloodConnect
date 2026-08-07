<?php

namespace App\Shared\Infrastructure\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Generates a Donation Certificate PDF using Dompdf.
 */
class DonationCertificatePdfGenerator
{
    public function generate(array $donation): string
    {
        $html = $this->renderTemplate($donation);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    private function renderTemplate(array $donation): string
    {
        $template = dirname(__DIR__, 4) . '/App/Donation/Presentation/View/donation_certificate.php';
        $logoPath = dirname(__DIR__, 4) . '/public/images/bloodconnect-logo.png';

        $logoDataUri = '';
        if (is_file($logoPath)) {
            $logoDataUri = 'data:image/png;base64,' . base64_encode((string)file_get_contents($logoPath));
        }

        extract([
            'donation' => $donation,
            'logoDataUri' => $logoDataUri,
        ], EXTR_SKIP);

        ob_start();
        require $template;
        return ob_get_clean();
    }
}
