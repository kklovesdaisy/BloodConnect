<?php

namespace App\Shared\Middleware;

use App\Shared\Helpers\Session;

class HospitalMiddleware
{
    public function handle()
    {
        Session::start();

        if (!Session::has('user_id')) {
            header('Location: /BloodConnect/public/login');
            exit;
        }

        if ((int)Session::get('user_type_id') !== 4) {
            http_response_code(403);
            require __DIR__ . '/../Presentation/View/403.php';
            exit;
        }
    }
}
