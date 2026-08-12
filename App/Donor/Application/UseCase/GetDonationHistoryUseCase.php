<?php

namespace App\Donor\Application\UseCase;

use App\Donation\Domain\Repository\DonationRepositoryInterface;

/**
 * Builds the donor-side donation history.
 *
 * Unlike the accepted-request feed, this returns the actual donation records
 * (donation_history rows, including standalone donations) together with a
 * summary used by the Donation Summary card on the history page.
 */
class GetDonationHistoryUseCase
{
    public function __construct(
        private DonationRepositoryInterface $donationRepo
    ) {}

    public function execute(int $donorId): array
    {
        $donations = $this->donationRepo->findByDonor($donorId);

        $totalBags = 0;
        $lastDate = '';

        foreach ($donations as $donation) {
            $totalBags += (int)($donation['bags'] ?? 1);

            $date = trim((string)($donation['donation_date'] ?? ''));
            if ($date !== '' && ($lastDate === '' || $date > $lastDate)) {
                $lastDate = $date;
            }
        }

        return [
            'donations' => $donations,
            'total_donations' => count($donations),
            'total_bags' => $totalBags,
            'last_donation_date' => $lastDate !== ''
                ? date('d M Y', strtotime($lastDate))
                : 'No donation yet',
        ];
    }
}
