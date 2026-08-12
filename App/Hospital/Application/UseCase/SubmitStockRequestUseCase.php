<?php

namespace App\Hospital\Application\UseCase;

use App\BloodStock\Domain\Entity\BloodStock;
use App\Hospital\Domain\Entity\HospitalBloodRequest;
use App\Hospital\Domain\Repository\HospitalBloodRequestRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Infrastructure\Activity\ActivityLogger;

/**
 * Submits a hospital blood-stock request.
 *
 * The request is created in PENDING state and no stock is deducted yet.
 * An administrator must explicitly accept or decline the request; on
 * acceptance the available stock is allocated and any remainder is tracked
 * as a pending shortfall that the auto-fulfillment flow closes later.
 */
class SubmitStockRequestUseCase
{
    private const MAX_BAGS = 100;

    private array $allowedGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function __construct(
        private HospitalBloodRequestRepositoryInterface $requestRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private ActivityLogger $activityLogger
    ) {}

    public function execute(int $hospitalUserId, ?string $hospitalName, string $bloodGroup, int $quantity): array
    {
        $group = BloodStock::normalize($bloodGroup);

        if (!in_array($group, $this->allowedGroups, true)) {
            return ['success' => false, 'error' => 'Please select a valid blood group.'];
        }

        if ($quantity < 1 || $quantity > self::MAX_BAGS) {
            return ['success' => false, 'error' => 'Quantity must be between 1 and ' . self::MAX_BAGS . ' bags.'];
        }

        $requestId = $this->requestRepo->create([
            'request_code'         => $this->generateCode(),
            'hospital_user_id'     => $hospitalUserId,
            'hospital_name'        => $hospitalName,
            'blood_group'          => $group,
            'quantity_requested'   => $quantity,
            'quantity_allocated'   => 0,
            'quantity_pending'     => 0,
            'status'               => HospitalBloodRequest::STATUS_PENDING,
        ]);

        if ($requestId <= 0) {
            return ['success' => false, 'error' => 'Could not save the stock request. Please try again.'];
        }

        $this->notificationRepo->create(
            $hospitalUserId,
            'Stock Request Submitted',
            sprintf(
                'Your request for %d %s bag(s) has been submitted and is awaiting admin approval.',
                $quantity,
                $group
            ),
            'STOCK_REQUEST'
        );

        $this->notificationRepo->notifyAdmins(
            'New Hospital Stock Request',
            sprintf(
                '%s requested %d %s bag(s) and is awaiting approval.',
                $hospitalName ?: "Hospital #{$hospitalUserId}",
                $quantity,
                $group
            ),
            'STOCK_REQUEST'
        );

        $this->activityLogger->log(
            $hospitalUserId,
            $hospitalName,
            'STOCK_REQUEST_SUBMITTED',
            sprintf(
                'Hospital requested %d %s bag(s); awaiting admin approval.',
                $quantity,
                $group
            )
        );

        return [
            'success' => true,
            'message' => sprintf(
                'Your request for %d %s bag(s) was submitted and is awaiting admin approval.',
                $quantity,
                $group
            ),
            'status' => HospitalBloodRequest::STATUS_PENDING,
        ];
    }

    private function generateCode(): string
    {
        return 'HSR' . date('YmdHis') . str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }
}
