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

        $totalBags = 0;
        $totalExpiringSoon = 0;
        $totalExpired = 0;

        foreach ($stock as $row) {
            $totalBags += (int)$row['available_bags'];
            $totalExpiringSoon += (int)($row['expiring_soon'] ?? 0);
            $totalExpired += (int)($row['expired'] ?? 0);
        }

        ob_start();
        require __DIR__ . '/../View/blood_stock.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Layout/adminApp.php';
    }

    public function disposeExpired(): void
    {
        Session::start();
        $adminId = (int)Session::get('user_id');

        $result = $this->useCase->disposeExpiredBags($adminId > 0 ? $adminId : null);

        if ($result['success']) {
            Session::set('flash_message', $result['disposed'] . ' expired bag(s) disposed from inventory.');
            Session::set('flash_status', 'success');
        } else {
            Session::set('flash_message', 'Could not dispose expired bags.');
            Session::set('flash_status', 'error');
        }

        header('Location: /BloodConnect/public/admin/blood-stock');
        exit;
    }
}
