<?php

namespace App\Admin\Presentation\Controller;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\Donation\Domain\Repository\DonationRepositoryInterface;
use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;
use App\Admin\Application\UseCase\ViewBloodRequestsUseCase;
use App\Admin\Application\UseCase\ConfirmDonationUseCase;
use App\Admin\Application\UseCase\FindMatchingDonorsUseCase;
use App\Admin\Application\UseCase\AssignDonorsUseCase;
use App\Admin\Application\UseCase\DeleteBloodRequestUseCase;
use App\Admin\Application\UseCase\NotifyDonorsUseCase;
use App\BloodRequest\Application\UseCase\ExpireOverdueRequestsUseCase;

class AdminBloodRequestController
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private DonationRepositoryInterface $donationRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private DonorRepositoryInterface $donorRepository,
        private MasterDataRepository $masterRepo,
        private ViewBloodRequestsUseCase $viewUseCase,
        private ConfirmDonationUseCase $confirmUseCase,
        private FindMatchingDonorsUseCase $findMatchingUseCase,
        private AssignDonorsUseCase $assignUseCase,
        private DeleteBloodRequestUseCase $deleteUseCase,
        private NotifyDonorsUseCase $notifyUseCase,
        private ExpireOverdueRequestsUseCase $expireUseCase
    ) {}

    public function bloodRequests(): void
    {
        $this->expireUseCase->execute();

        $db = \App\Shared\Infrastructure\Database\Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM blood_requests WHERE status = 7");
        $stmt->execute();
        $_SESSION['pending_blood_requests_viewed_count'] = (int)$stmt->fetchColumn();
        ob_start();
        require __DIR__ . '/../View/blood_requests.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Layout/adminApp.php';
    }

    public function pendingBloodRequestsCount(): void
    {
        header('Content-Type: application/json');
        try {
            $db = \App\Shared\Infrastructure\Database\Database::getConnection();
            $pendingStatus = $this->viewUseCase->getPendingStatusId();

            $stmt = $db->prepare("SELECT COUNT(*) FROM blood_requests WHERE status = ?");
            $stmt->execute([$pendingStatus]);
            $totalPending = (int)$stmt->fetchColumn();

            $viewedIds = $_SESSION['admin_viewed_requests'] ?? [];
            $viewedIds = is_array($viewedIds) ? array_map('intval', $viewedIds) : [];

            $stmt2 = $db->prepare("SELECT request_id FROM blood_requests WHERE status = ?");
            $stmt2->execute([$pendingStatus]);
            $allPendingIds = array_map('intval', $stmt2->fetchAll(\PDO::FETCH_COLUMN) ?: []);
            $viewedCount = count(array_intersect($allPendingIds, $viewedIds));

            echo json_encode(['count' => max(0, $totalPending - $viewedCount)]);
        } catch (\Throwable $e) {
            echo json_encode(['count' => 0]);
        }
        exit;
    }

    public function donationHistory(): void
    {
        ob_start();
        require __DIR__ . '/../View/donation_history.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Layout/adminApp.php';
    }

    public function viewBloodRequest(): void
    {
        $this->expireUseCase->execute();

        $requestId = (int)($_GET['id'] ?? 0);

        if (!$requestId) {
            header('Location: /BloodConnect/public/admin/blood-requests');
            exit;
        }

        $request = $this->viewUseCase->getRequestDetail($requestId);

        if (!$request) {
            header('Location: /BloodConnect/public/admin/blood-requests');
            exit;
        }

        $pendingStatusId = $this->viewUseCase->getPendingStatusId();
        if ((int)$request['status'] === $pendingStatusId) {
            if (!isset($_SESSION['admin_viewed_requests']) || !is_array($_SESSION['admin_viewed_requests'])) {
                $_SESSION['admin_viewed_requests'] = [];
            }
            if (!in_array($requestId, $_SESSION['admin_viewed_requests'], true)) {
                $_SESSION['admin_viewed_requests'][] = $requestId;
            }
        }

        $isAccepted = $this->viewUseCase->isRequestAccepted($request);
        $isCancelledRequest = $this->viewUseCase->isRequestCancelled($request);
        $isDeclined = $this->viewUseCase->isRequestDeclined($request);
        $isExpired = $this->viewUseCase->isRequestExpired($request);

        $matchingResult = $this->findMatchingUseCase->execute($requestId);
        $matchingTier = $matchingResult['tier'] ?? 'none';
        $townshipDonors = $matchingResult['township_matches'] ?? [];
        $regionDonors = $matchingResult['region_matches'] ?? [];
        $allDonors = $matchingResult['all_matches'] ?? [];

        // Region-prioritized combined list: same township first, then same
        // region/state, then broadcast donors. Each donor is tagged with its
        // match tier so the admin is steered to assign from the same region.
        $tierRank = ['township' => 0, 'region' => 1, 'all' => 2];
        $tagged = [];
        foreach ($townshipDonors as $d) { $tagged[] = array_merge($d, ['match_tier' => 'township']); }
        foreach ($regionDonors as $d) { $tagged[] = array_merge($d, ['match_tier' => 'region']); }
        foreach ($allDonors as $d) { $tagged[] = array_merge($d, ['match_tier' => 'all']); }

        if (!empty($tagged)) {
            usort($tagged, fn($a, $b) => ($tierRank[$a['match_tier']] ?? 3) <=> ($tierRank[$b['match_tier']] ?? 3));
        }

        $donors = $tagged;

        $competingRequests = $matchingResult['competing_requests'] ?? [];

        $assignedDonors = $this->bloodRequestRepo->getAssignedDonors($requestId);

        $donorResponseMap = [];
        foreach ($assignedDonors as $ad) {
            $did = (int)($ad['donor_id'] ?? 0);
            if ($did > 0) {
                $donorResponseMap[$did] = (int)($ad['response_status_id'] ?? 11);
            }
        }

        $allMatchingDonorIds = array_filter(array_map(fn($d) => (int)($d['user_id'] ?? 0), $donors));
        if (!empty($allMatchingDonorIds)) {
            $responseStatuses = $this->bloodRequestRepo->getDonorResponseStatuses($requestId, $allMatchingDonorIds);
            foreach ($responseStatuses as $did => $rsid) {
                $donorResponseMap[$did] = $rsid;
            }
        }

        $acceptedDonor = null;
        $assignedDonor = null;

        $donorId = !empty($request['donor_id']) ? (int)$request['donor_id'] : 0;
        if ($donorId > 0) {
            $donorDetails = $this->donorRepository->getDonorDetails($donorId);
            $donorUser = $donorDetails ?: $this->userRepo->findById($donorId);
            $donorData = $donorUser ?: ['user_id' => $donorId, 'username' => 'Donor #' . $donorId, 'blood_group' => '', 'phone' => '', 'email' => ''];
            if ($isAccepted) {
                $acceptedDonor = $donorData;
            } else {
                $assignedDonor = $donorData;
            }
        }

        ob_start();
        require __DIR__ . '/../View/blood_request_detail.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Layout/adminApp.php';
    }

    public function acceptBloodRequest(): void
    {
        $this->expireUseCase->execute();

        $requestId = (int)($_POST['request_id'] ?? 0);
        $donorId = (int)($_POST['donor_id'] ?? 0);

        if (!$requestId || !$donorId) {
            header('Location: /BloodConnect/public/admin/blood-requests');
            exit;
        }

        $result = $this->confirmUseCase->assignDonor($requestId, $donorId);

        if (!$result['success']) {
            $_SESSION['flash_message'] = $result['error'];
            $_SESSION['flash_status'] = 'error';
            header('Location: /BloodConnect/public/admin/blood-request/view?id=' . $requestId);
            exit;
        }

        header('Location: /BloodConnect/public/admin/blood-requests?success=1');
        exit;
    }

    public function assignDonors(): void
    {
        $this->expireUseCase->execute();

        $requestId = (int)($_POST['request_id'] ?? 0);
        $donorIds = $_POST['donor_ids'] ?? [];

        if (!$requestId || empty($donorIds)) {
            $_SESSION['flash_message'] = 'No donors selected.';
            $_SESSION['flash_status'] = 'error';
            header('Location: /BloodConnect/public/admin/blood-request/view?id=' . $requestId);
            exit;
        }

        $donorIds = array_map('intval', (array)$donorIds);
        $donorIds = array_unique(array_filter($donorIds));

        if (empty($donorIds)) {
            $_SESSION['flash_message'] = 'No valid donors selected.';
            $_SESSION['flash_status'] = 'error';
            header('Location: /BloodConnect/public/admin/blood-request/view?id=' . $requestId);
            exit;
        }

        $result = $this->assignUseCase->execute($requestId, $donorIds);

        if (!$result['success']) {
            $_SESSION['flash_message'] = $result['error'];
            $_SESSION['flash_status'] = 'error';
            header('Location: /BloodConnect/public/admin/blood-request/view?id=' . $requestId);
            exit;
        }

        header('Location: /BloodConnect/public/admin/blood-requests?assigned=1');
        exit;
    }

    public function notifyDonors(): void
    {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $donorIds = $_POST['donor_ids'] ?? [];

        if (!$requestId) {
            header('Location: /BloodConnect/public/admin/blood-requests');
            exit;
        }

        $donorIds = array_map('intval', (array)$donorIds);
        $donorIds = array_unique(array_filter($donorIds));

        $result = $this->notifyUseCase->execute($requestId, $donorIds);

        if (!$result['success']) {
            $_SESSION['flash_message'] = $result['error'];
            $_SESSION['flash_status'] = 'error';
        } else {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_status'] = 'success';
        }

        header('Location: /BloodConnect/public/admin/blood-request/view?id=' . $requestId);
        exit;
    }

    public function completeRequest(): void
    {
        $requestId = (int)($_GET['id'] ?? 0);

        if (!$requestId) {
            header("Location: /BloodConnect/public/admin/blood-requests");
            exit;
        }

        $result = $this->confirmUseCase->complete($requestId);

        if (!$result['success']) {
            die($result['error']);
        }

        header("Location: /BloodConnect/public/admin/blood-requests");
        exit;
    }

    public function deleteBloodRequest(): void
    {
        $requestId = (int)($_POST['request_id'] ?? 0);

        if (!$requestId) {
            header('Location: /BloodConnect/public/admin/blood-requests?error=1');
            exit;
        }

        $result = $this->deleteUseCase->execute($requestId);

        if (!$result['success']) {
            header('Location: /BloodConnect/public/admin/blood-requests?error=1');
            exit;
        }

        header('Location: /BloodConnect/public/admin/blood-requests?deleted=1');
        exit;
    }

    public function resetToPending(): void
    {
        $requestId = (int)($_POST['request_id'] ?? 0);

        if (!$requestId) {
            header('Location: /BloodConnect/public/admin/blood-requests');
            exit;
        }

        $result = $this->bloodRequestRepo->resetRequestToPendingByAdmin($requestId);

        if ($result) {
            $_SESSION['flash_message'] = 'Request has been reset to Pending.';
            $_SESSION['flash_status'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Unable to reset this request. It may not be in Declined status.';
            $_SESSION['flash_status'] = 'error';
        }

        header('Location: /BloodConnect/public/admin/blood-request/view?id=' . $requestId);
        exit;
    }
}
