<?php

namespace App\Controllers;

use App\Auth;

class VersionController extends Controller
{
    public function index()
    {
        Auth::requireAuth();

        $this->render('version.index', [
            'pageTitle' => 'Version',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Version', 'active' => true],
            ],
            'info' => $this->buildVersionInfo(),
        ]);
    }

    private function buildVersionInfo()
    {
        return [
            'product' => config('app.name'),
            'version' => config('app.version'),
            'codename' => 'Standalone Foundation',
            'release_date' => '2026-06-03',
            'php_version' => PHP_VERSION,
            'environment' => config('app.env'),
            'dependencies' => [
                'OpenCart' => 'None',
                'OCMOD' => 'None',
                'ZIP Installer' => 'None',
            ],
            'features' => [
                'Session authentication',
                'Simple router',
                'Admin layout',
                'PDO database connection',
                'Health monitoring with database and storage status',
                'Git-based deployment',
            ],
        ];
    }
}
