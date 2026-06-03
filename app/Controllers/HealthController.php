<?php

namespace App\Controllers;

use App\Auth;

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
            'name' => 'PHP Version',
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'fail',
            'message' => 'Running PHP ' . PHP_VERSION,
            'detail' => 'Minimum required: 7.4.0',
        ];

        $checks[] = [
            'name' => 'Session',
            'status' => session_status() === PHP_SESSION_ACTIVE || session_status() === PHP_SESSION_NONE ? 'ok' : 'warn',
            'message' => session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Available',
            'detail' => 'Session authentication ready',
        ];

        $storageWritable = is_writable(IBS_STORAGE) || @mkdir(IBS_STORAGE . '/logs', 0755, true);
        $checks[] = [
            'name' => 'Storage',
            'status' => $storageWritable ? 'ok' : 'fail',
            'message' => $storageWritable ? 'Writable' : 'Not writable',
            'detail' => IBS_STORAGE,
        ];

        $configExists = file_exists(IBS_CONFIG . '/database.php');
        $checks[] = [
            'name' => 'Database Config',
            'status' => $configExists ? 'ok' : 'fail',
            'message' => $configExists ? 'Present' : 'Missing',
            'detail' => 'config/database.php',
        ];

        $dbStatus = $this->checkDatabase();
        $checks[] = $dbStatus;

        $checks[] = [
            'name' => 'Application',
            'status' => 'ok',
            'message' => config('app.name') . ' v' . config('app.version'),
            'detail' => 'Standalone foundation — no OpenCart dependency',
        ];

        return $checks;
    }

    private function checkDatabase()
    {
        $host = config('database.host');
        $port = config('database.port');
        $database = config('database.database');

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $host,
                $port,
                $database,
                config('database.charset', 'utf8mb4')
            );

            $pdo = new \PDO(
                $dsn,
                config('database.username'),
                config('database.password'),
                config('database.options', [])
            );
            $pdo->query('SELECT 1');

            return [
                'name' => 'Database Connection',
                'status' => 'ok',
                'message' => 'Connected',
                'detail' => $database . '@' . $host . ':' . $port,
            ];
        } catch (\PDOException $e) {
            return [
                'name' => 'Database Connection',
                'status' => 'warn',
                'message' => 'Not connected',
                'detail' => 'Configure config/database.php and ensure MySQL is running. ' . $e->getMessage(),
            ];
        }
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
