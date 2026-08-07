<?php

namespace App\Shared\Infrastructure\Report;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Renders any admin report to a printable PDF using Dompdf.
 * The shared HTML template lives in the report_pdf.php partial.
 */
class ReportPdfGenerator
{
    public function generate(
        string $title,
        array $columns,
        array $rows,
        array $totals = [],
        array $meta = [],
        string $subtitle = ''
    ): string {
        $template = dirname(__DIR__, 4) . '/App/Shared/Presentation/View/partials/report_pdf.php';
        $logoPath = dirname(__DIR__, 4) . '/public/images/bloodconnect-logo.png';

        $logoDataUri = '';
        if (is_file($logoPath)) {
            $logoDataUri = 'data:image/png;base64,' . base64_encode((string)file_get_contents($logoPath));
        }

        extract([
            'reportTitle' => $title,
            'reportSubtitle' => $subtitle,
            'reportColumns' => $columns,
            'reportRows' => $rows,
            'reportTotals' => $totals,
            'reportMeta' => $meta,
            'logoDataUri' => $logoDataUri,
        ], EXTR_SKIP);

        ob_start();
        require $template;
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
