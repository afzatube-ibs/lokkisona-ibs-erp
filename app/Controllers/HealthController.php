<?php

namespace App\Controllers;

use App\Auth;
use App\Database;

class HealthController extends Controller
{
    public function index()
    {
        Auth::requireAuth();

        $checks = $this->runChecks();

        $this->render('health.index', [
            'pageTitle' => 'Health Check',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Health Check', 'active' => true],
            ],
            'checks' => $checks,
            'overall' => $this->overallStatus($checks),
        ]);
    }

    private function runChecks()
    {
        $checks = [];

        $checks[] = [
            'name' => 'App Version',
            'status' => 'ok',
            'message' => 'v' . config('app.version'),
            'detail' => config('app.name'),
        ];

        $checks[] = [
            'name' => 'PHP Version',
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'fail',
            'message' => PHP_VERSION,
            'detail' => 'Minimum required: 7.4.0',
        ];

        $storageWritable = $this->isStorageWritable();
        $databaseStatus = $this->databaseStatus();
        $checks[] = [
            'name' => 'Database Connection Status',
            'status' => $databaseStatus['connected'] ? 'ok' : 'warn',
            'message' => $databaseStatus['message'],
            'detail' => $databaseStatus['detail'],
        ];

        $checks[] = [
            'name' => 'Storage Writable Status',
            'status' => $storageWritable ? 'ok' : 'fail',
            'message' => $storageWritable ? 'Writable' : 'Not writable',
            'detail' => IBS_STORAGE,
        ];

        $checks[] = [
            'name' => 'Environment',
            'status' => 'ok',
            'message' => config('app.env', 'local'),
            'detail' => 'Standalone foundation, no OpenCart dependency',
        ];

        $checks[] = [
            'name' => 'Current Server Time',
            'status' => 'ok',
            'message' => date('Y-m-d H:i:s T'),
            'detail' => 'Timezone: ' . date_default_timezone_get(),
        ];

        return $checks;
    }

    private function databaseStatus()
    {
        return Database::check();
    }

    private function isStorageWritable()
    {
        if (!is_dir(IBS_STORAGE)) {
            @mkdir(IBS_STORAGE, 0755, true);
        }

        return is_dir(IBS_STORAGE) && is_writable(IBS_STORAGE);
    }

    private function overallStatus($checks)
    {
        foreach ($checks as $check) {
            if ($check['status'] === 'fail') {
                return 'fail';
            }
        }

        foreach ($checks as $check) {
            if ($check['status'] === 'warn') {
                return 'warn';
            }
        }

        return 'ok';
    }
}
