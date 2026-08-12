<?php

namespace App\Admin\Application\UseCase;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\BloodRequest\Domain\Service\RequestPrioritizationService;
use App\BloodStock\Domain\Entity\BloodStock;
use App\BloodStock\Domain\Repository\BloodStockRepositoryInterface;
use App\Donation\Domain\Repository\DonationRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Infrastructure\Database\Database;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;
use App\Shared\Infrastructure\Mail\EmailService;

class ConfirmDonationUseCase
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private DonationRepositoryInterface $donationRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private MasterDataRepository $masterRepo,
        private RequestPrioritizationService $prioritizationService,
        private EmailService $emailService,
        private BloodStockRepositoryInterface $bloodStockRepo
    ) {}

    /**
     * Complete a blood request and update related records.
     *
     * Business rules:
     * - Request must exist
     * - Request must have a donor assigned
     * - Donation record status is synced
     * - Stock accounting runs inside a transaction: the donated bag is added
     *   to the donor's blood group, then one bag of the requested group is
     *   issued to the patient. If the requested group lacks stock the request
     *   cannot be completed and the whole change is rolled back.
     * - Admins are notified
     */
    public function complete(int $requestId): array
    {
        $request = $this->bloodRequestRepo->findById($requestId);

        if (!$request) {
            return ['success' => false, 'error' => 'Blood request not found.'];
        }

        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 11;

        // Idempotency guard: a re-complete must not run stock accounting twice.
        if ((int)($request['status'] ?? 0) === $completedStatus) {
            return ['success' => true];
        }

        // Business rule: request must have a donor assigned
        $donorId = (int)($request['donor_id'] ?? 0);
        if ($donorId <= 0) {
            return ['success' => false, 'error' => 'Cannot complete a request with no donor assigned.'];
        }

        $neededGroup = BloodStock::normalize((string)($request['blood_group_needed'] ?? ''));
        $donor = $this->userRepo->findById($donorId);
        $donorGroup = BloodStock::normalize((string)($donor['blood_group'] ?? ''));

        $donation = $this->donationRepo->findByRequestId($requestId);
        $donationId = $donation ? (int)$donation['donation_id'] : null;
        $donationDate = !empty($donation['donation_date'])
            ? (string)$donation['donation_date']
            : date('Y-m-d');

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Stock in: the donated bag enters inventory under the donor's
            // group with the donation date and the standard 42-day shelf life.
            if ($donorGroup !== '') {
                $this->bloodStockRepo->recordBags($donorGroup, 1, $donationDate, $donationId);
            }

            // Stock out: one bag of the needed group is issued to the patient.
            if ($neededGroup === '' || !$this->bloodStockRepo->decreaseIfAvailable($neededGroup, 1)) {
                $db->rollBack();
                return ['success' => false, 'error' => 'Insufficient blood stock.'];
            }

            // Update blood request status
            $this->bloodRequestRepo->completeRequest($requestId, (int)$completedStatus);

            // Sync donation record
            $this->donationRepo->updateStatusByRequestId($requestId, (int)$completedStatus);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Failed to complete the request. Please try again.'];
        }

        // Notify all admins
        $admins = $this->notificationRepo->getAdmins();
        foreach ($admins as $admin) {
            $this->notificationRepo->create(
                (int)$admin['user_id'],
                'Blood Request Completed',
                sprintf(
                    'Blood request %s has been completed and the record has been updated.',
                    $request['request_code']
                ),
                'REQUEST'
            );
        }

        $completionData = [
            'patient_name'   => (string)($request['patient_name'] ?? 'A patient'),
            'blood_group'    => $this->masterRepo->getLabel('BLOOD_GROUP', (string)($request['blood_group_needed'] ?? '')) ?? ($request['blood_group_needed'] ?? ''),
            'hospital_name'  => (string)($request['hospital_name'] ?? 'the hospital'),
            'request_code'   => (string)($request['request_code'] ?? ''),
        ];

        $patient = $this->userRepo->findById((int)($request['patient_id'] ?? 0));
        if ($patient) {
            $this->notificationRepo->create(
                (int)$patient['user_id'],
                'Donation Completed',
                sprintf(
                    'The blood donation for your request %s has been completed successfully.',
                    $request['request_code']
                ),
                'REQUEST'
            );

            if (!empty($patient['email'])) {
                $this->emailService->sendDonationCompletedToPatient($patient['email'], $completionData);
            }
        }

        $donor = $this->userRepo->findById($donorId);
        if ($donor) {
            $this->notificationRepo->create(
                (int)$donor['user_id'],
                'Donation Completed',
                'Your blood donation process has been completed successfully. Thank you for being a lifesaver.',
                'REQUEST'
            );

            if (!empty($donor['email'])) {
                $this->emailService->sendDonationCompletedToDonor($donor['email'], [
                    'donor_name' => (string)($donor['username'] ?? 'Donor'),
                ] + $completionData);
            }
        }

        return ['success' => true];
    }

    /**
     * Assign a donor to a blood request.
     *
     * Business rules:
     * - Request must exist
     * - Donor must exist
     * - Donor must be eligible (not already assigned to this request)
     * - Notifications are sent to donor, patient, and admins
     */
    public function assignDonor(int $requestId, int $donorId): array
    {
        $request = $this->bloodRequestRepo->findById($requestId);

        if (!$request) {
            return ['success' => false, 'error' => 'Blood request not found.'];
        }

        $donor = $this->userRepo->findById($donorId);
        if (!$donor) {
            return ['success' => false, 'error' => 'Donor not found.'];
        }

        // Business rule: donor must not already be assigned to this request
        if ((int)($request['donor_id'] ?? 0) === $donorId) {
            return ['success' => false, 'error' => 'This donor is already assigned to this request.'];
        }

        // Business rule: donor must not be assigned to another active request
        // unless the new request has higher urgency — then auto-reassign.
        $newRequestRank = $this->prioritizationService->rank((string)($request['urgency'] ?? ''));
        $assignedGroups = $this->bloodRequestRepo->getDonorsAssignedToOtherRequests([$donorId], $requestId);
        if (!empty($assignedGroups)) {
            $otherRequests = $assignedGroups[$donorId] ?? [];
            foreach ($otherRequests as $other) {
                $existingRank = $this->prioritizationService->rank((string)($other['urgency'] ?? ''));
                if ($newRequestRank < $existingRank) {
                    $this->bloodRequestRepo->unassignDonorFromRequest((int)$other['request_id'], $donorId);
                    $this->notificationRepo->create(
                        (int)($other['patient_id'] ?? 0),
                        'Donor Reassigned',
                        sprintf(
                            'Donor %s has been reassigned from your blood request %s (%s) to a higher-priority request.',
                            $donor['username'] ?? 'Donor',
                            $other['request_code'] ?? '#' . $other['request_id'],
                            strtoupper($other['urgency'] ?? 'ROUTINE')
                        ),
                        'WARNING'
                    );
                    break;
                }
            }

            $stillAssigned = $this->bloodRequestRepo->getDonorsAssignedToOtherRequests([$donorId], $requestId);
            if (!empty($stillAssigned)) {
                $parts = [];
                foreach ($stillAssigned[$donorId] ?? [] as $other) {
                    $parts[] = ($other['request_code'] ?? '#' . $other['request_id']) . ' (' . strtoupper($other['urgency'] ?? 'ROUTINE') . ')';
                }
                return [
                    'success' => false,
                    'error' => sprintf(
                        'This donor is already assigned to: %s. Request urgency is equal or higher — unassign manually first.',
                        implode(', ', $parts)
                    ),
                ];
            }
        }

        $assignedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ASSIGNED') ?? 42;

        $updated = $this->bloodRequestRepo->acceptByAdmin($requestId, $donorId, $assignedStatus);
        if (!$updated) {
            return ['success' => false, 'error' => 'Failed to assign donor.'];
        }

        // Notify admins
        $admins = $this->notificationRepo->getAdmins();
        foreach ($admins as $admin) {
            $this->notificationRepo->create(
                (int)$admin['user_id'],
                'Donor Assigned by Admin',
                sprintf(
                    'Blood request %s has been assigned to donor %s by an admin and is waiting for donor acceptance.',
                    $request['request_code'] ?? 'N/A',
                    $donor['username'] ?? 'Unknown donor'
                ),
                'ADMIN_ACTION'
            );
        }

        // Notify donor
        $this->notificationRepo->create(
            (int)$donorId,
            'Blood Request Assigned',
            sprintf(
                'You have been assigned to blood request %s. Please review and accept it when ready.',
                $request['request_code'] ?? 'N/A'
            ),
            'REQUEST'
        );

        // Notify patient
        $patient = $this->userRepo->findById((int)($request['patient_id'] ?? 0));
        if ($patient) {
            $this->notificationRepo->create(
                (int)$patient['user_id'],
                'Blood Request Matched',
                sprintf(
                    'A donor has been assigned for your blood request %s and is pending acceptance.',
                    $request['request_code'] ?? 'N/A'
                ),
                'REQUEST'
            );
        }

        $assignmentData = [
            'patient_name'   => (string)($request['patient_name'] ?? 'A patient'),
            'blood_group'    => $this->masterRepo->getLabel('BLOOD_GROUP', (string)($request['blood_group_needed'] ?? '')) ?? ($request['blood_group_needed'] ?? ''),
            'hospital_name'  => (string)($request['hospital_name'] ?? 'the hospital'),
            'urgency'        => (string)($request['urgency'] ?? 'ROUTINE'),
            'request_code'   => (string)($request['request_code'] ?? ''),
        ];

        if (!empty($donor['email'])) {
            $this->emailService->sendDonorAssignedToDonor($donor['email'], [
                'donor_name' => (string)($donor['username'] ?? 'Donor'),
            ] + $assignmentData);
        }

        if ($patient && !empty($patient['email'])) {
            $this->emailService->sendDonorAssignedToPatient($patient['email'], $assignmentData);
        }

        return ['success' => true];
    }
}
