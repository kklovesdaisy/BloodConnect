<?php

namespace App\Authentication\Presentation\Controller;

use App\Shared\Helpers\Session;
use App\Shared\Presentation\View\View;
use App\Authentication\Domain\Repository\AuthRepositoryInterface;
use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Infrastructure\Mail\EmailService;
use App\Authentication\Application\UseCase\RegisterPatientUseCase;
use App\Authentication\Application\DTO\RegisterPatientDTO;

class VerifyEmailController
{
    public function __construct(
        private AuthRepositoryInterface $authRepo,
        private EmailService $emailService,
        private DonorRepositoryInterface $donorRepo,
        private RegisterPatientUseCase $registerPatientUseCase,
        private NotificationRepositoryInterface $notificationRepo
    ) {}
    public function show()
    {
        return View::render('Authentication', 'verify-email');
    }

    public function verify()
    {
        Session::start();

        $email = Session::get('verify_email');
        $pending = Session::get('pending_registration');
        $code = $_POST['code'] ?? null;

        if (!$email || !$code) {
            $_SESSION['errors']['form'] = "Invalid request.";
            $this->redirect('/verify-email');
        }

        if (!$pending || $pending['email'] !== $email) {
            $_SESSION['errors']['form'] = "Registration data not found. Please register again.";
            $this->redirect('/register');
        }

        if (strtotime($pending['expires_at']) < time()) {
            $_SESSION['errors']['form'] = "Code expired. Please resend.";
            $this->redirect('/verify-email');
        }

        if ((string)$pending['otp'] !== trim((string)$code)) {
            $_SESSION['errors']['form'] = "Invalid code.";
            $this->redirect('/verify-email');
        }

        $dto = new RegisterPatientDTO(
            $pending['username'],
            $pending['email'],
            $pending['phone'],
            '',
            $pending['blood_group'],
            $pending['address'],
            $pending['role']
        );

        $userId = $this->registerPatientUseCase->finalizeRegistration(
            $dto,
            (int)$pending['user_type_id'],
            (int)$pending['status_id'],
            $pending['password_hash']
        );

        $roleLabel = ((int)$pending['user_type_id'] === 2) ? 'Donor' : 'Patient';
        $admins = $this->notificationRepo->getAdmins();
        foreach ($admins as $admin) {
            $this->notificationRepo->create(
                (int)$admin['user_id'],
                'New ' . $roleLabel . ' Registered',
                sprintf(
                    'A new %s "%s" has registered and verified their email (ID: %s).',
                    strtolower($roleLabel),
                    $pending['username'],
                    $userId
                ),
                'USER_ACTION'
            );
        }

        Session::remove('pending_registration');
        Session::remove('verify_email');

        $_SESSION['success'] = "Email verified successfully! You can now log in.";
        $this->redirect('/login');
    }

    public function resend()
    {
        Session::start();

        $email = Session::get('verify_email');
        $pending = Session::get('pending_registration');

        if (!$email || !$pending) {
            $this->redirect('/register');
        }

        $code = rand(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $pending['otp'] = $code;
        $pending['expires_at'] = $expires;
        Session::set('pending_registration', $pending);

        $this->emailService->sendOtp($email, $code, $pending['username'] ?? null);

        $_SESSION['success'] = "New code sent!";
        $this->redirect('/verify-email');
    }

    private function redirect(string $path): void
    {
        $basePath = '/BloodConnect/public';
        $normalizedPath = '/' . ltrim($path, '/');
        header('Location: ' . $basePath . $normalizedPath);
        exit;
    }
}

