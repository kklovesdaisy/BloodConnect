<?php

namespace App\BloodStock\Domain\Repository;

interface BloodStockRepositoryInterface
{
    public function findAll(): array;

    public function find(string $bloodGroup): ?array;

    /**
     * Add a batch of bags to a blood group (creates the row if it does not
     * exist yet). Each physical bag gets its own row in blood_bags with the
     * given collection date and an expiry date 42 days later.
     */
    public function recordBags(string $bloodGroup, int $bags, string $collectionDate, ?int $donationId = null): bool;

    /**
     * Atomically deduct bags only if enough stock is available, allocating the
     * oldest (soonest-expiring) available bags first (FEFO).
     * Returns false when the group has fewer bags than requested.
     */
    public function decreaseIfAvailable(string $bloodGroup, int $bags, ?int $requestId = null): bool;

    /**
     * Mark every still-available bag that has already passed its expiry date
     * as EXPIRED and remove those bags from the available count, logging each
     * removal to blood_stock_adjustments. Returns the number of bags expired.
     */
    public function expireDueBags(?int $createdBy = null): int;

    /**
     * Permanently remove expired bags from the inventory ledger (they are no
     * longer in blood_stock.available_bags). Returns the number disposed.
     */
    public function disposeExpiredBags(?int $createdBy = null): int;

    /**
     * Per-blood-group expiry overview: how many available bags are expiring
     * soon (within the warning window) and how many expired bags are still
     * held. Keyed by blood group.
     */
    public function findExpiryOverview(): array;

    /**
     * Per-blood-group count of bags currently allocated to (but not yet
     * released from) open stock requests. Keyed by blood group.
     */
    public function findReservedOverview(): array;

    /**
     * Most recent stock adjustment events (additions/removals), newest first.
     */
    public function findRecentAdjustments(int $limit = 6): array;
}
