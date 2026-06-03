<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Auth;

class VersionController extends Controller
{
    public function index()
    {
        Auth::requireAuth();
        ActivityLog::record('version_access', 'Version page viewed');

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
            'codename' => config('app.release_label'),
            'release_date' => '2026-06-03',
            'php_version' => PHP_VERSION,
            'php_requirement' => 'PHP 8.2+',
            'environment' => config('app.env'),
            'dependencies' => [
                'OpenCart' => 'None',
                'OCMOD' => 'None',
                'ZIP Installer' => 'None',
            ],
            'features' => [
                'Session authentication foundation',
                'Owner, admin, and staff role wording prepared',
                'File-backed activity log foundation',
                'Simple router',
                'Admin layout',
                'PDO database connection',
                'Health monitoring with database and storage status',
                'Git-based deployment',
            ],
        ];
    }
}
