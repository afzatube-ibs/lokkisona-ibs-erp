<?php

namespace App\Controllers;

use App\ActivityLog;

class VersionController extends Controller
{
    public function index()
    {
        $this->authorize('version.view');
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
            'release_date' => '2026-06-04',
            'php_version' => PHP_VERSION,
            'php_requirement' => 'PHP 8.2+',
            'environment' => config('app.env'),
            'dependencies' => [
                'OpenCart' => 'None',
                'OCMOD' => 'None',
                'ZIP Installer' => 'None',
            ],
            'features' => [
                'Business Source and Sales Channel foundation page and permissions without database writes',
                'Current primary source Lokkisona.com documented without hard-coding future channels',
                'Manual, offline, ecommerce, marketplace, wholesale, and other source types prepared',
                'Future order source links prepared for supplier workflow, dispatch, returns, and payable',
                'Supplier foundation page and permissions without database writes',
                'Primary supplier Iqbal & Brothers documented with channel-neutral architecture',
                'Planned supplier fields and clean supplier accounting wording',
                'User Management foundation page and permission',
                'Future database user planning without writes',
                'Local checkpoint runner for PHP lint, route smoke, and safety checks',
                'Manual migration foundation',
                'Database safety page and permission',
                'Planned future table inventory',
                'Session authentication foundation',
                'Owner, admin, staff, and supplier roles prepared',
                'Config-backed permission policy service',
                'Permission-aware route protection and sidebar visibility',
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
