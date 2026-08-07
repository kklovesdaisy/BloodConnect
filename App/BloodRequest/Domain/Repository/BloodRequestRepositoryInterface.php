<?php

namespace App\BloodRequest\Domain\Repository;

interface BloodRequestRepositoryInterface
{
    public function findById(int $id);
    public function findByCode(string $requestCode): ?array;
    public function findByPatientId(int $patientId): array;
    public function findPatientRequestDetail(int $requestId, int $patientId): array;
    public function findAcceptedRequestsForDonor(int $donorId, int $acceptedStatus): array;
    public function findDonorRequestDetail(int $requestId, int $donorId, int $acceptedStatus): array;
    public function create(array $data): bool;
    public function findAll(): array;
    public function findOverdueRequests(): array;
    public function expireOverdueRequests(): int;
    public function findPendingRequestsForDonor(string $bloodGroup, int $excludeDonorId = 0): array;
    public function findCompetingRequests(string $bloodGroup, string $stateRegion, int $excludeRequestId): array;
    public function getMatchingDonors(string $bloodGroup, ?string $township = null, ?string $stateRegion = null): array;
    public function acceptByAdmin(int $requestId, int $donorId, int $statusId): bool;
    public function completeRequest(int $requestId, int $completedStatus): bool;
    public function getPatientStats(int $patientId): array;
    public function hasPendingRequest(int $patientId): bool;
    public function updateDonorDecision(int $requestId, int $donorId, int $statusId): bool;
    public function updateDonorResponse(int $requestId, int $donorId, int $responseStatusId): bool;
    public function cancelRequest(int $requestId, int $patientId, int $cancelledStatus, int $assignedStatus = 42): bool;
    public function deleteRequest(int $requestId): bool;
    public function countAcceptedByDonors(): int;
    public function findDonorsByBloodGroupAndLocation(string $bloodGroup, ?string $township = null, ?string $stateRegion = null): array;
    public function findBestDonorByLocation(string $bloodGroup, ?string $township, ?string $stateRegion): ?array;
    public function assignDonorsToRequest(int $requestId, array $donorIds, int $statusId): bool;
    public function getAssignedDonors(int $requestId): array;
    public function getDonorResponseStatuses(int $requestId, array $donorIds): array;
    public function getDonorsAssignedToOtherRequests(array $donorIds, int $excludeRequestId): array;
    public function unassignDonorFromRequest(int $requestId, int $donorId): bool;
    public function findAssignedRequestsForDonor(int $donorId, int $assignedStatus): array;
    public function countRequestsGroupedByDate(int $days): array;
    public function findLatest(int $limit): array;
    public function resetRequestToDeclined(int $requestId): bool;
    public function findDeclinedRequestsForDonor(int $donorId): array;
    public function resetRequestToPending(int $requestId, int $patientId): bool;
    public function resetRequestToPendingByAdmin(int $requestId): bool;
}
