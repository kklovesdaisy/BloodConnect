<?php

namespace App\Hospital\Application\UseCase;

use App\Hospital\Domain\Repository\HospitalRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Infrastructure\Activity\ActivityLogger;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;

/**
 * Reads and updates the hospital's profile and account contact details.
 */
class ManageHospitalProfileUseCase
{
    public function __construct(
        private HospitalRepositoryInterface $hospitalRepo,
        private MasterDataRepository $masterRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private ActivityLogger $activityLogger
    ) {}

    public function getProfile(int $userId): array
    {
        $profile = $this->hospitalRepo->findByUserId($userId);

        if (!$profile) {
            $this->hospitalRepo->createProfile($userId, []);
            $profile = $this->hospitalRepo->findByUserId($userId) ?? [];
        }

        return $profile;
    }

    public function updateProfile(int $userId, array $data): array
    {
        $this->hospitalRepo->updateProfile($userId, $data);

        $profile = $this->hospitalRepo->findByUserId($userId);
        $hospitalName = (string)($profile['hospital_name'] ?? $data['hospital_name'] ?? '');

        $this->activityLogger->log(
            $userId,
            null,
            'HOSPITAL_PROFILE_UPDATED',
            "Hospital profile updated for user #{$userId}"
        );

        $this->notificationRepo->create(
            $userId,
            'Profile Updated',
            'Your hospital profile has been updated successfully.',
            'ADMIN_ACTION'
        );

        $this->notificationRepo->notifyAdmins(
            'Hospital Profile Updated',
            sprintf(
                'Hospital "%s" (user #%d) updated their profile.',
                $hospitalName !== '' ? $hospitalName : 'Unknown',
                $userId
            ),
            'PROFILE_UPDATE'
        );

        return ['success' => true];
    }
}
