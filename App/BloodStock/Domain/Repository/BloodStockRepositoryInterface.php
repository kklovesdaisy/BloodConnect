<?php

namespace App\BloodStock\Domain\Repository;

interface BloodStockRepositoryInterface
{
    public function findAll(): array;

    public function find(string $bloodGroup): ?array;

    /**
     * Add bags to a blood group (creates the row if it does not exist yet).
     */
    public function increase(string $bloodGroup, int $bags): bool;

    /**
     * Atomically deduct bags only if enough stock is available.
     * Returns false when the group has fewer bags than requested.
     */
    public function decreaseIfAvailable(string $bloodGroup, int $bags): bool;

    /**
     * Apply a signed manual adjustment and record it in the audit trail.
     * Never lets stock fall below zero.
     */
    public function adjust(string $bloodGroup, int $delta, string $reason, int $actorId): bool;

    public function recentAdjustments(int $limit = 20): array;
}
