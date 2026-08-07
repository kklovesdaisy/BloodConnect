<?php

namespace App\BloodRequest\Application\UseCase;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\Shared\Infrastructure\Pdf\BloodRequestReceiptPdfGenerator;

/**
 * Generates a downloadable Blood Request Receipt PDF for a patient's request.
 */
class GenerateRequestReceiptUseCase
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private BloodRequestReceiptPdfGenerator $receiptPdfGenerator
    ) {}

    public function execute(int $requestId, int $patientId): array
    {
        $request = $this->bloodRequestRepo->findPatientRequestDetail($requestId, $patientId);

        if (empty($request)) {
            return ['success' => false, 'error' => 'Blood request not found.'];
        }

        try {
            $pdf = $this->receiptPdfGenerator->generate($request);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Could not generate the receipt PDF. Please try again.'];
        }

        $requestCode = trim((string)($request['request_code'] ?? ''));

        return [
            'success'  => true,
            'pdf'      => $pdf,
            'filename' => 'BloodConnect_Receipt_' . ($requestCode !== '' ? $requestCode : 'Request' . $requestId) . '.pdf',
        ];
    }
}
