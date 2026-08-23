<?php

namespace App\Donor\Domain\Service;

class DonorEligibilityService
{
    private int $minAge = 18;
    private int $maxAge = 65;
    private int $minWeight = 50;

    public function evaluate(?string $dateOfBirth, ?string $weight, ?bool $isPregnant = null): array
    {
        $reasons = [];

        $ageValid = $this->isAgeValid($dateOfBirth, $reasons);
        $weightValid = $this->isWeightValid($weight, $reasons);
        $pregnancyValid = $isPregnant === null ? true : $this->isNotPregnant($isPregnant, $reasons);

        $eligible = $ageValid && $weightValid && $pregnancyValid;

        return [
            'eligible' => $eligible,
            'reasons' => $reasons,
            'message' => $eligible
                ? 'You meet all eligibility requirements.'
                : 'You do not meet all eligibility requirements: ' . implode(', ', $reasons),
        ];
    }

    public function isNotPregnant(bool $isPregnant, array &$reasons = []): bool
    {
        if ($isPregnant) {
            $reasons[] = 'Pregnant donors are not eligible to donate blood';
            return false;
        }

        return true;
    }

    public function isAgeValid(?string $dateOfBirth, array &$reasons = []): bool
    {
        if ($dateOfBirth === null || trim($dateOfBirth) === '') {
            $reasons[] = 'Date of birth is required';
            return false;
        }

        $dob = \DateTime::createFromFormat('Y-m-d', $dateOfBirth);
        if (!$dob) {
            $reasons[] = 'Invalid date of birth format';
            return false;
        }

        $now = new \DateTime();
        $age = (int)$dob->diff($now)->y;

        if ($age < $this->minAge) {
            $reasons[] = "You must be at least {$this->minAge} years old (current age: {$age})";
            return false;
        }

        if ($age > $this->maxAge) {
            $reasons[] = "You must be under {$this->maxAge} years old (current age: {$age})";
            return false;
        }

        return true;
    }

    public function isWeightValid(?string $weight, array &$reasons = []): bool
    {
        if ($weight === null || trim($weight) === '') {
            $reasons[] = 'Weight is required';
            return false;
        }

        $weightKg = (float)$weight;

        if ($weightKg < $this->minWeight) {
            $reasons[] = "Weight must be at least {$this->minWeight} kg (current: {$weightKg} kg)";
            return false;
        }

        return true;
    }

    public function getMinAge(): int
    {
        return $this->minAge;
    }

    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    public function getMinWeight(): int
    {
        return $this->minWeight;
    }
}
