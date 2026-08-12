<?php

namespace App\Donor\Presentation\Controller;

use App\Shared\Helpers\Permission;
use App\Shared\Helpers\Session;
use App\Shared\Presentation\View\View;
use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\BloodRequest\Application\UseCase\AcceptBloodRequestUseCase;
use App\BloodRequest\Application\UseCase\DeclineBloodRequestUseCase;
use App\BloodRequest\Application\UseCase\ExpireOverdueRequestsUseCase;
use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Donation\Domain\Repository\DonationRepositoryInterface;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;
use App\Donor\Domain\Service\DonorDonationEligibilityService;
use App\Donor\Application\UseCase\GetDonorProfileUseCase;
use App\Donor\Application\UseCase\GetDonorDashboardUseCase;
use App\Donor\Application\UseCase\GetDonationHistoryUseCase;
use App\Donor\Application\UseCase\UpdateDonorProfileUseCase;
use App\Donor\Application\UseCase\CompleteDonorProfileUseCase;
use App\Donor\Application\UseCase\RecordDonationUseCase;
use App\Donation\Application\UseCase\GenerateDonationCertificateUseCase;
use App\Donor\Domain\Service\DonorEligibilityService;

class DonorController
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private DonorRepositoryInterface $donorRepo,
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private DonationRepositoryInterface $donationRepo,
        private MasterDataRepository $masterRepo,
        private DonorDonationEligibilityService $eligibilityService,
        private AcceptBloodRequestUseCase $acceptUseCase,
        private DeclineBloodRequestUseCase $declineUseCase,
        private GetDonorProfileUseCase $getDonorProfileUseCase,
        private GetDonationHistoryUseCase $getDonationHistoryUseCase,
        private UpdateDonorProfileUseCase $updateDonorProfileUseCase,
        private CompleteDonorProfileUseCase $completeDonorProfileUseCase,
        private DonorEligibilityService $donorEligibilityService,
        private GetDonorDashboardUseCase $dashboardUseCase,
        private GenerateDonationCertificateUseCase $certificateUseCase,
        private ExpireOverdueRequestsUseCase $expireUseCase,
        private RecordDonationUseCase $recordDonationUseCase
    ) {}

    private function authGuard()
    {
        Session::start();

        if (!Session::has('user_id')) {
            header('Location: /BloodConnect/public/login');
            exit;
        }

        $this->expireUseCase->execute();
    }

    private function getUserId(): int
    {
        return (int) Session::get('user_id');
    }

    private function getUnreadCount(): int
    {
        return $this->notificationRepo->getUnreadCount($this->getUserId());
    }

    public function pendingBloodRequestsCount(): void
    {
        $this->authGuard();

        header('Content-Type: application/json');

        try {
            $user = Session::get('user');
            $bloodGroup = is_array($user) ? trim((string)($user['blood_group'] ?? '')) : '';

            if ($bloodGroup === '') {
                $donor = $this->donorRepo->findById($this->getUserId());
                $bloodGroup = trim((string)($donor['blood_group'] ?? ''));
            }

            if ($bloodGroup === '') {
                echo json_encode(['count' => 0]);
                exit;
            }

            $pendingRequests = $this->bloodRequestRepo->findPendingRequestsForDonor($bloodGroup, $this->getUserId());
            $totalPending = count($pendingRequests);
            $assignedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ASSIGNED') ?? 42;
            $assignedRequests = $this->bloodRequestRepo->findAssignedRequestsForDonor($this->getUserId(), $assignedStatus);
            $totalAssigned = count($assignedRequests);
            $acceptedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;
            $acceptedRequests = $this->bloodRequestRepo->findAcceptedRequestsForDonor($this->getUserId(), $acceptedStatus);
            $totalAccepted = count($acceptedRequests);

            $allRequestIds = array_merge(
                array_column($pendingRequests, 'request_id'),
                array_column($assignedRequests, 'request_id'),
                array_column($acceptedRequests, 'request_id')
            );
            $allRequestIds = array_unique(array_filter(array_map('intval', $allRequestIds)));

            $viewedIds = $_SESSION['donor_viewed_requests'] ?? [];
            $viewedIds = is_array($viewedIds) ? array_map('intval', $viewedIds) : [];
            $viewedCount = count(array_intersect($allRequestIds, $viewedIds));

            echo json_encode([
                'count' => max(0, ($totalPending + $totalAssigned + $totalAccepted) - $viewedCount)
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['count' => 0]);
        }
        exit;
    }

    /**
     * Renders a donor view through the shared donor layout, injecting the
     * pending blood-request badge count that the sidebar relies on. This keeps
     * the presentation layer free of direct repository instantiation.
     */
    private function renderDonorView(string $view, array $data = []): void
    {
        $data['pending_requests_count'] = $this->getPendingRequestsCount();
        View::render('Donor', $view, $data);
    }

    private function getPendingRequestsCount(): int
    {
        if (!Permission::can('blood_request.view_matching')) {
            return 0;
        }

        $user = Session::get('user');
        $bloodGroup = is_array($user) ? trim((string)($user['blood_group'] ?? '')) : '';

        if ($bloodGroup === '') {
            $donor = $this->donorRepo->findById($this->getUserId());
            $bloodGroup = trim((string)($donor['blood_group'] ?? ''));
        }

        if ($bloodGroup === '') {
            return 0;
        }

        $pendingRequests = $this->bloodRequestRepo->findPendingRequestsForDonor($bloodGroup, $this->getUserId());
        $totalPending = count($pendingRequests);

        $assignedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ASSIGNED') ?? 42;
        $assignedRequests = $this->bloodRequestRepo->findAssignedRequestsForDonor($this->getUserId(), $assignedStatus);
        $totalAssigned = count($assignedRequests);

        $acceptedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;
        $acceptedRequests = $this->bloodRequestRepo->findAcceptedRequestsForDonor($this->getUserId(), $acceptedStatus);
        $totalAccepted = count($acceptedRequests);

        $allRequestIds = array_unique(array_filter(array_map('intval', array_merge(
            array_column($pendingRequests, 'request_id'),
            array_column($assignedRequests, 'request_id'),
            array_column($acceptedRequests, 'request_id')
        ))));

        $viewedIds = $_SESSION['donor_viewed_requests'] ?? [];
        $viewedIds = is_array($viewedIds) ? array_map('intval', $viewedIds) : [];
        $viewedCount = count(array_intersect($allRequestIds, $viewedIds));

        return max(0, ($totalPending + $totalAssigned + $totalAccepted) - $viewedCount);
    }





    public function donor_dashboard()
    {
        $this->authGuard();

        $user = Session::get('user');
        $bloodGroup = '';

        if (is_array($user)) {
            $bloodGroup = trim((string)($user['blood_group'] ?? ''));
        }

        if ($bloodGroup === '') {
            $donor = $this->donorRepo->findById((int)Session::get('user_id'));
            $bloodGroup = trim((string)($donor['blood_group'] ?? ''));
        }

        $dashboard = $this->dashboardUseCase->execute((int)Session::get('user_id'), $bloodGroup);

        $this->renderDonorView('donor_dashboard', array_merge($dashboard, [
            'user' => $user,
            'blood_group' => $bloodGroup,
        ]));
    }





    public function acceptRequest()
    {
        $this->authGuard();

        $donorId = (int)(Session::get('user')['user_id'] ?? 0);
        $requestId = (int)$_POST['request_id'];

        $result = $this->acceptUseCase->execute($requestId, $donorId);

        if (!$result['success']) {
            Session::set('flash_message', $result['error']);
            Session::set('flash_status', 'error');
            header('Location: /BloodConnect/public/donor/blood-requests');
            exit;
        }

        header('Location: /BloodConnect/public/donor/dashboard');
        exit;
    }






    public function declineRequest()
    {
        $this->authGuard();

        $donorId = (int)(Session::get('user')['user_id'] ?? 0);
        $requestId = (int)$_POST['request_id'];

        $result = $this->declineUseCase->execute($requestId, $donorId);

        if (!$result['success']) {
            echo $result['error'];
            exit;
        }

        header('Location: /BloodConnect/public/donor/dashboard');
        exit;
    }

    public function donatePage()
    {
        $this->authGuard();

        $donorId = (int)Session::get('user_id');
        $profile = $this->getDonorProfileUseCase->execute($donorId);

        $donation = $this->donationRepo->findByDonor($donorId);
        $lastDonationDate = !empty($donation[0]['donation_date']) ? (string)$donation[0]['donation_date'] : '';

        $eligibility = $this->eligibilityService->evaluate(
            $lastDonationDate,
            $profile['availability_state']['next_available_date']
        );
        $eligibility['next_available_date'] = $profile['availability_state']['next_available_date'] ?? '';

        $this->renderDonorView('donate', [
            'user' => Session::get('user'),
            'profile' => $profile,
            'eligibility' => $eligibility,
        ]);
    }

    public function storeDonation()
    {
        $this->authGuard();

        $donorId = (int)Session::get('user_id');
        $bloodGroup = trim((string)($_POST['blood_group'] ?? ''));
        $bags = (int)($_POST['bags'] ?? 1);

        if ($bloodGroup === '') {
            $user = Session::get('user');
            if (is_array($user)) {
                $bloodGroup = trim((string)($user['blood_group'] ?? ''));
            }
        }

        $result = $this->recordDonationUseCase->execute($donorId, $bloodGroup, $bags);

        if (!$result['success']) {
            Session::set('flash_message', $result['error']);
            Session::set('flash_status', 'error');
            header('Location: /BloodConnect/public/donor/donate');
            exit;
        }

        Session::set('flash_message', $result['message'] ?? 'Donation recorded successfully.');
        Session::set('flash_status', 'success');
        header('Location: /BloodConnect/public/donor/donate');
        exit;
    }

    public function profile()
    {
        $this->authGuard();

        $userId = (int)Session::get('user_id');
        $donorDetails = $this->donorRepo->getDonorDetails($userId);
        if (!$donorDetails) {
            $this->donorRepo->createDonorProfile($userId);
            $donorDetails = $this->donorRepo->getDonorDetails($userId);
        }

        $profile = $this->getDonorProfileUseCase->execute($userId);

        $this->renderDonorView('donor_profile', [
            'user' => Session::get('user'),
            'donorDetails' => $donorDetails,
            'availability' => $profile['availability_state']['available'] ? 'Available' : 'Unavailable',
            'availability_message' => $profile['availability_state']['available']
                ? $profile['eligibility']['message']
                : ($profile['availability_state']['next_available_date'] ? 'You will be eligible again after the waiting period.' : $profile['eligibility']['message']),
            'next_eligible_date' => $profile['availability_state']['available'] ? '' : ($profile['availability_state']['next_available_date'] ?? $profile['eligibility']['next_eligible_date'])
        ]);
    }

    public function bloodRequests()
    {
        $this->authGuard();

        $user = Session::get('user');
        $bloodGroup = '';

        if (is_array($user)) {
            $bloodGroup = trim((string)($user['blood_group'] ?? ''));
        }

        if ($bloodGroup === '') {
            $donor = $this->donorRepo->findById((int)Session::get('user_id'));
            $bloodGroup = trim((string)($donor['blood_group'] ?? ''));
        }

        $acceptedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;
        $assignedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ASSIGNED') ?? 42;
        $pendingRequests = $this->bloodRequestRepo->findPendingRequestsForDonor($bloodGroup, (int)Session::get('user_id'));
        $acceptedRequests = $this->bloodRequestRepo->findAcceptedRequestsForDonor((int)Session::get('user_id'), $acceptedStatus);
        $assignedRequests = $this->bloodRequestRepo->findAssignedRequestsForDonor((int)Session::get('user_id'), $assignedStatus);
        $declinedRequests = $this->bloodRequestRepo->findDeclinedRequestsForDonor((int)Session::get('user_id'));
        $combinedRequests = array_merge($pendingRequests, $assignedRequests, $acceptedRequests, $declinedRequests);

        $message = Session::get('flash_message', '');
        $status = Session::get('flash_status', '');
        Session::remove('flash_message');
        Session::remove('flash_status');

        $this->renderDonorView('blood_requests', [
            'user' => $user,
            'blood_group' => $bloodGroup,
            'requests' => $combinedRequests,
            'message' => $message,
            'status'  => $status,
        ]);
    }

    public function bloodRequestDetails(int $requestId)
    {
        $this->authGuard();

        $request = $this->bloodRequestRepo->findById($requestId);

        if (!$request) {
            die('Blood request not found.');
        }

        $pendingStatusId = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;
        $assignedStatusId = $this->masterRepo->getId('REQUEST_STATUS', 'ASSIGNED') ?? 42;
        $acceptedStatusId = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;
        $currentStatus = (int)($request['status'] ?? 0);

        if (in_array($currentStatus, [$pendingStatusId, $assignedStatusId, $acceptedStatusId], true)) {
            if (!isset($_SESSION['donor_viewed_requests']) || !is_array($_SESSION['donor_viewed_requests'])) {
                $_SESSION['donor_viewed_requests'] = [];
            }
            if (!in_array($requestId, $_SESSION['donor_viewed_requests'], true)) {
                $_SESSION['donor_viewed_requests'][] = $requestId;
            }
        }

        $donorId = (int)Session::get('user_id');
        $responseStatuses = $this->bloodRequestRepo->getDonorResponseStatuses($requestId, [$donorId]);
        $donorResponseStatus = $responseStatuses[$donorId] ?? 0;

        $this->renderDonorView('blood_request_details', [
            'user' => Session::get('user'),
            'request' => $request,
            'donor_response_status' => $donorResponseStatus,
        ]);
    }

    public function history()
    {
        $this->authGuard();

        $history = $this->getDonationHistoryUseCase->execute((int)Session::get('user_id'));
        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 9;

        $this->renderDonorView('donation_history', [
            'user' => Session::get('user'),
            'donations' => $history['donations'],
            'total_donations' => $history['total_donations'],
            'total_bags' => $history['total_bags'],
            'last_donation_date' => $history['last_donation_date'],
            'completed_status_id' => $completedStatus,
        ]);
    }

    public function viewHistory()
    {
        $this->authGuard();

        $requestId = (int)($_GET['id'] ?? 0);

        if (!$requestId) {
            header('Location: /BloodConnect/public/donor/history');
            exit;
        }

        $acceptedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;
        $request = $this->bloodRequestRepo->findDonorRequestDetail($requestId, (int)Session::get('user_id'), (int)$acceptedStatus);

        if (empty($request)) {
            header('Location: /BloodConnect/public/donor/history');
            exit;
        }

        $donorDetails = $this->donorRepo->getDonorDetails((int)Session::get('user_id'));

        $donation = $this->donationRepo->findByRequestId($requestId);
        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 9;
        $donationId = 0;
        if (!empty($donation) && (int)($donation['status'] ?? 0) === $completedStatus) {
            $donationId = (int)($donation['donation_id'] ?? 0);
        }

        $this->renderDonorView('donor_request_history_detail', [
            'user' => Session::get('user'),
            'request' => $request,
            'donation_id' => $donationId,
            'donorDetails' => $donorDetails,
        ]);
    }

    public function viewDonationCertificate()
    {
        $this->authGuard();

        $donationId = (int)($_GET['id'] ?? 0);

        if (!$donationId) {
            header('Location: /BloodConnect/public/donor/history');
            exit;
        }

        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 11;
        $donation = $this->donationRepo->findDonationForCertificate($donationId, $this->getUserId(), $completedStatus);

        if (empty($donation)) {
            Session::set('flash_message', 'No completed donation record found for this request.');
            Session::set('flash_status', 'error');
            header('Location: /BloodConnect/public/donor/history');
            exit;
        }

        $donation['certificate_number'] = 'BC-' . str_pad((string)(int)($donation['donation_id'] ?? 0), 6, '0', STR_PAD_LEFT);

        $this->renderDonorView('donor_certificate', [
            'user' => Session::get('user'),
            'donation' => $donation,
            'donation_id' => (int)$donation['donation_id'],
        ]);
    }

    public function downloadDonationCertificate()
    {
        $this->authGuard();

        $donationId = (int)($_GET['id'] ?? 0);

        if (!$donationId) {
            header('Location: /BloodConnect/public/donor/history');
            exit;
        }

        $result = $this->certificateUseCase->execute($donationId, $this->getUserId());

        if (empty($result['success'])) {
            Session::set('flash_message', $result['error'] ?? 'Certificate could not be generated.');
            Session::set('flash_status', 'error');
            header('Location: /BloodConnect/public/donor/history');
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . strlen($result['pdf']));
        echo $result['pdf'];
        exit;
    }

    public function updateProfilePage()
    {
        $this->authGuard();

        $userId = (int)Session::get('user_id');
        $donorDetails = $this->donorRepo->getDonorDetails($userId);
        if (!$donorDetails) {
            $this->donorRepo->createDonorProfile($userId);
            $donorDetails = $this->donorRepo->getDonorDetails($userId);
        }

        $this->renderDonorView('update_profile', [
            'user' => Session::get('user'),
            'donorDetails' => $donorDetails
        ]);
    }

    public function notifications()
    {
        $this->authGuard();

        $this->renderDonorView('notification', [
            'notifications' => $this->notificationRepo->findByUserId($this->getUserId()),
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
            'success' => $success,
            'count' => $this->notificationRepo->getUnreadCount($this->getUserId())
        ]);
        exit;
    }

    public function markAllNotificationsRead()
    {
        $this->authGuard();

        $success = $this->notificationRepo->markAllAsRead($this->getUserId());

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'count' => 0
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

    public function completeProfile()
    {
        $this->authGuard();

        $userId = (int)Session::get('user_id');
        $donorDetails = $this->donorRepo->getDonorDetails($userId);
        if (!$donorDetails) {
            $this->donorRepo->createDonorProfile($userId);
            $donorDetails = $this->donorRepo->getDonorDetails($userId);
        }
        $isUpdate = $donorDetails && !empty($donorDetails['date_of_birth']);

        $eligibility = null;
        if ($donorDetails && !empty($donorDetails['date_of_birth']) && !empty($donorDetails['weight'])) {
            $eligibility = $this->donorEligibilityService->evaluate(
                $donorDetails['date_of_birth'],
                $donorDetails['weight']
            );
        }

        $this->renderDonorView('complete_profile', [
            'user' => Session::get('user'),
            'donorDetails' => $donorDetails,
            'errors' => Session::get('errors', []),
            'old' => Session::get('old', []),
            'eligibility' => $eligibility,
            'isUpdate' => $isUpdate,
        ]);

        Session::remove('errors');
        Session::remove('old');
    }

    public function saveCompleteProfile()
    {
        $this->authGuard();

        $userId = (int)Session::get('user_id');
        $username = (string)Session::get('username');

        $result = $this->completeDonorProfileUseCase->execute($userId, $username, $_POST);

        if (!$result['success']) {
            Session::set('errors', $result['errors'] ?? []);
            Session::set('old', $_POST);
            header('Location: /BloodConnect/public/donor/complete-profile');
            exit;
        }

        // Update session weight info
        $userSession = Session::get('user');
        if (is_array($userSession)) {
            $userSession['weight'] = $_POST['weight'] ?? '';
            Session::set('user', $userSession);
        }

        header('Location: /BloodConnect/public/donor/dashboard');
        exit;
    }

    public function updateProfile()
    {
        $this->authGuard();

        $userId = (int)Session::get('user_id');

        $password = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if ($password !== '' && $password !== $confirmPassword) {
            die('Passwords do not match.');
        }

        $userSession = Session::get('user');
        if (!is_array($userSession)) {
            $userSession = [];
        }

        $existingBloodGroup = trim((string)($userSession['blood_group'] ?? ''));

        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'blood_group' => $existingBloodGroup,
            'address' => trim($_POST['address'] ?? ''),
        ];

        if ($password !== '') {
            $data['password'] = $password;
        }

        $result = $this->updateDonorProfileUseCase->execute($userId, $data);

        if (!$result['success']) {
            die($result['error']);
        }

        // Save donor-specific fields (DOB if not set, weight/location always updatable)
        $donorDetails = $this->donorRepo->getDonorDetails($userId);
        $donorSaveData = [];
        if (!empty($_POST['date_of_birth']) && empty($donorDetails['date_of_birth'])) {
            $donorSaveData['date_of_birth'] = trim($_POST['date_of_birth']);
        }
        if (isset($_POST['weight']) && $_POST['weight'] !== '') {
            $weightValue = trim($_POST['weight']);
            if (!is_numeric($weightValue) || (float)$weightValue <= 0) {
                die('Please enter a valid weight.');
            }
            $donorSaveData['weight'] = $weightValue;
        }
        if (isset($_POST['state_region']) && $_POST['state_region'] !== '') {
            $donorSaveData['state_region'] = trim($_POST['state_region']);
        }
        if (isset($_POST['township']) && $_POST['township'] !== '') {
            $donorSaveData['township'] = trim($_POST['township']);
        }
        if (!empty($donorSaveData)) {
            $this->donorRepo->saveDonorDetails($userId, $donorSaveData);
            // Re-evaluate availability so a corrected weight flips the donor
            // back to Available when they meet the eligibility rules again.
            $this->donorRepo->syncAvailabilityStatus($userId);
        }

        Session::set('username', $data['username']);
        Session::set('email', $data['email']);

        $userSession = Session::get('user');
        if (!is_array($userSession)) {
            $userSession = [];
        }

        $userSession['username'] = $data['username'];
        $userSession['email'] = $data['email'];
        $userSession['phone'] = $data['phone'];
        $userSession['blood_group'] = $data['blood_group'];
        $userSession['address'] = $data['address'];

        Session::set('user', $userSession);

        header('Location: /BloodConnect/public/donor/profile');
        exit;
    }
}

