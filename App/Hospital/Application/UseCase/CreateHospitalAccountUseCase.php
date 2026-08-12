<?php

namespace App\Hospital\Application\UseCase;

use App\Authentication\Domain\Repository\AuthRepositoryInterface;
use App\Hospital\Domain\Repository\HospitalRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Infrastructure\Activity\ActivityLogger;
use App\Shared\Infrastructure\Mail\EmailService;

/**
 * Creates a hospital account on behalf of an administrator.
 *
 * Hospital accounts are admin-created only (no self-registration). A users row
 * with user_type_id = 4 plus the hospitals profile row are created together in
 * a transaction; the hospital staff are emailed their temporary credentials.
 */
class CreateHospitalAccountUseCase
{
    public function __construct(
        private HospitalRepositoryInterface $hospitalRepo,
        private AuthRepositoryInterface $authRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private ActivityLogger $activityLogger,
        private EmailService $emailService
    ) {}

    public function execute(array $data, ?int $adminId = null, ?string $adminName = null): array
    {
        $username = trim((string)($data['username'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $hospitalName = trim((string)($data['hospital_name'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($username === '' || $email === '' || $hospitalName === '') {
            return ['success' => false, 'error' => 'Username, email and hospital name are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Please provide a valid email address.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters.'];
        }

        if ($this->authRepo->findByEmail($email)) {
            return ['success' => false, 'error' => 'A user with this email already exists.'];
        }

        if ($this->authRepo->findByUsername($username)) {
            return ['success' => false, 'error' => 'This username is already taken.'];
        }

        $statusId = $this->getActiveStatusId();
        $userId = $this->hospitalRepo->createAccount(
            array_merge($data, ['email' => $email]),
            password_hash($password, PASSWORD_BCRYPT),
            $statusId
        );

        if ($userId <= 0) {
            return ['success' => false, 'error' => 'Could not create the hospital account. Please try again.'];
        }

        $this->activityLogger->log(
            $adminId,
            $adminName,
            'HOSPITAL_CREATED',
            "Admin created hospital account \"{$hospitalName}\" (user #{$userId})"
        );

        $this->notificationRepo->notifyAdmins(
            'New Hospital Account',
            sprintf('Admin %s created hospital "%s" (%s).', $adminName ?? 'Admin', $hospitalName, $email),
            'ADMIN_ACTION',
            $adminId !== null ? (int)$adminId : null
        );

        $this->notificationRepo->create(
            $userId,
            'Welcome to BloodConnect',
            sprintf('Your hospital "%s" account has been created by an administrator. Please log in to get started.', $hospitalName),
            'ADMIN_ACTION'
        );

        $this->emailService->sendHospitalCredentials($email, $username, $password, $hospitalName);

        return ['success' => true, 'user_id' => $userId, 'message' => 'Hospital account created successfully.'];
    }

    private function getActiveStatusId(): int
    {
        $repo = new \App\Shared\Infrastructure\Persistence\MasterDataRepository();
        $id = $repo->getId('USER_STATUS', 'ACTIVE');

        return $id ?? 1;
    }
}
