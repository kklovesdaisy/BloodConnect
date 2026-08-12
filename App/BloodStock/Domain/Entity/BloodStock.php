<?php

namespace App\BloodStock\Domain\Entity;

/**
 * Value object for a single blood stock row.
 *
 * Blood groups are stored keyed by their human label form ('A+', 'O-', ...)
 * which is the format used by blood_requests.blood_group_needed. Legacy
 * master-data codes such as 'A_POS' are normalized to that label form.
 */
class BloodStock
{
    /**
     * Blood bags are normally usable for 42 days after collection, after
     * which they expire and must be disposed of.
     */
    public const SHELF_LIFE_DAYS = 42;

    /**
     * Bags whose expiry date is within this many days are flagged as
     * "expiring soon" on the admin inventory page.
     */
    public const EXPIRY_WARNING_DAYS = 7;

    private static array $codeToLabel = [
        'A_POS' => 'A+', 'A_NEG' => 'A-',
        'B_POS' => 'B+', 'B_NEG' => 'B-',
        'AB_POS' => 'AB+', 'AB_NEG' => 'AB-',
        'O_POS' => 'O+', 'O_NEG' => 'O-',
    ];

    public function __construct(
        private string $bloodGroup,
        private int $availableBags,
        private ?string $lastUpdated = null
    ) {}

    public static function normalize(string $bloodGroup): string
    {
        $value = strtoupper(trim($bloodGroup));

        return self::$codeToLabel[$value] ?? $value;
    }

    public function getBloodGroup(): string
    {
        return $this->bloodGroup;
    }

    public function getAvailableBags(): int
    {
        return $this->availableBags;
    }

    public function getLastUpdated(): ?string
    {
        return $this->lastUpdated;
    }

    public function isLow(int $threshold = 2): bool
    {
        return $this->availableBags > 0 && $this->availableBags <= $threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->availableBags <= 0;
    }
}
