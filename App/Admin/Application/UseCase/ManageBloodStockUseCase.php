<?php

namespace App\Admin\Application\UseCase;

use App\BloodStock\Domain\Repository\BloodStockRepositoryInterface;
use App\Donation\Domain\Repository\DonationRepositoryInterface;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;

class ManageBloodStockUseCase
{
    public function __construct(
        private BloodStockRepositoryInterface $stockRepo,
        private DonationRepositoryInterface $donationRepo,
        private MasterDataRepository $masterRepo
    ) {}

    /**
     * Current inventory for every blood group (all groups are seeded, so the
     * admin page always shows the full set even when a group is at zero).
     *
     * Each row is enriched with the bags donated by donors and the bags
     * allocated to hospital requests for that group, plus an expiry overview
     * (bags expiring soon and expired bags awaiting disposal).
     */
    public function listAll(): array
    {
        // Move any bags past their 42-day shelf life into EXPIRED and off the
        // available count before the page is rendered.
        $this->stockRepo->expireDueBags();

        $rows = $this->stockRepo->findAll();
        $expiry = $this->stockRepo->findExpiryOverview();

        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 9;
        $donated = $this->donationRepo->sumDonatedBagsByBloodGroup($completedStatus);

        foreach ($rows as &$row) {
            $row['label'] = $this->masterRepo->getLabel('BLOOD_GROUP', (string)$row['blood_group'])
                ?? (string)$row['blood_group'];
            $row['donated_bags'] = (int)($donated[$row['blood_group']] ?? 0);
            $row['expiring_soon'] = (int)($expiry[$row['blood_group']]['expiring_soon'] ?? 0);
            $row['expired'] = (int)($expiry[$row['blood_group']]['expired'] ?? 0);
            $row['oldest_expiry'] = $expiry[$row['blood_group']]['oldest_expiry'] ?? null;
        }
        unset($row);

        return $rows;
    }

    /**
     * Expire any past-due bags, then permanently remove all expired bags from
     * the inventory ledger. Returns the number of bags disposed.
     */
    public function disposeExpiredBags(?int $createdBy = null): array
    {
        $disposed = $this->stockRepo->disposeExpiredBags($createdBy);

        return [
            'success'  => true,
            'disposed' => $disposed,
        ];
    }
}
