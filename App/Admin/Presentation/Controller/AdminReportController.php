<?php

namespace App\Admin\Presentation\Controller;

use App\Admin\Application\UseCase\ReportDataProvider;
use App\Shared\Infrastructure\Report\NativeXlsxGenerator;
use App\Shared\Infrastructure\Report\ReportPdfGenerator;

/**
 * Admin Export Reports: on-screen filtered views plus PDF / Excel downloads.
 * Each report exposes the same filters to both the browser table and the
 * exporters, so the exported file always matches what was on screen.
 */
class AdminReportController
{
    private ReportDataProvider $provider;
    private ReportPdfGenerator $pdf;
    private NativeXlsxGenerator $xlsx;

    public function __construct()
    {
        $this->provider = new ReportDataProvider();
        $this->pdf = new ReportPdfGenerator();
        $this->xlsx = new NativeXlsxGenerator();
    }

    public function index(): void
    {
        ob_start();
        require __DIR__ . '/../View/reports.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Layout/adminApp.php';
    }

    public function donors(): void
    {
        $this->render('donor_report.php', 'donors', 'Donor Report', 'donor_report');
    }

    public function patients(): void
    {
        $this->render('patient_report.php', 'patients', 'Patient Report', 'patient_report');
    }

    public function bloodRequests(): void
    {
        $this->render('blood_request_report.php', 'bloodRequests', 'Blood Request Report', 'blood_request_report');
    }

    public function donations(): void
    {
        $tab = strtolower((string)($_GET['tab'] ?? 'donor')) === 'hospital' ? 'hospital' : 'donor';
        $method = $tab === 'hospital' ? 'hospitalAllocations' : 'donations';
        $this->render('donation_report.php', $method, 'Donation Report', 'donation_report', $tab);
    }

    private function render(string $view, string $providerMethod, string $title, string $fileStem, string $tab = 'donor'): void
    {
        $export = strtolower((string)($_GET['export'] ?? ''));
        $filters = array_intersect_key($_GET, array_flip(['q', 'status', 'urgency', 'blood_group', 'location', 'from', 'to']));

        $data = $this->provider->{$providerMethod}($filters);

        if ($export === 'pdf') {
            $this->sendPdf($title, $fileStem, $data, $tab);
        }
        if ($export === 'excel' || $export === 'xlsx') {
            $this->sendExcel($title, $fileStem, $data, $tab);
        }

        $reportData = $data;
        $reportTitle = $title;
        $reportTab = $tab;

        ob_start();
        require __DIR__ . '/../View/' . $view;
        $content = ob_get_clean();
        require __DIR__ . '/../Layout/adminApp.php';
    }

    private function sendPdf(string $title, string $fileStem, array $data, string $tab = 'donor'): void
    {
        $meta = $this->meta();
        $columns = $this->columnsFor($fileStem, $tab);
        $content = $this->pdf->generate($title, $columns, $data['rows'], $data['totals'], $meta, $this->subtitle($data['filters']));

        $filename = $fileStem . '_' . date('Y-m-d') . '.pdf';
        $this->download($content, $filename, 'application/pdf');
    }

    private function sendExcel(string $title, string $fileStem, array $data, string $tab = 'donor'): void
    {
        $meta = $this->meta();
        $columns = $this->columnsFor($fileStem, $tab);
        $content = $this->xlsx->generate($title, $columns, $data['rows'], $data['totals'], $meta, $this->subtitle($data['filters']));

        $filename = $fileStem . '_' . date('Y-m-d') . '.xlsx';
        $this->download($content, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function columnsFor(string $fileStem, string $tab = 'donor'): array
    {
        if ($fileStem === 'donation_report' && $tab === 'hospital') {
            return ['Request Code', 'Hospital', 'Location', 'Blood Group', 'Requested', 'Allocated', 'Pending', 'Status', 'Date'];
        }
        return match ($fileStem) {
            'donor_report' => ['Donor ID', 'Name', 'Blood Group', 'Phone', 'Location', 'Status'],
            'patient_report' => ['Patient ID', 'Name', 'Blood Group', 'Phone', 'Status'],
            'blood_request_report' => ['Request ID', 'Patient', 'Blood Group', 'Hospital', 'Urgency', 'Status'],
            'donation_report' => ['Donation ID', 'Donor', 'Patient', 'Date', 'Hospital', 'Status'],
            default => [],
        };
    }

    private function subtitle(array $filters): string
    {
        $from = $filters['from'] ?? '';
        $to = $filters['to'] ?? '';
        if ($from !== '' && $to !== '') {
            return 'Period: ' . date('d-M-Y', strtotime($from)) . ' to ' . date('d-M-Y', strtotime($to));
        }
        if ($from !== '') {
            return 'Period: from ' . date('d-M-Y', strtotime($from));
        }
        if ($to !== '') {
            return 'Period: up to ' . date('d-M-Y', strtotime($to));
        }
        return 'All records';
    }

    private function meta(): array
    {
        return [
            'Generated By' => 'Administrator',
            'Generated Date' => date('d-M-Y'),
            'Generated Time' => date('h:i A'),
        ];
    }

    private function download(string $content, string $filename, string $mime): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo $content;
        exit;
    }
}
