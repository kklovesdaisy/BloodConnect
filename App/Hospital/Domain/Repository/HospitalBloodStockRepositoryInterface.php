<?php

namespace App\Hospital\Domain\Repository;

interface HospitalBloodStockRepositoryInterface
{
    /**
     * Per blood-group inventory currently held by a hospital: bags the
     * central bank allocated against this hospital's stock requests that
     * have not yet passed their expiry date.
     *
     * @return array<int, array{blood_group: string, available_bags: int, last_updated: string}>
     */
    public function findAllByHospital(int $hospitalUserId): array;

    /**
     * Expiry overview (expiring soon / oldest expiry) for the bags held by
     * a hospital, keyed by blood group.
     *
     * @return array<string, array{expiring_soon: int, expired: int, oldest_expiry: string}>
     */
    public function findExpiryOverviewByHospital(int $hospitalUserId): array;
}
