<?php

/**
 * --------------------------------------------------------
 * Application Bootstrap
 * --------------------------------------------------------
 */

// Set default timezone (Myanmar)
date_default_timezone_set('Asia/Yangon');

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

/**
 * --------------------------------------------------------
 * Dependency Injection Container
 * --------------------------------------------------------
 */
$container = new \App\Shared\Infrastructure\Container\Container();

// ===== Repository Bindings (Interface -> Implementation) =====
$container->singleton(
    \App\Authentication\Domain\Repository\AuthRepositoryInterface::class,
    \App\Authentication\Infrastructure\Persistence\AuthRepository::class
);

$container->singleton(
    \App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface::class,
    \App\BloodRequest\Infrastructure\Persistence\BloodRequestRepository::class
);

$container->singleton(
    \App\Donation\Domain\Repository\DonationRepositoryInterface::class,
    \App\Donation\Infrastructure\Persistence\DonationRepository::class
);

$container->singleton(
    \App\Donor\Domain\Repository\DonorRepositoryInterface::class,
    \App\Donor\Infrastructure\Persistence\DonorRepository::class
);

$container->singleton(
    \App\Notification\Domain\Repository\NotificationRepositoryInterface::class,
    \App\Notification\Infrastructure\Persistence\NotificationRepository::class
);

$container->singleton(
    \App\User\Domain\Repository\UserRepositoryInterface::class,
    \App\User\Infrastructure\Persistence\UserRepository::class
);

$container->singleton(
    \App\BloodStock\Domain\Repository\BloodStockRepositoryInterface::class,
    \App\BloodStock\Infrastructure\Persistence\BloodStockRepository::class
);

$container->singleton(
    \App\Hospital\Domain\Repository\HospitalRepositoryInterface::class,
    \App\Hospital\Infrastructure\Persistence\HospitalRepository::class
);

$container->singleton(
    \App\Hospital\Domain\Repository\HospitalBloodRequestRepositoryInterface::class,
    \App\Hospital\Infrastructure\Persistence\HospitalBloodRequestRepository::class
);

$container->singleton(
    \App\Hospital\Domain\Repository\HospitalBloodStockRepositoryInterface::class,
    \App\Hospital\Infrastructure\Persistence\HospitalBloodStockRepository::class
);

// ===== Shared Service Bindings =====
$container->singleton(
    \App\Shared\Infrastructure\Persistence\MasterDataRepository::class,
    \App\Shared\Infrastructure\Persistence\MasterDataRepository::class
);

$container->singleton(
    \App\Shared\Infrastructure\Persistence\RoleRepository::class,
    \App\Shared\Infrastructure\Persistence\RoleRepository::class
);

$container->singleton(
    \App\Shared\Infrastructure\Mail\EmailService::class,
    \App\Shared\Infrastructure\Mail\EmailService::class
);

$container->singleton(
    \App\Shared\Infrastructure\Activity\ActivityLogger::class,
    \App\Shared\Infrastructure\Activity\ActivityLogger::class
);

// ===== Blood Request Receipt (PDF) =====
$container->singleton(
    \App\Shared\Infrastructure\Pdf\BloodRequestReceiptPdfGenerator::class,
    \App\Shared\Infrastructure\Pdf\BloodRequestReceiptPdfGenerator::class
);

$container->singleton(
    \App\BloodRequest\Application\UseCase\GenerateRequestReceiptUseCase::class,
    function (\App\Shared\Infrastructure\Container\Container $c) {
        return new \App\BloodRequest\Application\UseCase\GenerateRequestReceiptUseCase(
            $c->get(\App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface::class),
            $c->get(\App\Shared\Infrastructure\Pdf\BloodRequestReceiptPdfGenerator::class)
        );
    }
);

// ===== Donation Certificate (PDF) =====
$container->singleton(
    \App\Shared\Infrastructure\Pdf\DonationCertificatePdfGenerator::class,
    \App\Shared\Infrastructure\Pdf\DonationCertificatePdfGenerator::class
);

$container->singleton(
    \App\Donation\Application\UseCase\GenerateDonationCertificateUseCase::class,
    function (\App\Shared\Infrastructure\Container\Container $c) {
        return new \App\Donation\Application\UseCase\GenerateDonationCertificateUseCase(
            $c->get(\App\Donation\Domain\Repository\DonationRepositoryInterface::class),
            $c->get(\App\Shared\Infrastructure\Pdf\DonationCertificatePdfGenerator::class),
            $c->get(\App\Shared\Infrastructure\Persistence\MasterDataRepository::class)
        );
    }
);

