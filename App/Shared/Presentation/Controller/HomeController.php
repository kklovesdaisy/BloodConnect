<?php

namespace App\Shared\Presentation\Controller;

use App\Shared\Presentation\View\View;
use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\Hospital\Domain\Repository\HospitalBloodRequestRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

class HomeController
{
    public function __construct(
        private BloodRequestRepositoryInterface $bloodRequestRepo,
        private HospitalBloodRequestRepositoryInterface $hospitalRequestRepo,
        private UserRepositoryInterface $userRepo
    ) {}

    public function home()
    {
        // Successful donations = donor requests accepted by recipients plus
        // each hospital stock request the system has fully completed (counted once).
        $successfulDonations = $this->bloodRequestRepo->countAcceptedByDonors()
            + $this->hospitalRequestRepo->countCompletedRequests();

        $users = $this->userRepo->findAll();
        $totalUsers = count($users);

        $totalDonors = 0;
        foreach ($users as $user) {
            if ((int)($user['user_type_id'] ?? 0) === 2) $totalDonors++;
        }

        return View::render('Shared', 'home', [
            'successful_donations' => $successfulDonations,
            'total_donors' => $totalDonors,
            'total_users' => $totalUsers,
        ]);
    }

    public function about()
    {
        return View::render('Shared', 'about');
    }

    public function contact()
    {
        return View::render('Shared', 'contact');
    }

    public function contactSend()
    {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        $errors = [];
        if ($name === '')    $errors['name']    = 'Full name is required.';
        if ($email === '' || !str_contains($email, '@')) $errors['email'] = 'A valid email is required.';
        if ($subject === '') $errors['subject'] = 'Please specify a clear subject.';
        if ($message === '') $errors['message'] = 'Message cannot be blank.';

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/contact'));
            exit;
        }

        $_SESSION['success'] = 'Your message has been received. Our team will respond within 24 hours.';
        $_SESSION['old'] = [];
        header('Location: /contact');
        exit;
    }

    public function faq()
    {
        return View::render('Shared', 'faq');
    }

    public function privacy()
    {
        return View::render('Shared', 'privacy_policy');
    }

    public function terms()
    {
        return View::render('Shared', 'terms_of_service');
    }

    public function search()
    {
        return View::render('Shared', 'search');
    }

    public function donors()
    {
        return View::render('Shared', 'donors');
    }
    public function donor_dashboard()
    {
        return View::render('Shared', 'donor-dashboard');
    }
}

