<?php

use Routes\Router;

use App\Shared\Presentation\Controller\HomeController;
use App\Authentication\Presentation\Controller\AuthController;
use App\Authentication\Presentation\Controller\VerifyEmailController;
use App\User\Presentation\Controller\PatientController;
use App\BloodRequest\Presentation\Controller\BloodRequestController;
use App\Admin\Presentation\Controller\AdminDashboardController;
use App\Admin\Presentation\Controller\AdminUserController;
use App\Admin\Presentation\Controller\AdminBloodRequestController;
use App\Admin\Presentation\Controller\AdminDonorController;
use App\Admin\Presentation\Controller\AdminRoleController;
use App\Admin\Presentation\Controller\AdminReportController;
use App\Admin\Presentation\Controller\AdminBloodStockController;
use App\Admin\Presentation\Controller\AdminHospitalController;
use App\Donor\Presentation\Controller\DonorController;
use App\Hospital\Presentation\Controller\HospitalController;
use App\Authentication\Presentation\Controller\GoogleAuthController;

$router = new Router();

/*
|--------------------------------------------------------------------------
| REGISTER MIDDLEWARE ALIASES
|--------------------------------------------------------------------------
*/
$router->aliasMiddleware('auth', \App\Shared\Middleware\AuthMiddleware::class);
$router->aliasMiddleware('admin', \App\Shared\Middleware\AdminMiddleware::class);
$router->aliasMiddleware('donor', \App\Shared\Middleware\DonorMiddleware::class);
$router->aliasMiddleware('patient', \App\Shared\Middleware\PatientMiddleware::class);
$router->aliasMiddleware('hospital', \App\Shared\Middleware\HospitalMiddleware::class);

/*
|--------------------------------------------------------------------------
| Middleware with parameter support (can:{permission})
| The Router parses "can:dashboard.view" into PermissionMiddleware("dashboard.view")
|--------------------------------------------------------------------------
*/
$router->aliasMiddleware('can', \App\Shared\Middleware\PermissionMiddleware::class);

/*
|--------------------------------------------------------------------------
| HOME ROUTES (public)
|--------------------------------------------------------------------------
*/
$router->get('/', [HomeController::class, 'home']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/contact', [HomeController::class, 'contact']);
$router->post('/contact/send', [HomeController::class, 'contactSend']);
$router->get('/search', [HomeController::class, 'search']);
$router->get('/faq', [HomeController::class, 'faq']);
$router->get('/privacy-policy', [HomeController::class, 'privacy']);
$router->get('/terms-of-service', [HomeController::class, 'terms']);

/*
|--------------------------------------------------------------------------
| AUTH ROUTES (guest only)
|--------------------------------------------------------------------------
*/
$router->get('/register', [AuthController::class, 'showRegister']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

// Admin console login (separate page; same auth flow)
$router->get('/admin/login', [AuthController::class, 'showAdminLogin']);
$router->post('/admin/login', [AuthController::class, 'login']);

$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'sendPasswordResetOtp']);
$router->get('/forgot-password/verify', [AuthController::class, 'showForgotPasswordOtp']);
$router->post('/forgot-password/verify', [AuthController::class, 'verifyForgotPasswordOtp']);
$router->get('/forgot-password/reset', [AuthController::class, 'showResetPasswordForm']);
$router->post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

$router->post('/patient/register', [AuthController::class, 'registerPatient']);
$router->post('/donor/register', [AuthController::class, 'registerPatient']);

