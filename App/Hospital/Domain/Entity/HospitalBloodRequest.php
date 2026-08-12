<?php

namespace App\Hospital\Domain\Entity;

/**
 * Value object for a hospital blood-stock request.
 *
 * Each request tracks how many bags were allocated immediately from stock
 * (quantity_allocated) and how many are still owed to the hospital
 * (quantity_pending). When new blood arrives, pending shortfalls are
 * auto-fulfilled oldest-first per blood group.
 */
class HospitalBloodRequest
{
    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_PARTIAL   = 'PARTIAL';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_DECLINED  = 'DECLINED';

    private static array $validStatuses = [
        self::STATUS_PENDING,
        self::STATUS_PARTIAL,
        self::STATUS_COMPLETED,
        self::STATUS_DECLINED,
    ];

    public function __construct(
        private string $requestCode,
        private int $hospitalUserId,
        private ?string $hospitalName,
        private string $bloodGroup,
        private int $quantityRequested,
        private int $quantityAllocated,
        private int $quantityPending,
        private string $status
    ) {
        $this->requestCode = trim($requestCode);
        $this->hospitalName = $hospitalName !== null ? trim($hospitalName) : null;
        $this->bloodGroup = strtoupper(trim($bloodGroup));
        $this->quantityRequested = max(0, $quantityRequested);
        $this->quantityAllocated = max(0, $quantityAllocated);
        $this->quantityPending = max(0, $quantityPending);
    }

    public static function isOpenStatus(string $status): bool
    {
        return in_array($status, [self::STATUS_PENDING, self::STATUS_PARTIAL], true);
    }

    public function getRequestCode(): string
    {
        return $this->requestCode;
    }

    public function getHospitalUserId(): int
    {
        return $this->hospitalUserId;
    }

    public function getHospitalName(): ?string
    {
        return $this->hospitalName;
    }

    public function getBloodGroup(): string
    {
        return $this->bloodGroup;
    }

    public function getQuantityRequested(): int
    {
        return $this->quantityRequested;
    }

    public function getQuantityAllocated(): int
    {
        return $this->quantityAllocated;
    }

    public function getQuantityPending(): int
    {
        return $this->quantityPending;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
