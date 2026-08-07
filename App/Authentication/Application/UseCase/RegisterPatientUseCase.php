<?php

namespace App\Authentication\Application\UseCase;

use App\Authentication\Application\DTO\RegisterPatientDTO;
use App\Authentication\Domain\Repository\AuthRepositoryInterface;
use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Shared\Infrastructure\Mail\EmailService;

class RegisterPatientUseCase
{
    public function __construct(
        private AuthRepositoryInterface $repo,
        private EmailService $emailService,
        private DonorRepositoryInterface $donorRepo
    ) {}

    public function execute(
        RegisterPatientDTO $dto,
        int $userTypeId,
        int $statusId,
        int $otp,
        string $expiresAt
    ): array {

        if ($this->repo->findByEmail($dto->email)) {
            throw new \DomainException("Email already exists");
        }

        $passwordHash = password_hash($dto->password, PASSWORD_BCRYPT);

        $this->emailService->sendOtp($dto->email, $otp, $dto->username);

        return [
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'password_hash' => $passwordHash,
        ];
    }

    public function finalizeRegistration(
        RegisterPatientDTO $dto,
        int $userTypeId,
        int $statusId,
        string $passwordHash
    ): string {
        $userId = $this->repo->createPatient(
            $dto,
            $userTypeId,
            $statusId,
            0,
            '',
            $passwordHash
        );

        if ($userTypeId === 2) {
            $this->donorRepo->createDonorProfile((int)$userId);
        }

        return $userId;
    }
}
