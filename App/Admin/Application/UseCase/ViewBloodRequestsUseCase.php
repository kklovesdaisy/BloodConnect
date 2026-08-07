<?php

namespace App\Admin\Application\UseCase;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;

class ViewBloodRequestsUseCase
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private MasterDataRepository $masterRepo
    ) {}

    public function getRequestDetail(int $requestId): ?array
    {
        return $this->bloodRequestRepo->findById($requestId);
    }

    public function getMatchingDonors(string $bloodGroup, ?string $township = null, ?string $stateRegion = null): array
    {
        return $this->bloodRequestRepo->getMatchingDonors($bloodGroup, $township, $stateRegion);
    }

    public function isRequestAccepted(array $request): bool
    {
        $acceptedStatusId = $this->getAcceptedStatusId();
        return ((int)($request['status'] ?? 0) === $acceptedStatusId)
            || (strtolower((string)($request['status_name'] ?? '')) === 'accepted');
    }

    public function isRequestAssigned(array $request): bool
    {
        $assignedStatusId = $this->getAssignedStatusId();
        return ((int)($request['status'] ?? 0) === $assignedStatusId)
            || (strtolower((string)($request['status_name'] ?? '')) === 'assigned');
    }

    public function getAssignedStatusId(): int
    {
        return $this->masterRepo->getId('REQUEST_STATUS', 'ASSIGNED') ?? 42;
    }

    public function getAcceptedStatusId(): int
    {
        return $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;
    }

    public function getPendingStatusId(): int
    {
        return $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;
    }

    public function isRequestCancelled(array $request): bool
    {
        $cancelledStatusId = $this->masterRepo->getId('REQUEST_STATUS', 'CANCELLED') ?? 10;
        return ((int)($request['status'] ?? 0) === $cancelledStatusId)
            || (strtolower((string)($request['status_name'] ?? '')) === 'cancelled');
    }

    public function isRequestDeclined(array $request): bool
    {
        $declinedStatusId = $this->getDeclinedStatusId();
        return ((int)($request['status'] ?? 0) === $declinedStatusId)
            || (strtolower((string)($request['status_name'] ?? '')) === 'declined');
    }

    public function isRequestExpired(array $request): bool
    {
        $expiredStatusId = $this->getExpiredStatusId();
        return ((int)($request['status'] ?? 0) === $expiredStatusId)
            || (strtolower((string)($request['status_name'] ?? '')) === 'expired');
    }

    public function getExpiredStatusId(): int
    {
        return $this->masterRepo->getId('REQUEST_STATUS', 'EXPIRED') ?? 48;
    }

    public function getDeclinedStatusId(): int
    {
        return $this->masterRepo->getId('REQUEST_STATUS', 'DECLINED') ?? 47;
    }

    public function assignDonorToRequest(int $requestId, int $donorId, int $statusId): bool
    {
        return $this->bloodRequestRepo->acceptByAdmin($requestId, $donorId, $statusId);
    }
}
