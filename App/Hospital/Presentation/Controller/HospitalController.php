<?php

namespace App\Hospital\Presentation\Controller;

use App\Hospital\Application\UseCase\HospitalDashboardUseCase;
use App\Hospital\Application\UseCase\ManageHospitalProfileUseCase;
use App\Hospital\Application\UseCase\SubmitStockRequestUseCase;
use App\Hospital\Domain\Repository\HospitalBloodRequestRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Helpers\Session;
use App\Shared\Presentation\View\View;

class HospitalController
{
    public function __construct(
        private HospitalDashboardUseCase $dashboardUseCase,
        private SubmitStockRequestUseCase $submitStockRequestUseCase,
        private ManageHospitalProfileUseCase $profileUseCase,
        private HospitalBloodRequestRepositoryInterface $requestRepo,
        private NotificationRepositoryInterface $notificationRepo
    ) {}

    private function authGuard(): void
    {
        Session::start();

        if (!Session::has('user_id')) {
            header('Location: /BloodConnect/public/login');
            exit;
        }
    }

    private function getUserId(): int
    {
        return (int)Session::get('user_id');
    }

    private function renderHospitalView(string $view, array $data = []): void
    {
        $data['pending_count'] = $this->getPendingCount();
        View::render('Hospital', $view, $data);
    }

    private function getPendingCount(): int
    {
        try {
            return $this->requestRepo->countOpenByHospital($this->getUserId());
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function dashboard(): void
    {
        $this->authGuard();

        $dashboard = $this->dashboardUseCase->execute($this->getUserId());

        $this->renderHospitalView('hospital_dashboard', [
            'user' => Session::get('user'),
            'dashboard' => $dashboard,
        ]);
    }

    public function requests(): void
    {
        $this->authGuard();

        $requests = $this->requestRepo->findByHospital($this->getUserId());

        $message = Session::get('flash_message', '');
        $status = Session::get('flash_status', '');
        Session::remove('flash_message');
        Session::remove('flash_status');

        $this->renderHospitalView('my_requests', [
            'user' => Session::get('user'),
            'requests' => $requests,
            'message' => $message,
            'status' => $status,
        ]);
    }

    public function bloodStock(): void
    {
        $this->authGuard();

        $dashboard = $this->dashboardUseCase->execute($this->getUserId());

        $this->renderHospitalView('blood_stock', [
            'user' => Session::get('user'),
            'stock' => $dashboard['stock'],
        ]);
    }

    public function createRequestPage(): void
    {
        $this->authGuard();

        $stock = [];
        try {
            $repo = new \App\BloodStock\Infrastructure\Persistence\BloodStockRepository();
            $stock = $repo->findAll();
        } catch (\Throwable $e) {
            $stock = [];
        }

        $this->renderHospitalView('create_request', [
            'user' => Session::get('user'),
            'stock' => $stock,
        ]);
    }

    public function storeRequest(): void
    {
        $this->authGuard();

        $bloodGroup = trim((string)($_POST['blood_group'] ?? ''));
        $quantity = (int)($_POST['quantity'] ?? 0);

        $profile = (new \App\Hospital\Infrastructure\Persistence\HospitalRepository())->findByUserId($this->getUserId());

        $result = $this->submitStockRequestUseCase->execute(
            $this->getUserId(),
            $profile['hospital_name'] ?? null,
            $bloodGroup,
            $quantity
        );

        if (!$result['success']) {
            Session::set('flash_message', $result['error']);
            Session::set('flash_status', 'error');
        } else {
            Session::set('flash_message', $result['message']);
            Session::set('flash_status', 'success');
        }

        header('Location: /BloodConnect/public/hospital/requests');
        exit;
    }

    public function pending(): void
    {
        $this->authGuard();

        $open = array_values(array_filter(
            $this->requestRepo->findByHospital($this->getUserId()),
            fn(array $request) => ((int)($request['quantity_pending'] ?? 0) > 0)
                && in_array($request['status'] ?? '', ['PENDING', 'PARTIAL'], true)
        ));

        $this->renderHospitalView('pending_requests', [
            'user' => Session::get('user'),
            'requests' => $open,
            'pending_total' => $this->requestRepo->sumPendingByHospital($this->getUserId()),
        ]);
    }

    public function profile(): void
    {
        $this->authGuard();

        $profile = $this->profileUseCase->getProfile($this->getUserId());

        $this->renderHospitalView('hospital_profile', [
            'user' => Session::get('user'),
            'profile' => $profile,
        ]);
    }

    public function updateProfilePage(): void
    {
        $this->authGuard();

        $profile = $this->profileUseCase->getProfile($this->getUserId());

        $this->renderHospitalView('update_profile', [
            'user' => Session::get('user'),
            'profile' => $profile,
        ]);
    }

    public function updateProfile(): void
    {
        $this->authGuard();

        $data = [
            'hospital_name' => trim((string)($_POST['hospital_name'] ?? '')),
            'license_no'    => trim((string)($_POST['license_no'] ?? '')),
            'state_region'  => trim((string)($_POST['state_region'] ?? '')),
            'township'      => trim((string)($_POST['township'] ?? '')),
            'address'       => trim((string)($_POST['address'] ?? '')),
            'phone'         => trim((string)($_POST['phone'] ?? '')),
        ];

        $this->profileUseCase->updateProfile($this->getUserId(), $data);

        Session::set('flash_message', 'Hospital profile updated successfully.');
        Session::set('flash_status', 'success');

        header('Location: /BloodConnect/public/hospital/profile');
        exit;
    }

    public function notifications(): void
    {
        $this->authGuard();

        $this->renderHospitalView('notifications', [
            'user' => Session::get('user'),
            'notifications' => $this->notificationRepo->findByUserId($this->getUserId()),
            'unreadCount' => $this->notificationRepo->getUnreadCount($this->getUserId()),
        ]);
    }
}
