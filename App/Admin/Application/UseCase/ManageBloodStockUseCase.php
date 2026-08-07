<?php

namespace App\Admin\Application\UseCase;

use App\BloodStock\Domain\Repository\BloodStockRepositoryInterface;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;

class ManageBloodStockUseCase
{
    public function __construct(
        private BloodStockRepositoryInterface $stockRepo,
        private MasterDataRepository $masterRepo
    ) {}

    /**
     * Current inventory for every blood group (all groups are seeded, so the
     * admin page always shows the full set even when a group is at zero).
     */
    public function listAll(): array
    {
        $rows = $this->stockRepo->findAll();

        foreach ($rows as &$row) {
            $row['label'] = $this->masterRepo->getLabel('BLOOD_GROUP', (string)$row['blood_group'])
                ?? (string)$row['blood_group'];
        }
        unset($row);

        return $rows;
    }

    public function recentAdjustments(int $limit = 20): array
    {
        return $this->stockRepo->recentAdjustments($limit);
    }

    /**
     * Apply a manual stock adjustment. A positive delta adds bags, a negative
     * delta removes them; stock can never fall below zero.
     */
    public function adjust(string $bloodGroup, int $delta, string $reason, int $actorId): array
    {
        $bloodGroup = trim($bloodGroup);

        if ($bloodGroup === '') {
            return ['success' => false, 'error' => 'Please select a blood group.'];
        }

        if ($delta === 0) {
            return ['success' => false, 'error' => 'Adjustment quantity cannot be zero.'];
        }

        if (abs($delta) > 100) {
            return ['success' => false, 'error' => 'Adjustment quantity is too large (max 100 bags).'];
        }

        $applied = $this->stockRepo->adjust($bloodGroup, $delta, $reason, $actorId);

        if (!$applied) {
            return ['success' => false, 'error' => 'Adjustment failed: the stock level cannot go below zero.'];
        }

        $direction = $delta > 0 ? 'added to' : 'removed from';

        return [
            'success' => true,
            'message' => sprintf('%d bag(s) %s %s stock.', abs($delta), $direction, strtoupper($bloodGroup)),
        ];
    }
}
