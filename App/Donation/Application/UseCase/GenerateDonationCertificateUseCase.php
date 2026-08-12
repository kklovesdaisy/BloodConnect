<?php

namespace App\Donation\Application\UseCase;

use App\Donation\Domain\Repository\DonationRepositoryInterface;
use App\Shared\Infrastructure\Pdf\DonationCertificatePdfGenerator;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;

/**
 * Generates a downloadable Donation Certificate PDF for a donor's completed donation.
 */
class GenerateDonationCertificateUseCase
{
    public function __construct(
        private DonationRepositoryInterface $donationRepo,
        private DonationCertificatePdfGenerator $certificatePdfGenerator,
        private MasterDataRepository $masterRepo
    ) {}

    public function execute(int $donationId, int $donorId): array
    {
        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 11;

        $donation = $this->donationRepo->findDonationForCertificate($donationId, $donorId, $completedStatus);

        if (empty($donation)) {
            return ['success' => false, 'error' => 'No completed donation record found for this request.'];
        }

        $donationId = (int)($donation['donation_id'] ?? 0);
        $donation['certificate_number'] = 'BC-' . str_pad((string)$donationId, 6, '0', STR_PAD_LEFT);

        try {
            $pdf = $this->certificatePdfGenerator->generate($donation);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Could not generate the donation certificate PDF. Please try again.'];
        }

        $requestCode = trim((string)($donation['request_code'] ?? ''));

        return [
            'success'  => true,
            'pdf'      => $pdf,
            'filename' => 'BloodConnect_Donation_Certificate_' . ($requestCode !== '' ? $requestCode : 'Donation' . $donationId) . '.pdf',
        ];
    }
}
