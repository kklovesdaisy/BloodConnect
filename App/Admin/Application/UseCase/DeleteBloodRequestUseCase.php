<?php

namespace App\Admin\Application\UseCase;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;
use App\User\Domain\Repository\UserRepositoryInterface;

/**
 * Encapsulates deletion of a blood request and the notifications that must be
 * sent when a PENDING request is removed (patient, matched donors, admins).
 *
 * Keeping this out of the controller honours the thin-controller principle and
 * makes the deletion behaviour unit-testable.
 */
class DeleteBloodRequestUseCase
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private MasterDataRepository $masterRepo
    ) {
    }

    public function execute(int $requestId): array
    {
        $request = $this->bloodRequestRepo->findById($requestId);

        if (!$request) {
            return ['success' => false, 'error' => 'Request not found.'];
        }

        $pendingStatusId = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;
        $isPending = (int)($request['status'] ?? 0) === $pendingStatusId;

        $deleted = $this->bloodRequestRepo->deleteRequest($requestId);

        if (!$deleted) {
            return ['success' => false, 'error' => 'Could not delete the request.'];
        }

        $this->notifyDeletion($request);

        return ['success' => true];
    }

    private function notifyDeletion(array $request): void
    {
        $patientId = (int)($request['patient_id'] ?? 0);
        $donorId = (int)($request['donor_id'] ?? 0);
        $requestCode = (string)($request['request_code'] ?? 'Unknown');
        $patientName = (string)($request['patient_name'] ?? 'A patient');
        $bloodGroup = (string)($request['blood_group_needed'] ?? '');
        $requestId = (int)($request['request_id'] ?? 0);

        $donorIds = [];

        if ($donorId > 0) {
            $donorIds[] = $donorId;
        }

        if ($requestId > 0) {
            $assignedDonors = $this->bloodRequestRepo->getAssignedDonors($requestId);
            foreach ($assignedDonors as $ad) {
                $adId = (int)($ad['donor_id'] ?? 0);
                if ($adId > 0) {
                    $donorIds[] = $adId;
                }
            }
        }

        if ($bloodGroup !== '') {
            foreach ($this->bloodRequestRepo->getMatchingDonors($bloodGroup) as $matchedDonor) {
                $matchedDonorId = (int)($matchedDonor['user_id'] ?? 0);
                if ($matchedDonorId > 0) {
                    $donorIds[] = $matchedDonorId;
                }
            }
        }

        if ($patientId > 0) {
            $this->notificationRepo->create(
                $patientId,
                'Request Deleted',
                "Admin deleted your blood request {$requestCode}.",
                'REMINDER'
            );
        }

        foreach (array_unique(array_filter($donorIds)) as $notifyDonorId) {
            $this->notificationRepo->create(
                $notifyDonorId,
                'Request Deleted',
                "The blood request {$requestCode} for {$patientName} was removed by admin.",
                'REMINDER'
            );
        }

        foreach ($this->userRepo->getAdmins() as $admin) {
            $adminId = (int)($admin['user_id'] ?? 0);
            if ($adminId > 0) {
                $this->notificationRepo->create(
                    $adminId,
                    'Request Deleted',
                    "Blood request {$requestCode} was deleted by admin.",
                    'REMINDER'
                );
            }
        }
    }
}
