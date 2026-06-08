<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Auth;
use App\Csrf;
use App\Permission;
use App\ReadFoundation\WriteGate;
use App\Services\Write\WriteResult;

class Controller
{
    protected function render($view, $data = [], $layout = 'layouts.admin')
    {
        $data['pageTitle'] = $data['pageTitle'] ?? config('app.name');
        $data['appName'] = config('app.name');
        $data['appVersion'] = config('app.version');
        $data['appReleaseLabel'] = config('app.release_label');
        $data['currentUser'] = Auth::user();
        $data['currentRole'] = Auth::role();
        $data['navItems'] = Permission::menuItems();
        $data['navNavigation'] = Permission::menuNavigation();
        $data['appEnv'] = config('app.env', 'local');
        $data['currentPath'] = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $queryString = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY) ?? '';
        $currentQuery = [];
        if ($queryString !== '') {
            parse_str($queryString, $currentQuery);
        }
        $data['currentQuery'] = $currentQuery;
        $data['canUseCalculator'] = Permission::can('supplier_calculator.view');
        $data['canUseQuickInvoice'] = Permission::can('supplier_quick_invoice.manage');
        $quickInvoiceGate = WriteGate::supplierQuickInvoice();
        $data['quickInvoiceGateReady'] = $quickInvoiceGate['ready'];
        $data['writeGateMessage'] = $quickInvoiceGate['ready']
            ? WriteGate::WARNING_MESSAGE
            : 'Apply migrations 0007_invoices_printing_supplier_tools.sql and 0010_supplier_quick_invoice_totals.sql manually before generating shop invoices.';
        $data['csrfField'] = Csrf::field();

        ob_start();
        view($view, $data);
        $content = ob_get_clean();

        view($layout, array_merge($data, ['content' => $content]));
    }

    protected function authorize($permission)
    {
        Auth::requireAuth();

        if (Permission::can($permission)) {
            return;
        }

        ActivityLog::record('access_denied', 'Permission check failed', [
            'permission' => $permission,
            'role' => Auth::role(),
        ]);

        http_response_code(403);
        $this->render('errors.403', [
            'pageTitle' => 'Access Denied',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Access Denied', 'active' => true],
            ],
            'permission' => $permission,
        ]);
        exit;
    }

    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit;
        }
    }

    protected function validateCsrf(): bool
    {
        return Csrf::validate($_POST['_csrf'] ?? null);
    }

    protected function flash(string $key, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['flash'][$key] = $message;
    }

    protected function flashLink(string $url, string $label): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['flash']['success_link_url'] = $url;
        $_SESSION['flash']['success_link_label'] = $label;
    }

    protected function pullFlash(string $key): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);

        return $message;
    }

    /**
     * @return array{url: string, label: string}|null
     */
    protected function pullFlashLink(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $url = $_SESSION['flash']['success_link_url'] ?? null;
        $label = $_SESSION['flash']['success_link_label'] ?? null;
        unset($_SESSION['flash']['success_link_url'], $_SESSION['flash']['success_link_label']);
        if (!is_string($url) || $url === '' || !is_string($label) || $label === '') {
            return null;
        }

        return ['url' => $url, 'label' => $label];
    }

    protected function redirectWithWriteResult(string $path, WriteResult $result): void
    {
        $this->flash($result->success ? 'success' : 'error', $result->message);
        redirect($path);
    }

    protected function writeGateStatus(array $physicalTables): array
    {
        return WriteGate::status($physicalTables);
    }
}