$router->get('/logout', [AuthController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| GOOGLE OAUTH ROUTES (public)
|--------------------------------------------------------------------------
*/
$router->get('/auth/google', [GoogleAuthController::class, 'redirect']);
$router->get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
$router->get('/auth/google/choose-role', [GoogleAuthController::class, 'chooseRole']);
$router->post('/auth/google/complete-registration', [GoogleAuthController::class, 'completeRegistration']);

/*
|--------------------------------------------------------------------------
| EMAIL VERIFICATION ROUTES (public)
|--------------------------------------------------------------------------
*/
$router->get('/verify-email', [VerifyEmailController::class, 'show']);
$router->post('/verify-email', [VerifyEmailController::class, 'verify']);
$router->post('/resend-code', [VerifyEmailController::class, 'resend']);

/*
|--------------------------------------------------------------------------
| SHARED NOTIFICATION AJAX ROUTES (auth only - all roles)
|--------------------------------------------------------------------------
*/
$router->group(['middleware' => ['auth', 'can:notification.view']], function (Router $router) {
    $router->post('/notification/mark-read', [PatientController::class, 'markNotificationRead']);
    $router->post('/notification/mark-all-read', [PatientController::class, 'markAllNotificationsRead']);
    $router->get('/notification/unread-count', [PatientController::class, 'unreadCount']);
    $router->post('/notification/delete', [PatientController::class, 'deleteNotification']);
});

/*
|--------------------------------------------------------------------------
| PATIENT ROUTES (auth + patient + RBAC permission)
|--------------------------------------------------------------------------
*/
$router->group(['middleware' => ['auth', 'patient']], function (Router $router) {
    $router->middleware(['can:dashboard.view'], function (Router $router) {
        $router->get('/patient/dashboard', [PatientController::class, 'patient_dashboard']);
    });
    $router->middleware(['can:blood_request.view_own'], function (Router $router) {
        $router->get('/patient/my-requests', [PatientController::class, 'myRequests']);
        $router->get('/patient/my-requests/cancelled', [PatientController::class, 'cancelledRequests']);
        $router->get('/patient/my-request/view', [PatientController::class, 'viewMyRequest']);
        $router->get('/patient/my-request/receipt', [PatientController::class, 'downloadRequestReceipt']);
        $router->post('/patient/request/cancel', [PatientController::class, 'cancelRequest']);
        $router->post('/patient/request/reactivate', [PatientController::class, 'reactivateRequest']);
    });
    $router->middleware(['can:blood_request.create'], function (Router $router) {
        $router->get('/patient/request-blood', [BloodRequestController::class, 'create']);
        $router->post('/patient/request-blood', [BloodRequestController::class, 'store']);
    });
    $router->middleware(['can:donor.search'], function (Router $router) {
        $router->get('/patient/search-donors', [PatientController::class, 'searchDonors']);
    });
    $router->middleware(['can:profile.view'], function (Router $router) {
        $router->get('/patient/profile', [PatientController::class, 'profile']);
        $router->get('/patient/profile/update', [PatientController::class, 'updateProfilePage']);
    });
    $router->middleware(['can:profile.update'], function (Router $router) {
        $router->post('/patient/profile/update', [PatientController::class, 'updateProfile']);
    });
    $router->middleware(['can:notification.view'], function (Router $router) {
        $router->get('/patient/notifications', [PatientController::class, 'notifications']);
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (auth + admin + RBAC permission)
| Middleware pipeline: AuthMiddleware → AdminMiddleware → PermissionMiddleware
|--------------------------------------------------------------------------
*/

// Dashboard
$router->group(['middleware' => ['auth', 'admin', 'can:dashboard.view']], function (Router $router) {
    $router->get('/admin/dashboard', [AdminDashboardController::class, 'admin_dashboard']);
    $router->get('/admin/dashboard/chart-data', [AdminDashboardController::class, 'chartData']);
});

// Profile & Notifications (mapped to dashboard controller)
$router->group(['middleware' => ['auth', 'admin', 'can:profile.view']], function (Router $router) {
    $router->get('/admin/profile', [AdminDashboardController::class, 'profile']);
});
$router->group(['middleware' => ['auth', 'admin', 'can:profile.update']], function (Router $router) {
    $router->get('/admin/profile/update', [AdminDashboardController::class, 'updateProfilePage']);
    $router->post('/admin/profile/update', [AdminDashboardController::class, 'updateProfile']);
});
$router->group(['middleware' => ['auth', 'admin', 'can:notification.view']], function (Router $router) {
    $router->get('/admin/notifications', [AdminDashboardController::class, 'notifications']);
});

// User management
$router->group(['middleware' => ['auth', 'admin', 'can:user.view']], function (Router $router) {
    $router->get('/admin/user-management', [AdminUserController::class, 'userManagement']);
    $router->get('/admin/user/view', [AdminUserController::class, 'viewUser']);
    $router->get('/admin/user/edit', [AdminUserController::class, 'editUser']);
});
$router->group(['middleware' => ['auth', 'admin', 'can:user.update']], function (Router $router) {
    $router->post('/admin/user/update', [AdminUserController::class, 'updateUser']);
});
$router->group(['middleware' => ['auth', 'admin', 'can:user.delete']], function (Router $router) {
    $router->get('/admin/user/delete', [AdminUserController::class, 'deleteUser']);
});

// Donor management
$router->group(['middleware' => ['auth', 'admin', 'can:donor.view']], function (Router $router) {
    $router->get('/admin/donor-management', [AdminDonorController::class, 'donorManagement']);
});

// Hospital management
$router->group(['middleware' => ['auth', 'admin', 'can:hospital.view']], function (Router $router) {
    $router->get('/admin/hospitals', [AdminHospitalController::class, 'index']);
});
$router->group(['middleware' => ['auth', 'admin', 'can:hospital.create']], function (Router $router) {
    $router->get('/admin/hospitals/create', [AdminHospitalController::class, 'create']);
    $router->post('/admin/hospitals/create', [AdminHospitalController::class, 'store']);
});

// Hospital blood stock requests (admin overview)
$router->group(['middleware' => ['auth', 'admin', 'can:hospital_request.view']], function (Router $router) {
    $router->get('/admin/hospital-requests', [AdminHospitalController::class, 'hospitalRequests']);
    $router->post('/admin/hospital-requests/accept', [AdminHospitalController::class, 'acceptRequest']);
    $router->post('/admin/hospital-requests/decline', [AdminHospitalController::class, 'declineRequest']);
    $router->post('/admin/hospital-requests/provide-more', [AdminHospitalController::class, 'provideMore']);
    $router->get('/admin/pending-hospital-requests-count', [AdminHospitalController::class, 'pendingHospitalRequestsCount']);
});

// Blood requests
$router->group(['middleware' => ['auth', 'admin', 'can:blood_request.view_matching']], function (Router $router) {
    $router->get('/admin/blood-requests', [AdminBloodRequestController::class, 'bloodRequests']);
    $router->get('/admin/blood-request/view', [AdminBloodRequestController::class, 'viewBloodRequest']);
    $router->get('/admin/donation-history', [AdminBloodRequestController::class, 'donationHistory']);
    $router->post('/admin/blood-request/accept', [AdminBloodRequestController::class, 'acceptBloodRequest']);
    $router->post('/admin/blood-request/assign-donors', [AdminBloodRequestController::class, 'assignDonors']);
    $router->post('/admin/blood-request/notify-donors', [AdminBloodRequestController::class, 'notifyDonors']);
    $router->post('/admin/blood-request/delete', [AdminBloodRequestController::class, 'deleteBloodRequest']);
    $router->post('/admin/blood-request/reset-to-pending', [AdminBloodRequestController::class, 'resetToPending']);
    $router->get('/admin/request/complete', [AdminBloodRequestController::class, 'completeRequest']);
    $router->get('/admin/pending-blood-requests-count', [AdminBloodRequestController::class, 'pendingBloodRequestsCount']);

    // Blood stock management
    $router->get('/admin/blood-stock', [AdminBloodStockController::class, 'index']);
    $router->post('/admin/blood-stock/dispose-expired', [AdminBloodStockController::class, 'disposeExpired']);
});

// Roles & permissions
$router->group(['middleware' => ['auth', 'admin', 'can:user_type.manage']], function (Router $router) {
    $router->get('/admin/roles', [AdminRoleController::class, 'roles']);
    $router->get('/admin/roles/{id}', [AdminRoleController::class, 'editRole']);
    $router->post('/admin/roles/{id}', [AdminRoleController::class, 'editRole']);
});
$router->group(['middleware' => ['auth', 'admin', 'can:permission.manage']], function (Router $router) {
    $router->post('/admin/roles/update-permissions', [AdminRoleController::class, 'updateRolePermissions']);
});

// Export reports (PDF & Excel)
$router->group(['middleware' => ['auth', 'admin', 'can:report.view']], function (Router $router) {
    $router->get('/admin/reports', [AdminReportController::class, 'index']);
    $router->get('/admin/reports/donors', [AdminReportController::class, 'donors']);
    $router->get('/admin/reports/patients', [AdminReportController::class, 'patients']);
    $router->get('/admin/reports/blood-requests', [AdminReportController::class, 'bloodRequests']);
    $router->get('/admin/reports/donations', [AdminReportController::class, 'donations']);
});

/*
|--------------------------------------------------------------------------
| DONOR ROUTES (auth + donor + RBAC permission)
|--------------------------------------------------------------------------
*/
$router->group(['middleware' => ['auth', 'donor']], function (Router $router) {
    // Complete donor profile (no permission check - always accessible to donors)
    $router->get('/donor/complete-profile', [DonorController::class, 'completeProfile']);
    $router->post('/donor/complete-profile', [DonorController::class, 'saveCompleteProfile']);

    $router->middleware(['can:dashboard.view'], function (Router $router) {
        $router->get('/donor/dashboard', [DonorController::class, 'donor_dashboard']);
    });
    $router->middleware(['can:profile.view'], function (Router $router) {
        $router->get('/donor/profile', [DonorController::class, 'profile']);
    });
    $router->middleware(['can:blood_request.view_matching'], function (Router $router) {
        $router->get('/donor/blood-requests', [DonorController::class, 'bloodRequests']);
        $router->get('/donor/blood-request/{id}', [DonorController::class, 'bloodRequestDetails']);
        $router->get('/donor/pending-blood-requests-count', [DonorController::class, 'pendingBloodRequestsCount']);
    });
    $router->middleware(['can:donation_history.view'], function (Router $router) {
        $router->get('/donor/history', [DonorController::class, 'history']);
        $router->get('/donor/history/view', [DonorController::class, 'viewHistory']);
        $router->get('/donor/donation/certificate/view', [DonorController::class, 'viewDonationCertificate']);
        $router->get('/donor/donation/certificate', [DonorController::class, 'downloadDonationCertificate']);
    });
    $router->middleware(['can:profile.update'], function (Router $router) {
        $router->get('/donor/profile/update', [DonorController::class, 'updateProfilePage']);
        $router->post('/donor/profile/update', [DonorController::class, 'updateProfile']);
    });
    $router->middleware(['can:notification.view'], function (Router $router) {
        $router->get('/donor/notifications', [DonorController::class, 'notifications']);
    });
    $router->middleware(['can:donation.create'], function (Router $router) {
        $router->get('/donor/donate', [DonorController::class, 'donatePage']);
        $router->post('/donor/donate', [DonorController::class, 'storeDonation']);
    });

    $router->middleware(['can:blood_request.accept'], function (Router $router) {
        $router->post('/donor/request/accept', [DonorController::class, 'acceptRequest']);
    });
    $router->middleware(['can:blood_request.decline'], function (Router $router) {
        $router->post('/donor/request/decline', [DonorController::class, 'declineRequest']);
    });
});

/*
|--------------------------------------------------------------------------
| HOSPITAL ROUTES (auth + hospital + RBAC permission)
|--------------------------------------------------------------------------
*/
$router->group(['middleware' => ['auth', 'hospital']], function (Router $router) {
    $router->middleware(['can:dashboard.view'], function (Router $router) {
        $router->get('/hospital/dashboard', [HospitalController::class, 'dashboard']);
    });
    $router->middleware(['can:blood_stock.view'], function (Router $router) {
        $router->get('/hospital/stock', [HospitalController::class, 'bloodStock']);
    });
    $router->middleware(['can:hospital_request.view'], function (Router $router) {
        $router->get('/hospital/requests', [HospitalController::class, 'requests']);
        $router->get('/hospital/requests/pending', [HospitalController::class, 'pending']);
    });
    $router->middleware(['can:hospital_request.create'], function (Router $router) {
        $router->get('/hospital/requests/create', [HospitalController::class, 'createRequestPage']);
        $router->post('/hospital/requests/create', [HospitalController::class, 'storeRequest']);
    });
    $router->middleware(['can:profile.view'], function (Router $router) {
        $router->get('/hospital/profile', [HospitalController::class, 'profile']);
        $router->get('/hospital/profile/update', [HospitalController::class, 'updateProfilePage']);
    });
    $router->middleware(['can:profile.update'], function (Router $router) {
        $router->post('/hospital/profile/update', [HospitalController::class, 'updateProfile']);
    });
    $router->middleware(['can:notification.view'], function (Router $router) {
        $router->get('/hospital/notifications', [HospitalController::class, 'notifications']);
    });
});

return $router;
