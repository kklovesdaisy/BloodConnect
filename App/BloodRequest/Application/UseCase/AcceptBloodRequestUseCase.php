<?php

namespace App\BloodRequest\Application\UseCase;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Donation\Domain\Repository\DonationRepositoryInterface;
use App\Donor\Domain\Service\DonorDonationEligibilityService;
use App\Donor\Domain\Service\DonorEligibilityService;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;
use App\Shared\Infrastructure\Activity\ActivityLogger;
use App\Shared\Infrastructure\Mail\EmailService;

class AcceptBloodRequestUseCase
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private DonorRepositoryInterface $donorRepo,
        private DonationRepositoryInterface $donationRepo,
        private DonorDonationEligibilityService $eligibilityService,
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private MasterDataRepository $masterRepo,
        private ActivityLogger $activityLogger,
        private DonorEligibilityService $donorEligibilityService,
        private EmailService $emailService
    ) {}

    public function execute(int $requestId, int $donorId): array
    {
        $request = $this->bloodRequestRepo->findById($requestId);
        if (!$request) {
            return ['success' => false, 'error' => 'Blood request not found.'];
        }

        $expiredStatus = $this->masterRepo->getId('REQUEST_STATUS', 'EXPIRED') ?? 48;
        $pendingStatus = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;

        if ((int)($request['status'] ?? 0) === $expiredStatus) {
            return ['success' => false, 'error' => 'This request has expired and can no longer be accepted.'];
        }

        if (!empty($request['expiry_date']) && strtotime((string)$request['expiry_date']) < time()) {
            if ((int)($request['status'] ?? 0) === $pendingStatus) {
                $this->bloodRequestRepo->expireOverdueRequests();
            }
            return ['success' => false, 'error' => 'This request has expired and can no longer be accepted.'];
        }

        $donorProfile = $this->donorRepo->getDonorDetails($donorId);
        if ($donorProfile) {
            $profileEligibility = $this->donorEligibilityService->evaluate(
                (string)($donorProfile['date_of_birth'] ?? ''),
                (string)($donorProfile['weight'] ?? '')
            );
            if (!$profileEligibility['eligible']) {
                return ['success' => false, 'error' => 'You cannot accept this request: ' . implode(', ', $profileEligibility['reasons'])];
            }
        }

        $availability = $this->donorRepo->syncAvailabilityStatus($donorId);
        $acceptedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;

        $lastDonation = $this->bloodRequestRepo->findAcceptedRequestsForDonor($donorId, $acceptedStatus);
        $lastDonationDate = !empty($lastDonation[0]['created_at']) ? (string)$lastDonation[0]['created_at'] : '';

        $eligibility = $this->eligibilityService->evaluate($lastDonationDate, $availability['next_available_date']);
        if (!$eligibility['is_available']) {
            return ['success' => false, 'error' => 'You are not eligible to donate yet. Next eligible date: ' . $eligibility['next_eligible_date']];
        }

        $existingAccepted = $this->bloodRequestRepo->findAcceptedRequestsForDonor($donorId, $acceptedStatus);
        if (!empty($existingAccepted)) {
            return ['success' => false, 'error' => 'You cannot accept more requests while you have an active accepted request.'];
        }

        $updated = $this->bloodRequestRepo->updateDonorDecision($requestId, $donorId, $acceptedStatus);
        if (!$updated) {
            return ['success' => false, 'error' => 'Failed to accept request.'];
        }

        $nextAvailableDate = (new \DateTime('now', new \DateTimeZone('Asia/Yangon')))
            ->modify('+3 months')
            ->format('Y-m-d H:i:s');
        $this->donorRepo->saveNextAvailableDate($donorId, $nextAvailableDate);

        $this->donationRepo->create([
            'request_id'    => $requestId,
            'donor_id'      => $donorId,
            'donation_date' => date('Y-m-d'),
            'status'        => $acceptedStatus,
            'remarks'       => 'Accepted by donor'
        ]);

        $request = $this->bloodRequestRepo->findById($requestId);
        $donor = $this->userRepo->findById($donorId);
        $patient = $this->userRepo->findById((int)($request['patient_id'] ?? 0));

        $requestCode = (string)($request['request_code'] ?? 'N/A');
        $donorName = (string)($donor['username'] ?? 'Unknown donor');

        $admins = $this->notificationRepo->getAdmins();
        foreach ($admins as $admin) {
            $this->notificationRepo->create(
                (int)$admin['user_id'],
                'Blood Request Accepted',
                sprintf(
                    'Blood request %s has been accepted by donor %s.',
                    $requestCode,
                    $donorName
                ),
                'REQUEST'
            );
        }

        $acceptedData = [
            'patient_name'   => (string)($request['patient_name'] ?? 'A patient'),
            'blood_group'    => $this->masterRepo->getLabel('BLOOD_GROUP', (string)($request['blood_group_needed'] ?? '')) ?? ($request['blood_group_needed'] ?? ''),
            'hospital_name'  => (string)($request['hospital_name'] ?? 'the hospital'),
            'request_code'   => $requestCode,
        ];

        $adminEmails = $this->userRepo->getAdminEmails();
        foreach ($adminEmails as $admin) {
            if (!empty($admin['email'])) {
                $this->emailService->sendDonorAcceptedToAdmin($admin['email'], [
                    'donor_name' => $donorName,
                ] + $acceptedData);
            }
        }

        if ($patient && !empty($patient['email'])) {
            $this->emailService->sendDonorAcceptedToPatient($patient['email'], [
                'donor_name' => $donorName,
            ] + $acceptedData);
        }

        if ($donor) {
            $this->notificationRepo->create(
                $donorId,
                'Blood Request Accepted',
                sprintf(
                    'You accepted blood request %s. The patient will be notified shortly.',
                    $requestCode
                ),
                'REQUEST'
            );
        }

        if ($patient) {
            $this->notificationRepo->create(
                (int)$patient['user_id'],
                'Blood Request Accepted',
                sprintf(
                    'Donor %s has accepted your blood request %s.',
                    $donorName,
                    $requestCode
                ),
                'REQUEST'
            );
        }

        $this->activityLogger->log(
            $donorId,
            $donor['username'] ?? null,
            'REQUEST_ACCEPTED',
            "Donor accepted blood request {$requestCode} (ID: {$requestId})"
        );

        return ['success' => true];
    }
}
