<?php

namespace App\Donor\Application\UseCase;

use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Donor\Domain\Service\DonorEligibilityService;
use App\Shared\Infrastructure\Activity\ActivityLogger;

class CompleteDonorProfileUseCase
{
    public function __construct(
        private DonorRepositoryInterface $donorRepo,
        private DonorEligibilityService $eligibilityService,
        private ActivityLogger $activityLogger
    ) {}

    public function execute(int $userId, string $username, array $data): array
    {
        $errors = [];

        $ageRaw = trim((string)($data['age'] ?? ''));
        $weight = trim((string)($data['weight'] ?? ''));
        $pregnancyStatus = strtolower(trim((string)($data['pregnancy_status'] ?? '')));

        if ($ageRaw === '') {
            $errors['age'] = 'Age is required.';
        } elseif (!ctype_digit($ageRaw) || (int)$ageRaw < 1 || (int)$ageRaw > 120) {
            $errors['age'] = 'Please enter a valid age between 1 and 120.';
        }

        if ($weight === '') {
            $errors['weight'] = 'Weight is required.';
        } elseif (!is_numeric($weight) || (float)$weight <= 0) {
            $errors['weight'] = 'Please enter a valid weight.';
        }

        $isPregnant = null;
        if ($pregnancyStatus === 'pregnant') {
            $isPregnant = true;
        } elseif ($pregnancyStatus === 'not_pregnant') {
            $isPregnant = false;
        } else {
            $errors['pregnancy_status'] = 'Please select Pregnant or Not Pregnant before saving.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Derive date of birth from the entered age (self-reported screening data).
        $dateOfBirth = date('Y-m-d', strtotime('-' . (int)$ageRaw . ' years'));

        $saveData = [
            'weight' => $weight,
            'date_of_birth' => $dateOfBirth,
            'is_pregnant' => $isPregnant,
        ];

        $stateRegion = trim((string)($data['state_region'] ?? ''));
        $township = trim((string)($data['township'] ?? ''));
        if ($stateRegion !== '') {
            $saveData['state_region'] = $stateRegion;
        }
        if ($township !== '') {
            $saveData['township'] = $township;
        }

        $saved = $this->donorRepo->saveDonorDetails($userId, $saveData);
        if (!$saved) {
            return ['success' => false, 'errors' => ['form' => 'Failed to save donor profile.']];
        }

        // Re-evaluate availability immediately: donors who fail the
        // eligibility requirements (age 18-65, weight >= 50kg, not pregnant)
        // must be marked unavailable right away.
        $this->donorRepo->syncAvailabilityStatus($userId);

        $eligibility = $this->eligibilityService->evaluate(
            $dateOfBirth,
            $weight,
            $isPregnant
        );

        $this->activityLogger->log(
            $userId,
            $username,
            'DONOR_PROFILE_COMPLETED',
            "Donor profile completed (Age: {$ageRaw}, Weight: {$weight}kg, Pregnant: " . ($isPregnant ? 'yes' : 'no') . ")"
        );

        return [
            'success' => true,
            'eligibility' => $eligibility,
        ];
    }
}
