<?php

namespace App\Donor\Application\UseCase;

use App\BloodStock\Domain\Entity\BloodStock;
use App\BloodStock\Domain\Repository\BloodStockRepositoryInterface;
use App\Donation\Domain\Repository\DonationRepositoryInterface;
use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Donor\Domain\Service\DonorDonationEligibilityService;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Infrastructure\Activity\ActivityLogger;
use App\Shared\Infrastructure\Database\Database;
use App\Shared\Infrastructure\Mail\EmailService;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;
use App\User\Domain\Repository\UserRepositoryInterface;

/**
 * Records a standalone donor donation (not linked to any patient request).
 *
 * Business rules:
 * - The donor must have a blood group set.
 * - A donor can donate at most 2 bags per donation.
 * - Pregnancy eligibility is enforced via the donor profile (is_pregnant)
 *   and the availability status, not per-donation.
 * - The donor must be eligible (3-month cooldown via the availability window).
 * - Inside one transaction: the donation is recorded, the donated bags are
 *   added to central stock, and any open hospital stock-request shortfalls for
 *   that group are auto-fulfilled oldest-first. If stock is later found
 *   insufficient, everything is rolled back.
 * - After donating, the donor enters a 3-month cooldown.
 */
class RecordDonationUseCase
{
    private const MAX_BAGS = 2;
    private const COOLDOWN_MONTHS = 3;

    public function __construct(
        private DonationRepositoryInterface $donationRepo,
        private DonorRepositoryInterface $donorRepo,
        private UserRepositoryInterface $userRepo,
        private BloodStockRepositoryInterface $stockRepo,
        private MasterDataRepository $masterRepo,
        private DonorDonationEligibilityService $eligibilityService,
        private NotificationRepositoryInterface $notificationRepo,
        private EmailService $emailService,
        private ActivityLogger $activityLogger
    ) {}

    public function execute(int $donorId, string $bloodGroup, int $bags): array
    {
        $group = BloodStock::normalize($bloodGroup);

        if ($group === '' || !in_array($group, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) {
            return ['success' => false, 'error' => 'Please set a valid blood group on your profile before donating.'];
        }

        $bags = max(1, $bags);

        if ($bags > self::MAX_BAGS) {
            return ['success' => false, 'error' => 'You can donate at most ' . self::MAX_BAGS . ' bags in a single donation.'];
        }

        $user = $this->userRepo->findById($donorId);
        if (!$user) {
            return ['success' => false, 'error' => 'Donor account not found.'];
        }

        $availabilityState = $this->donorRepo->syncAvailabilityStatus($donorId);
        $lastDonation = $this->donationRepo->findByDonor($donorId);
        $lastDonationDate = !empty($lastDonation[0]['donation_date'])
            ? (string)$lastDonation[0]['donation_date']
            : '';

        $eligibility = $this->eligibilityService->evaluate(
            $lastDonationDate,
            $availabilityState['next_available_date']
        );

        if (empty($eligibility['is_available']) || empty($availabilityState['available'])) {
            return [
                'success' => false,
                'error' => 'You are not eligible to donate right now. '
                    . ($eligibility['next_eligible_date'] !== ''
                        ? 'You will be eligible again on ' . $eligibility['next_eligible_date'] . '.'
                        : 'Please check your profile eligibility (age 18-65, minimum 50kg, not pregnant).'),
            ];
        }

        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 9;
        $today = date('Y-m-d');

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $donationId = $this->donationRepo->createStandalone([
                'donor_id'      => $donorId,
                'donation_date' => $today,
                'status'        => $completedStatus,
                'remarks'       => 'Standalone donation of ' . $bags . ' bag(s)',
                'bags'          => $bags,
            ]);

            if ($donationId === null) {
                throw new \RuntimeException('Could not record the donation.');
            }

            $this->stockRepo->recordBags($group, $bags, $today, $donationId);

            $nextAvailable = date('Y-m-d H:i:s', strtotime("+{$this->getCooldownMonths()} months", strtotime($today)));
            $this->donorRepo->saveNextAvailableDate($donorId, $nextAvailable);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('Standalone donation failed: ' . $e->getMessage());

            return ['success' => false, 'error' => 'Could not record the donation. Please try again.'];
        }

        $username = (string)($user['username'] ?? 'Donor');

        $this->activityLogger->log(
            $donorId,
            $username,
            'DONATION_RECORDED',
            sprintf('%s donated %d %s bag(s) to central stock.', $username, $bags, $group)
        );

        $this->notificationRepo->create(
            $donorId,
            'Donation Recorded',
            sprintf('Thank you! Your donation of %d %s bag(s) has been recorded.', $bags, $group),
            'REQUEST'
        );

        $admins = $this->notificationRepo->getAdmins();
        foreach ($admins as $admin) {
            $this->notificationRepo->create(
                (int)$admin['user_id'],
                'Standalone Donation Recorded',
                sprintf('%s donated %d %s bag(s) directly to central stock.', $username, $bags, $group),
                'REQUEST'
            );
        }

        if (!empty($user['email'])) {
            $this->emailService->sendDonationCompletedToDonor($user['email'], [
                'donor_name' => $username,
            ]);
        }

        $message = sprintf(
            'Your donation of %d %s bag(s) has been recorded successfully.',
            $bags,
            $group
        );

        return ['success' => true, 'message' => $message];
    }

    private function getCooldownMonths(): int
    {
        return self::COOLDOWN_MONTHS;
    }
}
