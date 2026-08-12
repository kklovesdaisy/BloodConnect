<?php

namespace App\Hospital\Domain\Repository;

interface HospitalRepositoryInterface
{
    /**
     * Find a hospital profile row by its linked user id.
     */
    public function findByUserId(int $userId): ?array;

    /**
     * Create a hospital profile row for an existing user.
     */
    public function createProfile(int $userId, array $data): bool;

    /**
     * Update the hospital profile row (name/license/location/contact).
     */
    public function updateProfile(int $userId, array $data): bool;

    /**
     * Create a full hospital account: a users row (user_type_id = 4) plus the
     * hospitals profile row. Returns the new user id, or 0 on failure.
     */
    public function createAccount(array $data, string $passwordHash, int $statusId): int;

    /**
     * All hospital profiles joined with their account details, newest first.
     */
    public function findAll(): array;
}
