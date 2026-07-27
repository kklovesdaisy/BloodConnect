<?php

namespace App\Donor\Domain\Service;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;

class DonorLocationMatchingService
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo
    ) {}

    public function findMatches(string $bloodGroup, ?string $township, ?string $stateRegion): array
    {
        $townshipMatches = [];
        $regionMatches = [];
        $allMatches = [];

        if ($township !== null && $township !== '') {
            $townshipMatches = $this->bloodRequestRepo->findDonorsByBloodGroupAndLocation(
                $bloodGroup, $township, null
            );
        }

        $townshipIds = array_column($townshipMatches, 'user_id');

        if ($stateRegion !== null && $stateRegion !== '') {
            $regionMatches = $this->bloodRequestRepo->findDonorsByBloodGroupAndLocation(
                $bloodGroup, null, $stateRegion
            );
            $regionMatches = array_filter($regionMatches, fn($d) => !in_array((int)($d['user_id'] ?? 0), $townshipIds, true));
            $regionMatches = array_values($regionMatches);
        }

        $regionIds = array_column($regionMatches, 'user_id');
        $excludeIds = array_merge($townshipIds, $regionIds);

        $allMatches = $this->bloodRequestRepo->findDonorsByBloodGroupAndLocation(
            $bloodGroup, null, null
        );
        if (!empty($excludeIds)) {
            $allMatches = array_filter($allMatches, fn($d) => !in_array((int)($d['user_id'] ?? 0), $excludeIds, true));
            $allMatches = array_values($allMatches);
        }

        return [
            'tier' => !empty($townshipMatches) ? 'township'
                   : (!empty($regionMatches) ? 'region'
                   : (!empty($allMatches) ? 'all' : 'none')),
            'township_matches' => $townshipMatches,
            'region_matches'   => $regionMatches,
            'all_matches'      => $allMatches,
        ];
    }
}
