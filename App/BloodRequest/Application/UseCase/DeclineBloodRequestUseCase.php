<?php

namespace App\BloodRequest\Application\UseCase;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Infrastructure\Activity\ActivityLogger;
use App\Shared\Infrastructure\Mail\EmailService;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;

class DeclineBloodRequestUseCase
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private ActivityLogger $activityLogger,
        private EmailService $emailService,
        private MasterDataRepository $masterRepo
    ) {}

    public function execute(int $requestId, int $donorId): array
    {
        $request = $this->bloodRequestRepo->findById($requestId);
        if (!$request) {
            return ['success' => false, 'error' => 'Blood request not found.'];
        }

        $donor = $this->userRepo->findById($donorId);
        $donorName = $donor['username'] ?? 'Unknown donor';
        $requestCode = $request['request_code'] ?? 'N/A';

        $this->bloodRequestRepo->updateDonorResponse($requestId, $donorId, 13);

        $this->bloodRequestRepo->resetRequestToDeclined($requestId);

        $this->activityLogger->log(
            $donorId,
            null,
            'REQUEST_DECLINED',
            "Donor declined blood request ID: {$requestId}"
        );

        $admins = $this->notificationRepo->getAdmins();
        foreach ($admins as $admin) {
            $this->notificationRepo->create(
                (int)$admin['user_id'],
                'Blood Request Declined',
                "Donor {$donorName} declined blood request {$requestCode}.",
                'REQUEST'
            );
        }

        if (!empty($request['patient_id'])) {
            $this->notificationRepo->create(
                (int)$request['patient_id'],
                'Blood Request Declined',
                "Donor {$donorName} declined your blood request {$requestCode}.",
                'REQUEST'
            );
        }

        $this->notificationRepo->create(
            $donorId,
            'Blood Request Declined',
            "You declined blood request {$requestCode}.",
            'REQUEST'
        );

        $declinedData = [
            'patient_name'   => (string)($request['patient_name'] ?? 'A patient'),
            'blood_group'    => $this->masterRepo->getLabel('BLOOD_GROUP', (string)($request['blood_group_needed'] ?? '')) ?? ($request['blood_group_needed'] ?? ''),
            'hospital_name'  => (string)($request['hospital_name'] ?? 'the hospital'),
            'request_code'   => $requestCode,
        ];

        if (!empty($request['patient_id'])) {
            $patient = $this->userRepo->findById((int)$request['patient_id']);
            if ($patient && !empty($patient['email'])) {
                $this->emailService->sendDonorDeclinedToPatient($patient['email'], $declinedData);
            }
        }

        $adminEmails = $this->userRepo->getAdminEmails();
        foreach ($adminEmails as $admin) {
            if (!empty($admin['email'])) {
                $this->emailService->sendDonorDeclinedToAdmin($admin['email'], [
                    'donor_name' => $donorName,
                ] + $declinedData);
            }
        }

        return ['success' => true];
    }
}