// ===== Admin Controllers =====
$container->singleton(
    \App\Admin\Presentation\Controller\AdminDashboardController::class,
    function (\App\Shared\Infrastructure\Container\Container $c) {
        return new \App\Admin\Presentation\Controller\AdminDashboardController(
            $c->get(\App\User\Domain\Repository\UserRepositoryInterface::class),
            $c->get(\App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface::class),
            $c->get(\App\Notification\Domain\Repository\NotificationRepositoryInterface::class),
            $c->get(\App\Shared\Infrastructure\Activity\ActivityLogger::class)
        );
    }
);

$container->singleton(
    \App\Admin\Presentation\Controller\AdminUserController::class,
    function (\App\Shared\Infrastructure\Container\Container $c) {
        return new \App\Admin\Presentation\Controller\AdminUserController(
            $c->get(\App\Admin\Application\UseCase\ManageUsersUseCase::class)
        );
    }
);

$container->singleton(
    \App\Admin\Presentation\Controller\AdminBloodRequestController::class,
    function (\App\Shared\Infrastructure\Container\Container $c) {
        return new \App\Admin\Presentation\Controller\AdminBloodRequestController(
            $c->get(\App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface::class),
            $c->get(\App\Donation\Domain\Repository\DonationRepositoryInterface::class),
            $c->get(\App\Notification\Domain\Repository\NotificationRepositoryInterface::class),
            $c->get(\App\User\Domain\Repository\UserRepositoryInterface::class),
            $c->get(\App\Donor\Domain\Repository\DonorRepositoryInterface::class),
            $c->get(\App\Shared\Infrastructure\Persistence\MasterDataRepository::class),
            $c->get(\App\Admin\Application\UseCase\ViewBloodRequestsUseCase::class),
            $c->get(\App\Admin\Application\UseCase\ConfirmDonationUseCase::class),
            $c->get(\App\Admin\Application\UseCase\FindMatchingDonorsUseCase::class),
            $c->get(\App\Admin\Application\UseCase\AssignDonorsUseCase::class),
            $c->get(\App\Admin\Application\UseCase\DeleteBloodRequestUseCase::class),
            $c->get(\App\Admin\Application\UseCase\NotifyDonorsUseCase::class),
            $c->get(\App\BloodRequest\Application\UseCase\ExpireOverdueRequestsUseCase::class)
        );
    }
);

$container->singleton(
    \App\Admin\Presentation\Controller\AdminRoleController::class,
    function (\App\Shared\Infrastructure\Container\Container $c) {
        return new \App\Admin\Presentation\Controller\AdminRoleController(
            $c->get(\App\Shared\Infrastructure\Persistence\RoleRepository::class),
            $c->get(\App\Notification\Domain\Repository\NotificationRepositoryInterface::class)
        );
    }
);

$container->singleton(
    \App\Admin\Presentation\Controller\AdminBloodStockController::class,
    function (\App\Shared\Infrastructure\Container\Container $c) {
        return new \App\Admin\Presentation\Controller\AdminBloodStockController(
            $c->get(\App\Admin\Application\UseCase\ManageBloodStockUseCase::class)
        );
    }
);

/**
 * --------------------------------------------------------
 * Get request URI
 * --------------------------------------------------------
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/**
 * --------------------------------------------------------
 * Get project base path correctly
 * --------------------------------------------------------
 */
$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

/**
 * --------------------------------------------------------
 * Remove base path from URI
 * --------------------------------------------------------
 */
if ($basePath !== '/' && $basePath !== '') {
    $uri = str_replace($basePath, '', $uri);
}

/**
 * --------------------------------------------------------
 * Normalize URI
 * --------------------------------------------------------
 */
$uri = '/' . trim($uri, '/');

if ($uri === '//') {
    $uri = '/';
}

/**
 * --------------------------------------------------------
 * Run idempotent schema migrations once per process
 * --------------------------------------------------------
 */
(new \App\Shared\Infrastructure\Migration\SchemaMigrator())->migrate();

/**
 * --------------------------------------------------------
 * Load router
 * --------------------------------------------------------
 */
$router = require __DIR__ . '/../routes/web.php';
$router->setContainer($container);

$router->dispatch($uri, $_SERVER['REQUEST_METHOD']);
