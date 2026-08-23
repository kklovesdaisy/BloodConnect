<?php

namespace App\Hospital\Application\UseCase;

use App\BloodStock\Domain\Repository\BloodStockRepositoryInterface;
use App\Hospital\Domain\Entity\HospitalBloodRequest;
use App\Hospital\Domain\Repository\HospitalBloodRequestRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Infrastructure\Activity\ActivityLogger;

/**
 * Provides additional bags to an already-approved (PARTIAL) hospital stock
 * request using blood that is currently in stock.
 *
 * Any bags still owed (quantity_pending) are settled as far as current stock
 * allows; when the shortfall reaches zero the request is marked COMPLETED.
 * This is the manual counterpart to auto-fulfillment, letting an admin top up
 * a partial request without waiting for a new donation.
 */
class ProvideMoreStockUseCase
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

        if (($request['status'] ?? '') !== HospitalBloodRequest::STATUS_PARTIAL) {
            return ['success' => false, 'error' => 'Only partial requests can be provided with more bags.'];
        }

        $pending = (int)$request['quantity_pending'];
        if ($pending <= 0) {
            return ['success' => false, 'error' => 'This request is already fully fulfilled.'];
        }

        $group = (string)$request['blood_group'];
        $available = (int)($this->stockRepo->find($group)['available_bags'] ?? 0);

        if ($available <= 0) {
            return ['success' => false, 'error' => "No {$group} blood is currently in stock to provide."];
        }

        $alloc = min($pending, $available);

        if (!$this->stockRepo->decreaseIfAvailable($group, $alloc, $requestId)) {
            return ['success' => false, 'error' => 'Stock changed while processing. Please try again.'];
        }

        $this->requestRepo->allocateBags($requestId, $alloc);

        $newPending = $pending - $alloc;
        if ($newPending <= 0) {
            $this->requestRepo->markCompleted($requestId);
        }

        $hospitalUserId = (int)$request['hospital_user_id'];

        $this->notificationRepo->create(
            $hospitalUserId,
            'More Bags Provided',
            sprintf(
                'Your request %s (%s) received %d more bag(s) from current stock.',
                $request['request_code'],
                $group,
                $alloc
            ),
            'STOCK_REQUEST'
        );

        $hospitalName = $request['hospital_name'] ?? "user #{$hospitalUserId}";
        if ($newPending <= 0) {
            $adminMsg = sprintf(
                'Request %s for %s (%s) now fully fulfilled. %d bag(s) provided.',
                $request['request_code'],
                $hospitalName,
                $group,
                $alloc
            );
        } else {
            $adminMsg = sprintf(
                'Request %s for %s (%s): %d more bag(s) provided; %d bag(s) still pending.',
                $request['request_code'],
                $hospitalName,
                $group,
                $alloc,
                $newPending
            );
        }
        $this->notificationRepo->notifyAdmins(
            'Hospital Blood Request Top-Up',
            $adminMsg,
            'STOCK_REQUEST'
        );

        $this->activityLogger->log(
            $adminId,
            $adminName,
            'STOCK_REQUEST_TOP_UP',
            sprintf(
                'Admin provided %d extra %s bag(s) to %s (%s); %d still pending.',
                $alloc,
                $group,
                $request['hospital_name'] ?? "user #{$hospitalUserId}",
                $request['request_code'],
                $newPending
            )
        );

        $message = $newPending <= 0
            ? sprintf('Request fulfilled. %d %s bag(s) provided from current stock.', $alloc, $group)
            : sprintf(
                '%d %s bag(s) provided. %d bag(s) still owed to the hospital.',
                $alloc,
                $group,
                $newPending
            );

        return [
            'success'   => true,
            'message'   => $message,
            'allocated' => $alloc,
            'pending'   => $newPending,
        ];
    }
}
