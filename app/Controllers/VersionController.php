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
                'Return Receive & Review Batch planning foundation page and permissions, owner/admin controlled',
                'Supplier Return vs Lokkisona Return separation with pre-submit total/cost-snapshot confirmation',
                'Supplier Return may affect payable only after review; Lokkisona Return has no payable deduction',
                'Return batch planning, payable adjustment review, remark/note, and optional image proof planned',
                'Planned return receive, return batch, return item, and payable adjustment fields documented',
                'Supplier Payable & Settlement planning foundation page and permissions without payable writes',
                'Product Cost Payable from dispatch cost snapshot, based on product cost only, not selling price',
                'Supplier Invoice, Additional Payable, Return/Damage Deduction, Payment, and Advance wording planned',
                'Net Payable to Supplier formula, owner/admin approval/audit, and supplier self-request rules planned',
                'Planned payable ledger, supplier invoice, supplier payment, and deduction fields documented',
                'Dispatch Batch / Dispatch Report planning foundation page and permissions without dispatch writes',
                'Locked dispatch gate after Shipped with single-supplier batches and batch locking documented',
                'Batch reference format DDMMYYYY(COUNT_ORDER) and cost snapshot for Product Cost Payable planned',
                'Delivery Stop exception, future print/export/download output, and dispatch performance rules planned',
                'Planned dispatch report and dispatch report item fields documented without database writes',
                'Order Workflow planning foundation page and permissions without order sync or database writes',
                'Independent IBS workflow with main fulfillment stages and exception stages documented',
                'Allowed transition matrix, no move back to New Order, and dispatch report gate after Shipped',
                'Source/status mapping used only at import, IBS workflow stays independent after sync',
                'Cost snapshot for payable, action confirmation/activity log, and performance/safety rules planned',
                'Channel-neutral order workflow ready for other suppliers, sources, and manual/offline orders',
                'Product Control foundation page and permissions without database writes or OpenCart sync',
                'Current supplier context Iqbal & Brothers with channel-neutral product/cost/stock planning',
                'Read-only platform fields and supplier-editable model, cost, and stock rules documented',
                'Cost/stock history, low-stock warning, option image, and dispatch/payable cost snapshot rules',
                'Planned product and variant fields for future sync and supplier operations',
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
