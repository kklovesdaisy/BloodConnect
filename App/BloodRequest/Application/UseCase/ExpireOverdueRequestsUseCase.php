<?php

namespace App\BloodRequest\Application\UseCase;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Infrastructure\Mail\EmailService;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;

class ExpireOverdueRequestsUseCase
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private EmailService $emailService,
        private MasterDataRepository $masterRepo
    ) {}

    public function execute(): array
    {
        $overdue = $this->bloodRequestRepo->findOverdueRequests();

        if (empty($overdue)) {
            return ['expired' => 0, 'emails_sent' => 0];
        }

        $emailsSent = 0;

        foreach ($overdue as $req) {
            $requestCode = (string)($req['request_code'] ?? 'N/A');
            $patientId = (int)($req['patient_id'] ?? 0);
            $patientEmail = (string)($req['patient_email'] ?? '');
            $patientName = (string)($req['patient_name'] ?? 'A patient');

            $emailData = [
                'patient_name'   => $patientName,
                'blood_group'    => $this->masterRepo->getLabel('BLOOD_GROUP', (string)($req['blood_group_needed'] ?? '')) ?? ($req['blood_group_needed'] ?? ''),
                'hospital_name'  => (string)($req['hospital_name'] ?? 'the hospital'),
                'urgency'        => (string)($req['urgency'] ?? 'ROUTINE'),
                'request_code'   => $requestCode,
            ];

            if ($patientId > 0) {
                $this->notificationRepo->create(
                    $patientId,
                    'Blood Request Expired',
                    'Your blood request ' . $requestCode . ' has expired. Please submit a new request if blood is still required.',
                    'WARNING'
                );

                if ($patientEmail !== '') {
                    if ($this->emailService->sendRequestExpiredToPatient($patientEmail, $emailData)) {
                        $emailsSent++;
                    }
                }
            }

            $admins = $this->userRepo->getAdminEmails();
            foreach ($admins as $admin) {
                $adminId = (int)($admin['user_id'] ?? 0);
                if ($adminId > 0) {
                    $this->notificationRepo->create(
                        $adminId,
                        'Blood Request Expired',
                        'Blood request ' . $requestCode . ' for patient ' . $patientName . ' has expired.',
                        'WARNING'
                    );
                }

                if (!empty($admin['email'])) {
                    if ($this->emailService->sendRequestExpiredToAdmin($admin['email'], $emailData)) {
                        $emailsSent++;
                    }
                }
            }
        }

        $this->bloodRequestRepo->expireOverdueRequests();

        return ['expired' => count($overdue), 'emails_sent' => $emailsSent];
    }
}
