<?php

namespace App\Admin\Presentation\Controller;

use App\Hospital\Application\UseCase\ApproveStockRequestUseCase;
use App\Hospital\Application\UseCase\CreateHospitalAccountUseCase;
use App\Hospital\Application\UseCase\DeclineStockRequestUseCase;
use App\Hospital\Application\UseCase\ProvideMoreStockUseCase;
use App\Hospital\Domain\Repository\HospitalRepositoryInterface;
use App\Shared\Helpers\Session;

class AdminHospitalController
{
    public function __construct(
        private CreateHospitalAccountUseCase $createHospitalAccountUseCase,
        private HospitalRepositoryInterface $hospitalRepo,
        private ApproveStockRequestUseCase $approveStockRequestUseCase,
        private DeclineStockRequestUseCase $declineStockRequestUseCase,
        private ProvideMoreStockUseCase $provideMoreStockUseCase
    ) {}

    public function index(): void
    {
        $hospitals = $this->hospitalRepo->findAll();

        $this->render('hospital_management', [
            'hospitals' => $hospitals,
            'flash_message' => Session::get('flash_message', ''),
            'flash_status' => Session::get('flash_status', ''),
        ]);

        Session::remove('flash_message');
        Session::remove('flash_status');
    }

    public function create(): void
    {
        $this->render('create_hospital', [
            'errors' => Session::get('errors', []),
            'old' => Session::get('old', []),
        ]);

        Session::remove('errors');
        Session::remove('old');
    }

    public function store(): void
    {
        Session::start();
        $adminId = Session::get('user_id');
        $adminName = Session::get('username');

        $result = $this->createHospitalAccountUseCase->execute($_POST, $adminId, $adminName);

        if (!$result['success']) {
            Session::set('errors', ['form' => $result['error']]);
            Session::set('old', $_POST);
            header('Location: /BloodConnect/public/admin/hospitals/create');
            exit;
        }

        Session::set('flash_message', $result['message'] ?? 'Hospital account created successfully.');
        Session::set('flash_status', 'success');
        header('Location: /BloodConnect/public/admin/hospitals');
        exit;
    }

    public function hospitalRequests(): void
    {
        $this->render('hospital_requests', [
            'flash_message' => Session::get('flash_message', ''),
            'flash_status' => Session::get('flash_status', ''),
        ]);

        Session::remove('flash_message');
        Session::remove('flash_status');
    }

    public function acceptRequest(): void
    {
        Session::start();
        $requestId = (int)($_POST['request_id'] ?? 0);

        if ($requestId <= 0) {
            Session::set('flash_message', 'Invalid request.');
            Session::set('flash_status', 'error');
            header('Location: /BloodConnect/public/admin/hospital-requests');
            exit;
        }

        $result = $this->approveStockRequestUseCase->execute(
            $requestId,
            (int)Session::get('user_id'),
            Session::get('username')
        );

        Session::set('flash_message', $result['message'] ?? $result['error']);
        Session::set('flash_status', $result['success'] ? 'success' : 'error');

        header('Location: /BloodConnect/public/admin/hospital-requests');
        exit;
    }

    public function declineRequest(): void
    {
        Session::start();
        $requestId = (int)($_POST['request_id'] ?? 0);

        if ($requestId <= 0) {
            Session::set('flash_message', 'Invalid request.');
            Session::set('flash_status', 'error');
            header('Location: /BloodConnect/public/admin/hospital-requests');
            exit;
        }

        $result = $this->declineStockRequestUseCase->execute(
            $requestId,
            (int)Session::get('user_id'),
            Session::get('username')
        );

        Session::set('flash_message', $result['message'] ?? $result['error']);
        Session::set('flash_status', $result['success'] ? 'success' : 'error');

        header('Location: /BloodConnect/public/admin/hospital-requests');
        exit;
    }

    public function provideMore(): void
    {
        Session::start();
        $requestId = (int)($_POST['request_id'] ?? 0);

        if ($requestId <= 0) {
            Session::set('flash_message', 'Invalid request.');
            Session::set('flash_status', 'error');
            header('Location: /BloodConnect/public/admin/hospital-requests');
            exit;
        }

        $result = $this->provideMoreStockUseCase->execute(
            $requestId,
            (int)Session::get('user_id'),
            Session::get('username')
        );

        Session::set('flash_message', $result['message'] ?? $result['error']);
        Session::set('flash_status', $result['success'] ? 'success' : 'error');

        header('Location: /BloodConnect/public/admin/hospital-requests');
        exit;
    }

    public function pendingHospitalRequestsCount(): void
    {
        header('Content-Type: application/json');
        try {
            $db = \App\Shared\Infrastructure\Database\Database::getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM hospital_blood_requests WHERE status = 'PENDING'");
            $stmt->execute();

            echo json_encode(['count' => (int)$stmt->fetchColumn()]);
        } catch (\Throwable $e) {
            echo json_encode(['count' => 0]);
        }
        exit;
    }

    private function render(string $view, array $data = []): void
    {
        ob_start();
        extract($data);
        require __DIR__ . '/../View/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Layout/adminApp.php';
    }
}
