<?php

namespace App\Shared\Infrastructure\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Generates a Blood Request Receipt PDF using Dompdf.
 */
class BloodRequestReceiptPdfGenerator
{
    public function generate(array $request): string
    {
        $html = $this->renderTemplate($request);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function renderTemplate(array $request): string
    {
        $template = dirname(__DIR__, 4) . '/App/BloodRequest/Presentation/View/request_receipt.php';
        $logoPath = dirname(__DIR__, 4) . '/public/images/bloodconnect-logo.png';

        $logoDataUri = '';
        if (is_file($logoPath)) {
            $logoDataUri = 'data:image/png;base64,' . base64_encode((string)file_get_contents($logoPath));
        }

        extract([
            'request' => $request,
            'logoDataUri' => $logoDataUri,
        ], EXTR_SKIP);

        ob_start();
        require $template;
        return ob_get_clean();
    }
}
