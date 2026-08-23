<?php

namespace App\Donor\Application\UseCase;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\Donation\Domain\Repository\DonationRepositoryInterface;
use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Donor\Domain\Service\DonorDonationEligibilityService;
use App\Donor\Domain\Service\DonorEligibilityService;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;

/**
 * Builds the data required by the donor dashboard.
 *
 * Extracted from DonorController::donor_dashboard so the controller stays a
 * thin HTTP adapter. Session access remains the controller's responsibility;
 * this use case receives the donor id and (resolved) blood group as input.
 */
class GetDonorDashboardUseCase
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private DonorRepositoryInterface $donorRepo,
        private DonationRepositoryInterface $donationRepo,
        private MasterDataRepository $masterRepo,
        private DonorDonationEligibilityService $donationEligibilityService,
        private DonorEligibilityService $donorEligibilityService,
        private NotificationRepositoryInterface $notificationRepo
    ) {
    }

    public function execute(int $donorId, string $bloodGroup): array
    {
        $acceptedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;
        $assignedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ASSIGNED') ?? 42;
        $pendingRequests = $this->bloodRequestRepo->findPendingRequestsForDonor($bloodGroup, $donorId);
        $assignedRequests = $this->bloodRequestRepo->findAssignedRequestsForDonor($donorId, (int) $assignedStatus);
        $acceptedRequests = $this->bloodRequestRepo->findAcceptedRequestsForDonor($donorId, (int) $acceptedStatus);
        $combinedRequests = array_merge($pendingRequests, $assignedRequests, $acceptedRequests);

        // The real donation ledger (includes standalone donations), newest first.
        $donations = $this->donationRepo->findByDonor($donorId);
        $lastDonation = $donations[0] ?? [];
        $lastDonationDate = !empty($lastDonation['donation_date'])
            ? (string) $lastDonation['donation_date']
            : '';
        $lastDonationLocation = !empty($lastDonation['hospital_name'])
            ? (string) $lastDonation['hospital_name']
            : 'Central Stock';

        $availabilityState = $this->donorRepo->syncAvailabilityStatus($donorId);

        $donorDetails = $this->donorRepo->getDonorDetails($donorId);
        $profileEligibility = $donorDetails ? $this->donorEligibilityService->evaluate(
            (string) ($donorDetails['date_of_birth'] ?? ''),
            (string) ($donorDetails['weight'] ?? ''),
            (bool) ($donorDetails['is_pregnant'] ?? 0)
        ) : ['eligible' => true, 'message' => '', 'reasons' => []];

        $eligibility = $this->donationEligibilityService->evaluate(
            $lastDonationDate,
            $availabilityState['next_available_date']
        );

        $activities = [];
        foreach ($acceptedRequests as $req) {
            if (!empty($req['created_at'])) {
                $activities[] = [
                    'type' => 'accepted',
                    'label' => 'Accepted request ' . ($req['request_code']
                        ?? 'BR-' . str_pad((string) ($req['request_id'] ?? 0), 3, '0', STR_PAD_LEFT)),
                    'timestamp' => $req['created_at'],
                ];
            }
        }

        foreach ($donations as $donation) {
            $date = $donation['donation_date'] ?? $donation['created_at'] ?? '';
            if (!empty($date)) {
                $activities[] = [
                    'type' => 'donation',
                    'label' => 'Donation completed' . (!empty($donation['request_code'])
                        ? ' (' . $donation['request_code'] . ')' : ''),
                    'timestamp' => $date,
                ];
            }
        }

        $activities[] = [
            'type' => 'availability',
            'label' => 'Changed availability to ' . ($availabilityState['available'] ? 'Available' : 'Unavailable'),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        $availMessage = $availabilityState['available']
            ? $eligibility['message']
            : ($availabilityState['next_available_date']
                ? ''
                : (!empty($profileEligibility['reasons'])
                    ? 'Not eligible: ' . implode(', ', $profileEligibility['reasons'])
                    : $eligibility['message']));

        // "My Impact" metrics.
        $impactBags = 0;
        foreach ($donations as $donation) {
            $impactBags += max(1, (int)($donation['bags'] ?? 1));
        }
        $impactRequestsSupported = count($acceptedRequests);

        $recentNotifications = array_slice($this->notificationRepo->findByUserId($donorId), 0, 3);

        return [
            'availability' => $availabilityState['available'] ? 'Available' : 'Unavailable',
            'availability_message' => $availMessage,
            'next_eligible_date' => $availabilityState['available']
                ? ''
                : ($availabilityState['next_available_date'] ?? ($eligibility['next_eligible_date'] ?? '')),
            'last_donation_date' => !empty($lastDonationDate)
                ? date('d M Y', strtotime($lastDonationDate))
                : 'No donation yet',
            'last_donation_location' => $lastDonationLocation,
            'blood_requests' => $combinedRequests,
            'pending_requests_count' => count($pendingRequests),
            'total_donations' => count($donations),
            'recent_activities' => $activities,
            'impact_donations' => count($donations),
            'impact_bags' => $impactBags,
            'impact_requests_supported' => $impactRequestsSupported,
            'recent_notifications' => $recentNotifications,
        ];
    }
}
