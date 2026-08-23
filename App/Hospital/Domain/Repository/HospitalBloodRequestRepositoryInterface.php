<?php

namespace App\Hospital\Domain\Repository;

interface HospitalBloodRequestRepositoryInterface
{
    /**
     * Insert a new stock request. Returns the new request id.
     */
    public function create(array $data): int;

    /**
     * Requests belonging to one hospital, newest first.
     */
    public function findByHospital(int $hospitalUserId): array;

    /**
     * A single request by id, or null when it does not exist.
     */
    public function findById(int $requestId): ?array;

    /**
     * All open requests (PENDING/PARTIAL) that still owe bags for a blood
     * group, ordered oldest-first so shortfalls are fulfilled fairly.
     */
    public function findOpenByBloodGroup(string $bloodGroup): array;

    /**
     * Count of open requests (with remaining shortfall) for one hospital.
     */
    public function countOpenByHospital(int $hospitalUserId): int;

    /**
     * Total bags still owed by one hospital across open requests.
     */
    public function sumPendingByHospital(int $hospitalUserId): int;

    /**
     * Count of hospital stock requests the system has fully completed
     * (each completed request counts once as a successful donation).
     */
    public function countCompletedRequests(): int;

    /**
     * Add bags to quantity_allocated and subtract the same amount from
     * quantity_pending.
     */
    public function allocateBags(int $requestId, int $bags): bool;

    /**
     * Mark a request fully fulfilled.
     */
    public function markCompleted(int $requestId): bool;

    /**
     * Approve a PENDING request: add allocated bags, set the remaining
     * pending quantity and move the request to PARTIAL or COMPLETED.
     */
    public function accept(int $requestId, int $bags, int $newPending, string $status): bool;

    /**
     * Decline a PENDING request (clears any pending quantity).
     */
    public function decline(int $requestId): bool;
}
