<?php

namespace App\Authentication\Presentation\Controller;

use App\Authentication\Domain\Repository\AuthRepositoryInterface;
use App\Shared\Helpers\Session;
use App\Shared\Infrastructure\OAuth\GoogleOAuth;
use App\Shared\Infrastructure\Activity\ActivityLogger;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;

class GoogleAuthController
{
    public function __construct(
        private AuthRepositoryInterface $authRepo,
        private GoogleOAuth $googleOAuth,
        private ActivityLogger $activityLogger,
        private MasterDataRepository $masterRepo,
        private NotificationRepositoryInterface $notificationRepo
    ) {}
    public function redirect()
    {
        Session::start();

        if (!$this->googleOAuth->isConfigured()) {
            $_SESSION['errors']['form'] = 'Google sign-in is not configured yet. Please add valid Google OAuth credentials and the correct redirect URI in the environment file.';
            header('Location: /BloodConnect/public/login');
            exit;
        }

        header('Location: ' . $this->googleOAuth->getAuthorizationUrl());
        exit;
    }

    public function callback()
    {
        Session::start();

        $code = $_GET['code'] ?? '';
        $error = $_GET['error'] ?? '';

        if ($error || !$code) {
            header('Location: /BloodConnect/public/login');
            exit;
        }

        $tokenData = $this->googleOAuth->fetchAccessToken($code);
        if (!$tokenData) {
            $_SESSION['errors']['form'] = 'Failed to authenticate with Google. Please try again.';
            header('Location: /BloodConnect/public/login');
            exit;
        }

        $userInfo = $this->googleOAuth->fetchUserInfo($tokenData['access_token']);
        if (!$userInfo) {
            $_SESSION['errors']['form'] = 'Failed to fetch Google profile. Please try again.';
            header('Location: /BloodConnect/public/login');
            exit;
        }

        $googleId = $userInfo['id'] ?? '';
        $email = $userInfo['email'] ?? '';
        $name = $userInfo['name'] ?? '';
        $avatar = $userInfo['picture'] ?? '';

        if (!$email) {
            $_SESSION['errors']['form'] = 'Google account must have an email address.';
            header('Location: /BloodConnect/public/login');
            exit;
        }

        // Check if Google ID already exists
        $existingByGoogle = $this->authRepo->findByGoogleId($googleId);
        if ($existingByGoogle) {
            $this->loginUser($existingByGoogle);
            return;
        }

        // Check if email already exists
        $existingByEmail = $this->authRepo->findByEmailWithGoogle($email);
        if ($existingByEmail) {
            $this->authRepo->linkGoogleAccount((int)$existingByEmail['user_id'], $googleId, $avatar);
            $updatedUser = $this->authRepo->findByEmail($email);
            $this->loginUser($updatedUser);
            return;
        }

        // New user — store Google info in session and redirect to role selection
        Session::set('google_registration', [
            'google_id' => $googleId,
            'email'     => $email,
            'name'      => $name,
            'avatar'    => $avatar,
        ]);

        header('Location: /BloodConnect/public/auth/google/choose-role');
        exit;
    }

    public function chooseRole()
    {
        Session::start();

        $googleData = Session::get('google_registration');
        if (!$googleData) {
            header('Location: /BloodConnect/public/register');
            exit;
        }

        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        $viewFile = __DIR__ . '/../View/google_choose_role.php';
        $layout = __DIR__ . '/../../../Shared/Presentation/Layout/app.php';

        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        require $layout;
    }

    public function completeRegistration()
    {
        Session::start();

        $googleData = Session::get('google_registration');
        if (!$googleData) {
            header('Location: /BloodConnect/public/register');
            exit;
        }

        $role = $_POST['role'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $bloodGroup = trim($_POST['blood_group'] ?? '');

        if (!in_array($role, ['donor', 'patient'])) {
            $_SESSION['errors']['role'] = 'Please select a valid role.';
            header('Location: /BloodConnect/public/auth/google/choose-role');
            exit;
        }

        if (!$username) {
            $_SESSION['errors']['username'] = 'Username is required.';
            header('Location: /BloodConnect/public/auth/google/choose-role');
            exit;
        }

        if (!$bloodGroup || !in_array($bloodGroup, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])) {
            $_SESSION['errors']['blood_group'] = 'Please select your blood group.';
            header('Location: /BloodConnect/public/auth/google/choose-role');
            exit;
        }

        $userTypeId = $role === 'donor' ? 2 : 3;
        $statusId = 1;
        $pendingStatus = $this->masterRepo->getId('USER_STATUS', 'ACTIVE');
        if ($pendingStatus) {
            $statusId = (int)$pendingStatus;
        }

        $existingUser = $this->authRepo->findByUsername($username);
        if ($existingUser) {
            $_SESSION['errors']['username'] = 'Username is already taken.';
            header('Location: /BloodConnect/public/auth/google/choose-role');
            exit;
        }

        $this->authRepo->createGoogleUser(
            $username,
            $googleData['email'],
            $googleData['google_id'],
            $googleData['avatar'],
            $userTypeId,
            $statusId,
            $bloodGroup
        );

        Session::remove('google_registration');

        $user = $this->authRepo->findByEmail($googleData['email']);

        $roleLabel = ($userTypeId === 2) ? 'Donor' : 'Patient';
        $admins = $this->notificationRepo->getAdmins();
        foreach ($admins as $admin) {
            $this->notificationRepo->create(
                (int)$admin['user_id'],
                'New ' . $roleLabel . ' Registered (Google)',
                sprintf(
                    'A new %s "%s" has registered via Google Sign-In (ID: %s).',
                    strtolower($roleLabel),
                    $username,
                    $user['user_id'] ?? 'N/A'
                ),
                'USER_ACTION'
            );
        }

        $this->loginUser($user);
    }

    private function loginUser(array $user): void
    {
        Session::set('user', $user);
        Session::set('user_id', $user['user_id']);
        Session::set('username', $user['username']);
        Session::set('email', $user['email']);
        Session::set('user_type_id', $user['user_type_id']);

        $permissions = $this->authRepo->getPermissionsByUserType((int)$user['user_type_id']);
        Session::set('permissions', $permissions);

        $this->authRepo->setLoginStatus((int)$user['user_id'], 1);

        $this->activityLogger->log(
            $user['user_id'],
            $user['username'],
            'USER_LOGIN',
            $user['username'] . ' logged in to system'
        );

        $roleLabels = [1 => 'Admin', 2 => 'Donor', 3 => 'Patient', 4 => 'Hospital'];
        $roleLabel = $roleLabels[(int)($user['user_type_id'] ?? 0)] ?? 'User';
        $admins = $this->notificationRepo->getAdmins();
        foreach ($admins as $admin) {
            $this->notificationRepo->create(
                (int)$admin['user_id'],
                $roleLabel . ' Logged In (Google)',
                sprintf(
                    '%s "%s" has logged in to the system via Google Sign-In.',
                    $roleLabel,
                    $user['username'] ?? 'Unknown'
                ),
                'USER_ACTION'
            );
        }

        $redirectMap = [
            1 => '/BloodConnect/public/admin/dashboard',
            2 => '/BloodConnect/public/donor/complete-profile',
            3 => '/BloodConnect/public/patient/dashboard',
            4 => '/BloodConnect/public/hospital/dashboard',
        ];
        $redirect = $redirectMap[(int)$user['user_type_id']] ?? '/BloodConnect/public/';
        header("Location: $redirect");
        exit;
    }
}
