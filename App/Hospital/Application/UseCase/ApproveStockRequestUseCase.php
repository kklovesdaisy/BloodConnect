<?php

namespace App\Hospital\Application\UseCase;

use App\BloodStock\Domain\Repository\BloodStockRepositoryInterface;
use App\Hospital\Domain\Entity\HospitalBloodRequest;
use App\Hospital\Domain\Repository\HospitalBloodRequestRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Infrastructure\Activity\ActivityLogger;

/**
 * Approves a pending hospital stock request.
 *
 * Whatever bags are currently in stock for the requested group are allocated
 * immediately; if the request is not fully covered the remainder stays
 * pending (PARTIAL) and is fulfilled automatically as new blood arrives.
 */
class ApproveStockRequestUseCase
{
    public function __construct(
        private HospitalBloodRequestRepositoryInterface $requestRepo,
        private BloodStockRepositoryInterface $stockRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private ActivityLogger $activityLogger
    ) {}

    public function execute(int $requestId, int $adminId, ?string $adminName): array
    {
        $request = $this->requestRepo->findById($requestId);

        if ($request === null) {
            return ['success' => false, 'error' => 'Request not found.'];
        }

        if (($request['status'] ?? '') !== HospitalBloodRequest::STATUS_PENDING) {
            return ['success' => false, 'error' => 'This request is not awaiting approval.'];
        }

        $group = (string)$request['blood_group'];
        $requested = (int)$request['quantity_requested'];
        $available = (int)($this->stockRepo->find($group)['available_bags'] ?? 0);
        $alloc = min($requested, $available);

        if ($alloc > 0) {
            $this->stockRepo->decreaseIfAvailable($group, $alloc, $requestId);
        }

        $newPending = $requested - $alloc;
        $status = $newPending === 0
            ? HospitalBloodRequest::STATUS_COMPLETED
            : HospitalBloodRequest::STATUS_PARTIAL;

        if (!$this->requestRepo->accept($requestId, $alloc, $newPending, $status)) {
            return ['success' => false, 'error' => 'Could not approve the request. It may have already been processed.'];
        }

        $hospitalUserId = (int)$request['hospital_user_id'];

        $this->notificationRepo->create(
            $hospitalUserId,
            'Stock Request Approved',
            sprintf(
                'Your request %s (%s, %d bag(s)) was approved. %d bag(s) allocated; %d bag(s) will be provided as stock becomes available.',
                $request['request_code'],
                $group,
                $requested,
                $alloc,
                $newPending
            ),
            'STOCK_REQUEST'
        );

        $this->activityLogger->log(
            $adminId,
            $adminName,
            'STOCK_REQUEST_APPROVED',
            sprintf(
                'Admin approved %s (%s, %d requested) for %s; %d allocated, %d pending.',
                $request['request_code'],
                $group,
                $requested,
                $request['hospital_name'] ?? "user #{$hospitalUserId}",
                $alloc,
                $newPending
            )
        );

        $message = $newPending === 0
            ? sprintf('Request approved. %d %s bag(s) allocated from current stock.', $alloc, $group)
            : sprintf(
                'Request approved. %d of %d %s bag(s) allocated; %d bag(s) remain pending.',
                $alloc,
                $requested,
                $group,
                $newPending
            );

        return [
            'success'  => true,
            'message'  => $message,
            'allocated' => $alloc,
            'pending'  => $newPending,
            'status'   => $status,
        ];
    }
}
