<?php

namespace App\User\Presentation\Controller;

use App\Shared\Helpers\Session;
use App\Shared\Presentation\View\View;
use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\BloodRequest\Application\UseCase\CancelBloodRequestUseCase;
use App\BloodRequest\Application\UseCase\GenerateRequestReceiptUseCase;
use App\BloodRequest\Application\UseCase\ExpireOverdueRequestsUseCase;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Notification\Application\UseCase\SendProfileUpdateNotificationUseCase;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Helpers\Validator;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;

class PatientController
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private MasterDataRepository $masterRepo,
        private CancelBloodRequestUseCase $cancelUseCase,
        private SendProfileUpdateNotificationUseCase $profileUpdateNotifUseCase,
        private GenerateRequestReceiptUseCase $receiptUseCase,
        private ExpireOverdueRequestsUseCase $expireUseCase
    ) {}

    private function getUserId(): int
    {
        return (int) Session::get('user_id');
    }

    private function getUnreadCount(): int
    {
        return $this->notificationRepo->getUnreadCount($this->getUserId());
    }

    private function authGuard()
    {
        Session::start();

        if (!Session::has('user_id')) {
            header('Location: /BloodConnect/public/login');
            exit;
        }

        $this->expireUseCase->execute();
    }

    private function isCancelledRequest(array $request): bool
    {
        $status = strtolower((string)($request['status'] ?? $request['fulfillment_status'] ?? ''));

        return $status === 'cancelled'
            || $status === 'canceled'
            || (int)($request['status'] ?? 0) === 10;
    }

    private function filterRequests(array $requests, string $filter): array
    {
        $filter = strtolower(trim($filter));

        if ($filter === '' || $filter === 'all') {
            return $requests;
        }

        return array_values(array_filter($requests, function (array $request) use ($filter): bool {
            $status = strtolower(trim($request['status'] ?? ''));
            if ($filter === 'pending') {
                return in_array($status, ['pending', 'assigned', 'declined']);
            }
            return $status === $filter;
        }));
    }

    public function patient_dashboard()
    {
        $this->authGuard();

        $patientId = $this->getUserId();

        $allRequests = $this->bloodRequestRepo->findByPatientId($patientId);

        return View::render('User', 'patient_dashboard', [
            'username'            => Session::get('username'),
            'requests'            => array_slice($allRequests, 0, 6),
            'metrics'             => $this->bloodRequestRepo->getPatientStats($patientId),
            'unreadCount'         => $this->getUnreadCount(),
            'recent_notifications'=> array_slice($this->notificationRepo->findByUserId($patientId), 0, 3),
        ]);
    }

    public function myRequests()
    {
        $this->authGuard();

        $message = Session::get('flash_message', '');
        $status = Session::get('flash_status', '');
        Session::remove('flash_message');
        Session::remove('flash_status');

        $filter = strtolower((string)($_GET['filter'] ?? ''));
        $requests = $this->bloodRequestRepo->findByPatientId($this->getUserId());
        $requests = $this->filterRequests($requests, $filter);

        return View::render('User', 'myrequest', [
            'username'    => Session::get('username'),
            'requests'    => $requests,
            'unreadCount' => $this->getUnreadCount(),
            'message'     => $message,
            'status'      => $status,
            'filter'      => $filter,
            'pageTitle'   => match ($filter) {
                'pending'   => 'Pending Requests',
                'accepted'  => 'Accepted Requests',
                'cancelled' => 'Cancelled Requests',
                default     => 'My Requests',
            },
            'emptyMessage' => match ($filter) {
                'pending'   => 'You have no pending requests.',
                'accepted'  => 'You have no accepted requests.',
                'cancelled' => 'You have no cancelled requests.',
                default     => 'You have no requests recorded.',
            },
        ]);
    }

    public function cancelledRequests()
    {
        $this->authGuard();

        $message = Session::get('flash_message', '');
        $status = Session::get('flash_status', '');
        Session::remove('flash_message');
        Session::remove('flash_status');

        $requests = $this->bloodRequestRepo->findByPatientId($this->getUserId());
        $requests = $this->filterRequests($requests, 'cancelled');

        return View::render('User', 'myrequest', [
            'username'     => Session::get('username'),
            'requests'     => $requests,
            'unreadCount'  => $this->getUnreadCount(),
            'message'      => $message,
            'status'       => $status,
            'filter'       => 'cancelled',
            'pageTitle'    => 'Cancelled Requests',
            'emptyMessage' => 'You have no cancelled requests.',
        ]);
    }

    public function viewMyRequest()
    {
        $this->authGuard();

        $requestId = (int)($_GET['id'] ?? 0);

        if (!$requestId) {
            header('Location: /BloodConnect/public/patient/my-requests');
            exit;
        }

        $request = $this->bloodRequestRepo->findPatientRequestDetail($requestId, $this->getUserId());

        if (empty($request)) {
            header('Location: /BloodConnect/public/patient/my-requests');
            exit;
        }

        if (!empty($request['donor_id']) && empty($request['donor_name'])) {
            $donorUser = $this->userRepo->findById((int)$request['donor_id']);
            if ($donorUser) {
                $request['donor_name'] = $donorUser['username'] ?? 'Donor #' . $request['donor_id'];
                $request['donor_email'] = $donorUser['email'] ?? '';
                $request['donor_phone'] = $donorUser['phone'] ?? '';
                $request['donor_blood_group'] = $donorUser['blood_group'] ?? '';
                $request['donor_address'] = $donorUser['address'] ?? '';
            } else {
                $request['donor_name'] = 'Donor #' . $request['donor_id'];
                $request['donor_email'] = '';
                $request['donor_phone'] = '';
                $request['donor_blood_group'] = '';
                $request['donor_address'] = '';
            }
        }

        return View::render('User', 'request_detail', [
            'username'    => Session::get('username'),
            'request'     => $request,
            'unreadCount' => $this->getUnreadCount()
        ]);
    }

    public function downloadRequestReceipt()
    {
        $this->authGuard();

        $requestId = (int)($_GET['id'] ?? 0);

        if (!$requestId) {
            header('Location: /BloodConnect/public/patient/my-requests');
            exit;
        }

        $result = $this->receiptUseCase->execute($requestId, $this->getUserId());

        if (empty($result['success'])) {
            Session::set('flash_message', $result['error'] ?? 'Receipt could not be generated.');
            Session::set('flash_status', 'error');
            header('Location: /BloodConnect/public/patient/my-request/view?id=' . $requestId);
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . strlen($result['pdf']));
        echo $result['pdf'];
        exit;
    }

    public function profile()
    {
        $this->authGuard();

        return View::render('User', 'patientProfile', [
            'user'        => $this->userRepo->findById($this->getUserId()),
            'unreadCount' => $this->getUnreadCount()
        ]);
    }

    public function updateProfile()
    {
        $this->authGuard();

        $userId = $this->getUserId();

        $password = trim($_POST['password'] ?? $_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        $data = [
            'username'         => trim($_POST['username'] ?? ''),
            'email'            => trim($_POST['email'] ?? ''),
            'phone'            => trim($_POST['phone'] ?? ''),
            'blood_group'      => trim($_POST['blood_group'] ?? ''),
            'address'          => trim($_POST['address'] ?? ''),
            'password'         => $password,
            'confirm_password' => $confirmPassword,
        ];

        $validator = new Validator();

        $validator
            ->required('username', $data['username'])
            ->required('email', $data['email'])
            ->email('email', $data['email'])
            ->required('phone', $data['phone'])
            ->digits('phone', $data['phone'])
            ->lengthBetween('phone', $data['phone'], 11, 11)
            ->myanmarPhone('phone', $data['phone'])
            ->required('blood_group', $data['blood_group'])
            ->required('address', $data['address']);

        if ($validator->fails()) {
            print_r($validator->getErrors());
            exit;
        }

        if (!empty($data['password'])) {

            if ($data['password'] !== $data['confirm_password']) {
                die("Passwords do not match.");
            }

            $data['password'] = password_hash(
                $data['password'],
                PASSWORD_BCRYPT
            );
        } else {
            unset($data['password']);
        }

        if (!$this->userRepo->update($userId, $data)) {
            die("Update failed!");
        }

        Session::set('username', $data['username']);
        Session::set('email', $data['email']);

        $userSession = Session::get('user');
        if (is_array($userSession)) {
            $userSession['username'] = $data['username'];
            $userSession['email'] = $data['email'];
            Session::set('user', $userSession);
        }

        $this->profileUpdateNotifUseCase->execute($userId, $data['username']);

        header("Location: /BloodConnect/public/patient/profile");
        exit;
    }

    public function searchDonors()
    {
        $this->authGuard();

        return View::render('User', 'search_donors', [
            'username'    => Session::get('username'),
            'unreadCount' => $this->getUnreadCount()
        ]);
    }

    public function notifications()
    {
        $this->authGuard();

        $patientId = $this->getUserId();

        return View::render('User', 'notification', [
            'username'      => Session::get('username'),
            'notifications' => $this->notificationRepo->findByUserId($patientId),
            'unreadCount'   => $this->getUnreadCount()
        ]);
    }

    public function cancelRequest()
    {
        $this->authGuard();

        $requestId = (int)($_POST['request_id'] ?? 0);
        $patientId = $this->getUserId();

        if (!$requestId) {
            header('Location: /BloodConnect/public/patient/my-requests');
            exit;
        }

        $result = $this->cancelUseCase->execute($requestId, $patientId);

        if ($result['success']) {
            Session::set('flash_message', 'Your blood request has been cancelled.');
            Session::set('flash_status', 'success');
        } else {
            Session::set('flash_message', $result['error']);
            Session::set('flash_status', 'error');
        }

        header('Location: /BloodConnect/public/patient/my-requests');
        exit;
    }

    public function reactivateRequest()
    {
        $this->authGuard();

        $requestId = (int)($_POST['request_id'] ?? 0);
        $patientId = $this->getUserId();

        if (!$requestId) {
            header('Location: /BloodConnect/public/patient/my-requests');
            exit;
        }

        $result = $this->bloodRequestRepo->resetRequestToPending($requestId, $patientId);

        if ($result) {
            Session::set('flash_message', 'Your blood request has been re-submitted and is now pending again.');
            Session::set('flash_status', 'success');
        } else {
            Session::set('flash_message', 'Unable to re-activate this request. It may have already been updated.');
            Session::set('flash_status', 'error');
        }

        header('Location: /BloodConnect/public/patient/my-request/view?id=' . $requestId);
        exit;
    }

    public function updateProfilePage()
    {
        $this->authGuard();

        return View::render('User', 'update_profile', [
            'user' => $this->userRepo->findById($this->getUserId()),
            'unreadCount' => $this->getUnreadCount()
        ]);
    }

    public function markNotificationRead()
    {
        $this->authGuard();

        $id = (int)($_POST['notification_id'] ?? 0);

        $success = $this->notificationRepo->markAsRead($id);

        header('Content-Type: application/json');

        echo json_encode([
            "success" => $success,
            "count" => $this->notificationRepo->getUnreadCount($this->getUserId())
        ]);

        exit;
    }

    public function markAllNotificationsRead()
    {
        $this->authGuard();

        $success = $this->notificationRepo->markAllAsRead($this->getUserId());

        header('Content-Type: application/json');

        echo json_encode([
            "success" => $success,
            "count" => 0
        ]);

        exit;
    }

    public function unreadCount()
    {
        $this->authGuard();

        header('Content-Type: application/json');

        echo json_encode([
            'count' => $this->notificationRepo->getUnreadCount($this->getUserId())
        ]);

        exit;
    }

    public function deleteNotification()
    {
        $this->authGuard();

        $id = (int)($_POST['notification_id'] ?? 0);

        $success = $this->notificationRepo->deleteNotification($id, $this->getUserId());

        header('Content-Type: application/json');

        echo json_encode([
            "success" => $success,
            "count" => $this->notificationRepo->getUnreadCount($this->getUserId())
        ]);

        exit;
    }

    public function getPatientStats(int $patientId): array
    {
        return $this->bloodRequestRepo->getPatientStats($patientId);
    }
}

