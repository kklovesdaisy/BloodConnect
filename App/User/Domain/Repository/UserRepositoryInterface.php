<?php

namespace App\User\Domain\Repository;

interface UserRepositoryInterface
{
    public function findById(int $id);
    public function update(int $id, array $data);
    public function findAll(): array;
    public function getAdmins(): array;
    public function getAdminEmails(): array;
    public function softDelete(int $id): bool;
    public function restore(int $id): bool;
    public function countDonorsByBloodGroup(): array;
}
