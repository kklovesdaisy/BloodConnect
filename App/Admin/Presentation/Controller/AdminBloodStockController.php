<?php

namespace App\Admin\Presentation\Controller;

use App\Admin\Application\UseCase\ManageBloodStockUseCase;
use App\Shared\Helpers\Session;

class AdminBloodStockController
{
    public function __construct(private ManageBloodStockUseCase $useCase) {}

    public function index(): void
    {
        $stock = $this->useCase->listAll();
        $adjustments = $this->useCase->recentAdjustments(20);

        ob_start();
        require __DIR__ . '/../View/blood_stock.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Layout/adminApp.php';
    }

    public function adjust(): void
    {
        $bloodGroup = trim((string)($_POST['blood_group'] ?? ''));
        $quantity = abs((int)($_POST['quantity'] ?? 0));
        $operation = ($_POST['operation'] ?? 'add') === 'deduct' ? 'deduct' : 'add';
        $reason = trim((string)($_POST['reason'] ?? ''));

        $delta = $operation === 'deduct' ? -$quantity : $quantity;

        $result = $this->useCase->adjust(
            $bloodGroup,
            $delta,
            $reason,
            (int)($_SESSION['user_id'] ?? 0)
        );

        Session::set('flash_message', $result['error'] ?? $result['message'] ?? '');
        Session::set('flash_status', $result['success'] ? 'success' : 'error');

        header('Location: /BloodConnect/public/admin/blood-stock');
        exit;
    }
}
