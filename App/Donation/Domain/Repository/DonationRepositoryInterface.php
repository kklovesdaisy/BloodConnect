<?php

namespace App\Donation\Domain\Repository;

interface DonationRepositoryInterface
{
    public function create(array $data): bool;

    /**
     * Record a standalone donation (not linked to a patient blood request).
     * request_id is stored as NULL; the number of bags donated is recorded so
     * the blood stock can be increased accordingly. Returns the new
     * donation_id, or null when the insert failed.
     */
    public function createStandalone(array $data): ?int;

    /**
     * Locate the donation row recorded against a patient blood request so its
     * id and donation date can be used to create the corresponding stock bags.
     */
    public function findByRequestId(int $requestId): ?array;

    public function findByDonor(int $donorId): array;
    public function updateStatusByRequestId(int $requestId, int $status): bool;
    public function countSuccessfulDonations(?int $statusId = null): int;
    /**
     * Locate a donor's donation eligible for a certificate. A certificate is
     * available as soon as a donation is recorded (ACCEPTED or COMPLETED),
     * so the donor can always view/download it right after donating.
     *
     * @param int[] $statuses certificate-eligible REQUEST_STATUS ids
     */
    public function findDonationForCertificate(int $donationId, int $donorId, array $statuses = []): ?array;

    /**
     * Total donated bags (completed donations) per donor blood group.
     * Returns a map of [blood_group => total_bags].
     */
    public function sumDonatedBagsByBloodGroup(int $completedStatus): array;
}
