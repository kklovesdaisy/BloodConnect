<?php

namespace App\Hospital\Application\UseCase;

use App\Hospital\Domain\Entity\HospitalBloodRequest;
use App\Hospital\Domain\Repository\HospitalBloodRequestRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Infrastructure\Activity\ActivityLogger;

/**
 * Declines a pending hospital stock request.
 *
 * The request is moved to DECLINED and no bags are allocated; it is no
 * longer eligible for auto-fulfillment.
 */
class DeclineStockRequestUseCase
{
    public function __construct(
        private HospitalBloodRequestRepositoryInterface $requestRepo,
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
            return ['success' => false, 'error' => 'Only pending requests can be declined.'];
        }

        if (!$this->requestRepo->decline($requestId)) {
            return ['success' => false, 'error' => 'Could not decline the request. It may have already been processed.'];
        }

        $hospitalUserId = (int)$request['hospital_user_id'];
        $group = (string)$request['blood_group'];
        $requested = (int)$request['quantity_requested'];

        $this->notificationRepo->create(
            $hospitalUserId,
            'Stock Request Declined',
            sprintf(
                'Your request %s (%s, %d bag(s)) was declined by the administrator.',
                $request['request_code'],
                $group,
                $requested
            ),
            'STOCK_REQUEST'
        );

        $this->activityLogger->log(
            $adminId,
            $adminName,
            'STOCK_REQUEST_DECLINED',
            sprintf(
                'Admin declined %s (%s, %d requested) for %s.',
                $request['request_code'],
                $group,
                $requested,
                $request['hospital_name'] ?? "user #{$hospitalUserId}"
            )
        );

        return ['success' => true, 'message' => 'Request declined.'];
    }
}
