<?php

namespace App\Donation\Domain\Repository;

interface DonationRepositoryInterface
{
    public function create(array $data): bool;
    public function findByDonor(int $donorId): array;
    public function updateStatusByRequestId(int $requestId, int $status): bool;
    public function countSuccessfulDonations(?int $statusId = null): int;
    public function findDonationForCertificate(int $requestId, int $donorId): ?array;
}
