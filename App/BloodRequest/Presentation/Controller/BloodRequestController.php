<?php

namespace App\BloodRequest\Presentation\Controller;

use App\BloodRequest\Application\UseCase\CreateBloodRequestUseCase;
use App\BloodRequest\Application\UseCase\ExpireOverdueRequestsUseCase;
use App\Shared\Helpers\Session;
use App\Shared\Presentation\View\View;

class BloodRequestController
{
    public function __construct(
        private CreateBloodRequestUseCase $createUseCase,
        private ExpireOverdueRequestsUseCase $expireUseCase
    ) {}


    /**
     * Show Request Blood Page
     */
    public function create()
    {
        Session::start();

        if (!Session::has('user_id')) {
            header('Location: /BloodConnect/public/login');
            exit;
        }

        $message = Session::get('flash_message', '');
        $status = Session::get('flash_status', '');
        Session::remove('flash_message');
        Session::remove('flash_status');

        return View::render('User', 'request_blood', [
            'message' => $message,
            'status'  => $status,
        ]);
    }



    public function store()
    {
        Session::start();

        if (!Session::has('user_id')) {
            header('Location: /BloodConnect/public/login');
            exit;
        }

        $this->expireUseCase->execute();

        $data = [
            'blood_group_needed' => trim($_POST['blood_group_needed']),
            'hospital_name'      => trim($_POST['hospital_name']),
            'state_region'       => trim($_POST['state_region'] ?? ''),
            'township'           => trim($_POST['township'] ?? ''),
            'hospital_address'   => trim($_POST['hospital_address'] ?? ''),
            'urgency'            => trim($_POST['urgency']),
            'contact_phone'      => trim($_POST['contact_phone']),
            'unit'               => min(2, max(1, (int)($_POST['unit'] ?? 1))),
        ];

        $result = $this->createUseCase->execute(
            (int)Session::get('user_id'),
            trim($_POST['patient_name']),
            $data
        );

        if (!$result['success']) {
            Session::set('flash_message', $result['error']);
            Session::set('flash_status', 'error');
            header('Location: /BloodConnect/public/patient/request-blood');
            exit;
        }

        header('Location: /BloodConnect/public/patient/dashboard?page=my-requests');
        exit;
    }



    public function list() {}



    public function details(int $id) {}
}

