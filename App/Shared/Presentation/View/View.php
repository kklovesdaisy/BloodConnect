<?php

namespace App\Shared\Presentation\View;

/**
 * Single, canonical view renderer for the whole application.
 *
 * Previously there were four near-identical renderers
 * (Shared\View, Authentication\View, donorView, patientView). They are now
 * consolidated here: every module renders through View::render($module, ...),
 * which resolves the module's View directory and Layout file.
 *
 * Layout resolution per module:
 *   Shared/Authentication/BloodRequest -> app.php
 *   Donor                             -> donorApp.php
 *   User (patient)                    -> patientApp.php
 *   Admin                             -> adminApp.php
 *   Hospital                          -> hospitalApp.php
 */
class View
{
    private const LAYOUTS = [
        'Shared'         => 'app.php',
        'Authentication' => 'app.php',
        'BloodRequest'   => 'app.php',
        'Donor'          => 'donorApp.php',
        'User'           => 'patientApp.php',
        'Admin'          => 'adminApp.php',
        'Hospital'       => 'hospitalApp.php',
    ];

    public static function render(string $module, string $view, array $data = []): void
    {
        $base = dirname(__DIR__, 4);

        $viewFile = $base . "/App/{$module}/Presentation/View/{$view}.php";
        $layout   = $base . "/App/{$module}/Presentation/Layout/" . (self::LAYOUTS[$module] ?? 'app.php');

        if (!is_file($viewFile)) {
            throw new \Exception("View not found: {$viewFile}");
        }

        if (!is_file($layout)) {
            throw new \Exception("Layout not found: {$layout}");
        }

        extract($data);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layout;
    }
}
