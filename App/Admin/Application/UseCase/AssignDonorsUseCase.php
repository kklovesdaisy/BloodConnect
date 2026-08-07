<?php

namespace App\Admin\Application\UseCase;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\BloodRequest\Domain\Service\RequestPrioritizationService;
use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Donor\Domain\Service\DonorEligibilityService;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;
use App\Shared\Infrastructure\Mail\EmailService;

class AssignDonorsUseCase
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private MasterDataRepository $masterRepo,
        private DonorRepositoryInterface $donorRepo,
        private RequestPrioritizationService $prioritizationService,
        private DonorEligibilityService $donorEligibilityService,
        private EmailService $emailService
    ) {}

    public function execute(int $requestId, array $donorIds): array
    {
        if (empty($donorIds)) {
            return ['success' => false, 'error' => 'No donors selected.'];
        }

        $request = $this->bloodRequestRepo->findById($requestId);

        if (!$request) {
            return ['success' => false, 'error' => 'Blood request not found.'];
        }

        $expiredStatus = $this->masterRepo->getId('REQUEST_STATUS', 'EXPIRED') ?? 48;
        if ((int)($request['status'] ?? 0) === $expiredStatus) {
            return ['success' => false, 'error' => 'This request has expired and can no longer be assigned.'];
        }

        $validDonorIds = [];
        $skippedDonors = [];

        foreach ($donorIds as $donorId) {
            $donorDetails = $this->donorRepo->getDonorDetails((int)$donorId);
            if ($donorDetails) {
                $result = $this->donorEligibilityService->evaluate(
                    (string)($donorDetails['date_of_birth'] ?? ''),
                    (string)($donorDetails['weight'] ?? '')
                );
                if ($result['eligible']) {
                    $validDonorIds[] = (int)$donorId;
                } else {
                    $donor = $this->userRepo->findById((int)$donorId);
                    $skippedDonors[] = ($donor['username'] ?? 'Donor #' . $donorId) . ' (' . implode('; ', $result['reasons']) . ')';
                }
            }
        }

        if (empty($validDonorIds)) {
            return ['success' => false, 'error' => 'None of the selected donors meet eligibility requirements (minimum 50kg weight).'];
        }

        $responded = $this->bloodRequestRepo->getDonorResponseStatuses($requestId, $validDonorIds);
        $filtered = [];
        foreach ($validDonorIds as $donorId) {
            if (isset($responded[$donorId])) {
                $donor = $this->userRepo->findById($donorId);
                $donorName = $donor['username'] ?? 'Donor #' . $donorId;
                $skippedDonors[] = $donorName . ' (already responded to this request)';
            } else {
                $filtered[] = $donorId;
            }
        }
        $validDonorIds = $filtered;

        if (empty($validDonorIds)) {
            return ['success' => false, 'error' => 'All selected donors have already responded to this request.'];
        }

        // Urgency-aware reservation (requirements #2, #5, #6):
        // a donor who could serve a HIGHER-priority competing request (same
        // blood group + location) may not be assigned to a lower-priority
        // request while that higher-priority request still needs a donor.
        $reservingRequest = $this->findReservingRequest($request);
        if ($reservingRequest !== null) {
            $label = $reservingRequest['request_code'] ?? '#' . $reservingRequest['request_id'];
            $urgency = strtoupper((string)($reservingRequest['urgency'] ?? 'ROUTINE'));
            foreach ($validDonorIds as $donorId) {
                $donor = $this->userRepo->findById((int)$donorId);
                $skippedDonors[] = sprintf(
                    '%s is reserved for the higher-priority request %s (%s). Assign donors to that request first.',
                    $donor['username'] ?? 'Donor #' . $donorId,
                    $label,
                    $urgency
                );
            }
            return [
                'success' => false,
                'error' => 'All selected donors are reserved for a higher-priority request. Assign donors to the highest-priority request first.',
            ];
        }

        $assignedGroups = $this->bloodRequestRepo->getDonorsAssignedToOtherRequests($validDonorIds, $requestId);
        $reassignedDonorIds = [];

        foreach ($assignedGroups as $donorId => $otherRequests) {
            $donor = $this->userRepo->findById((int)$donorId);
            $donorName = $donor['username'] ?? 'Donor #' . $donorId;
            $newRequestRank = $this->prioritizationService->rank((string)($request['urgency'] ?? ''));

            foreach ($otherRequests as $other) {
                $existingRank = $this->prioritizationService->rank((string)($other['urgency'] ?? ''));
                $reqCode = $other['request_code'] ?? '#' . $other['request_id'];
                $existingUrgency = strtoupper($other['urgency'] ?? 'ROUTINE');

                if ($newRequestRank < $existingRank) {
                    $this->bloodRequestRepo->unassignDonorFromRequest((int)$other['request_id'], (int)$donorId);
                    $reassignedDonorIds[] = (int)$donorId;

                    $this->notificationRepo->create(
                        (int)$other['patient_id'] ?? 0,
                        'Donor Reassigned',
                        sprintf(
                            'Donor %s has been reassigned from your blood request %s (%s) to a higher-priority request.',
                            $donorName,
                            $reqCode,
                            $existingUrgency
                        ),
                        'WARNING'
                    );
                    break;
                }
            }
        }

        $finalDonorIds = $validDonorIds;
        foreach ($assignedGroups as $donorId => $otherRequests) {
            if (!in_array((int)$donorId, $reassignedDonorIds, true)) {
                $finalDonorIds = array_values(array_diff($finalDonorIds, [(int)$donorId]));
                $donor = $this->userRepo->findById((int)$donorId);
                $donorName = $donor['username'] ?? 'Donor #' . $donorId;
                $requestParts = [];
                foreach ($otherRequests as $other) {
                    $reqCode = $other['request_code'] ?? '#' . $other['request_id'];
                    $urgency = strtoupper($other['urgency'] ?? 'ROUTINE');
                    $requestParts[] = "{$reqCode} ({$urgency})";
                }
                $skippedDonors[] = sprintf(
                    '%s is already assigned to: %s — request urgency is equal or higher, unassign manually first.',
                    $donorName,
                    implode(', ', $requestParts)
                );
            }
        }

        if (empty($finalDonorIds)) {
            return ['success' => false, 'error' => 'All selected donors are already assigned to equal or higher-priority requests.'];
        }

        $assignedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ASSIGNED') ?? 42;

        $assigned = $this->bloodRequestRepo->assignDonorsToRequest($requestId, array_values($finalDonorIds), $assignedStatus);

        if (!$assigned) {
            return ['success' => false, 'error' => 'Failed to assign donors.'];
        }

        $requestCode = (string)($request['request_code'] ?? 'N/A');
        $patientName = (string)($request['patient_name'] ?? 'A patient');
        $bloodGroup = (string)($request['blood_group_needed'] ?? '');

        $bloodGroupLabel = $this->masterRepo->getLabel('BLOOD_GROUP', $bloodGroup) ?? $bloodGroup;
        $assignmentData = [
            'patient_name'   => $patientName,
            'blood_group'    => $bloodGroupLabel,
            'hospital_name'  => (string)($request['hospital_name'] ?? 'the hospital'),
            'urgency'        => (string)($request['urgency'] ?? 'ROUTINE'),
            'request_code'   => $requestCode,
        ];

        foreach ($finalDonorIds as $donorId) {
            $donor = $this->userRepo->findById((int)$donorId);
            if ($donor) {
                $this->notificationRepo->create(
                    (int)$donorId,
                    'Blood Request Assigned',
                    sprintf(
                        'You have been assigned to blood request %s for patient %s (%s). Please review and respond.',
                        $requestCode,
                        $patientName,
                        $bloodGroup
                    ),
                    'REQUEST'
                );

                if (!empty($donor['email'])) {
                    $this->emailService->sendDonorAssignedToDonor($donor['email'], [
                        'donor_name'   => (string)($donor['username'] ?? 'Donor'),
                    ] + $assignmentData);
                }
            }
        }

        if (!empty($skippedDonors)) {
            $currentUserId = $_SESSION['user_id'] ?? 0;
            $this->notificationRepo->create(
                (int)$currentUserId,
                'Donors Skipped - Assignment Conflict',
                sprintf(
                    'The following donors could not be assigned to request %s due to weight/age requirements: %s',
                    $requestCode,
                    implode('; ', $skippedDonors)
                ),
                'WARNING'
            );
        }

        $patient = $this->userRepo->findById((int)($request['patient_id'] ?? 0));
        if ($patient) {
            $this->notificationRepo->create(
                (int)$patient['user_id'],
                'Donors Assigned',
                sprintf(
                    'Donors have been assigned to your blood request %s. They will review and respond shortly.',
                    $requestCode
                ),
                'REQUEST'
            );

            if (!empty($patient['email'])) {
                $this->emailService->sendDonorAssignedToPatient($patient['email'], $assignmentData);
            }
        }

        $admins = $this->notificationRepo->getAdmins();
        foreach ($admins as $admin) {
            $adminId = (int)($admin['user_id'] ?? 0);
            if ($adminId > 0) {
                $this->notificationRepo->create(
                    $adminId,
                    'Donors Assigned',
                    sprintf(
                        'Donors have been assigned to blood request %s for patient %s (%s).',
                        $requestCode,
                        $patientName,
                        $bloodGroup
                    ),
                    'ADMIN_ACTION'
                );
            }
        }

        return ['success' => true];
    }

    private function findReservingRequest(array $request): ?array
    {
        $bloodGroup = (string)($request['blood_group_needed'] ?? '');
        $stateRegion = (string)($request['state_region'] ?? '');

        if ($bloodGroup === '' || $stateRegion === '') {
            return null;
        }

        $currentRank = $this->prioritizationService->rank((string)($request['urgency'] ?? ''));
        $requestId = (int)($request['request_id'] ?? 0);

        $competing = $this->bloodRequestRepo->findCompetingRequests(
            $bloodGroup,
            $stateRegion,
            $requestId
        );

        foreach ($competing as $cr) {
            $isHigherPriority = $this->prioritizationService->rank((string)($cr['urgency'] ?? '')) < $currentRank;
            $stillNeedsDonor = empty($cr['donor_id']);

            if ($isHigherPriority && $stillNeedsDonor) {
                return $cr;
            }
        }

        return null;
    }
}
