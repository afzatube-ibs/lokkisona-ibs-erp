<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Csrf;
use App\Domain\DispatchCostSnapshot;
use App\Domain\DispatchReportReference;
use App\Domain\SupplierTerminology;
use App\Permission;
use App\ReadFoundation\WriteGate;
use App\Repositories\BusinessSourceRepository;
use App\Repositories\DispatchReportRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductVariantRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Services\Write\DispatchReportWriteService;
use App\Services\Write\PayableLedgerWriteService;
use App\SupplierContext;
use App\Support\DispatchStatementLinePresenter;
use App\Support\OrderWorkflowRowPresenter;

class DispatchReportsController extends Controller
{
    public function index()
    {
        $this->authorize('dispatch_reports.view');
        ActivityLog::record('dispatch_reports_access', 'Dispatch reports list viewed');

        $supplierId = SupplierContext::enforceSupplierId(0);

        $eligibleContext = SupplierContext::isSupplier() ? [] : $this->buildEligibleOrdersContext();

        $this->render('dispatch-reports.index', [
            'pageTitle' => 'Daily Dispatch',
            'breadcrumbs' => [
                ['label' => 'Fulfillment', 'active' => false],
                ['label' => 'Daily Dispatch', 'active' => true],
            ],
            'eligibleOrders' => $eligibleContext['orders'] ?? [],
            'eligibleSummary' => $eligibleContext['summary'] ?? [],
            'eligibleFilters' => $eligibleContext['filters'] ?? [],
            'businessSources' => $eligibleContext['business_sources'] ?? [],
            'latestReports' => $this->buildLatestReports($supplierId),
            'canManageDispatch' => Permission::can('dispatch_reports.manage'),
            'isSupplierView' => SupplierContext::isSupplier(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGate' => WriteGate::dispatchReports(),
            'writeGateReady' => WriteGate::dispatchReports()['ready'],
        ]);
    }

    public function view()
    {
        $this->authorize('dispatch_reports.view');

        $reportId = (int) ($_GET['id'] ?? 0);
        $reference = trim((string) ($_GET['ref'] ?? ''));

        if ($reportId > 0) {
            try {
                $repository = new DispatchReportRepository();
                if ($repository->tableExists()) {
                    $report = $repository->findById($reportId);
                    if ($report !== null && !empty($report['dispatch_reference'])) {
                        redirect('/dispatch-report/' . rawurlencode((string) $report['dispatch_reference']));
                    }
                }
            } catch (\Throwable $e) {
                // fall through to not-found show
            }
        }

        if ($reference !== '') {
            redirect('/dispatch-report/' . rawurlencode($reference));
        }

        $this->show('');
    }

    public function show($batch)
    {
        $this->authorize('dispatch_reports.view');

        $supplierId = SupplierContext::enforceSupplierId(0);
        $batchReference = trim(rawurldecode((string) $batch));
        $printMode = !empty($_GET['print']);

        if ($batchReference !== '') {
            ActivityLog::record('dispatch_report_view', 'Dispatch report batch viewed: ' . $batchReference);
        }

        $reportDetail = $batchReference !== ''
            ? $this->buildReportDetailByReference($batchReference, $supplierId)
            : null;

        $this->render('dispatch-reports.show', [
            'pageTitle' => $reportDetail !== null
                ? SupplierTerminology::dailyDispatchStatement() . ' ' . ($reportDetail['report']['dispatch_reference'] ?? '')
                : SupplierTerminology::dailyDispatchStatement(),
            'breadcrumbs' => [
                ['label' => 'Fulfillment', 'active' => false],
                ['label' => 'Daily Dispatch', 'active' => false, 'url' => '/dispatch-reports'],
                ['label' => 'Statement', 'active' => true],
            ],
            'reportDetail' => $reportDetail,
            'batchReference' => $batchReference,
            'printMode' => $printMode,
            'isSupplierView' => SupplierContext::isSupplier(),
            'bodyClass' => $printMode ? 'admin-body--dispatch-print' : '',
        ]);
    }

    public function create()
    {
        $this->authorize('dispatch_reports.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/dispatch-reports');
        }
        $this->redirectWithWriteResult('/dispatch-reports', (new DispatchReportWriteService())->createDailyBatch($_POST));
    }

    public function printStatement($batch)
    {
        $this->authorize('dispatch_reports.view');

        $supplierId = SupplierContext::enforceSupplierId(0);
        $batchReference = trim(rawurldecode((string) $batch));
        $reportDetail = $batchReference !== ''
            ? $this->buildReportDetailByReference($batchReference, $supplierId)
            : null;

        if ($batchReference !== '' && $reportDetail !== null) {
            ActivityLog::record('dispatch_report_print', 'Daily dispatch statement printed: ' . $batchReference);
        }

        view('dispatch-reports.print', [
            'reportDetail' => $reportDetail,
            'isSupplierView' => SupplierContext::isSupplier(),
        ]);
        exit;
    }

    /**
     * @return array{orders: array<int, array<string, mixed>>, summary: array<string, int>, filters: array<string, int>, business_sources: array<int, string>}
     */
    private function buildEligibleOrdersContext(): array
    {
        $defaultSupplierId = (int) config('app.auth.supplier_id', 0);
        $supplierId = (int) ($_GET['supplier_id'] ?? $defaultSupplierId);
        $businessSourceId = (int) ($_GET['business_source_id'] ?? 0);

        $businessSources = $this->businessSourceNameMap();
        $sourceNames = $businessSources;

        try {
            $orders = (new OrderWriteRepository())->findShippedEligible(50, true, $supplierId, $businessSourceId);
        } catch (\Throwable $e) {
            return [
                'orders' => [],
                'summary' => ['awaiting' => 0, 'missing_cost' => 0, 'shown' => 0],
                'filters' => ['supplier_id' => $supplierId, 'business_source_id' => $businessSourceId],
                'business_sources' => $sourceNames,
            ];
        }

        $orderItems = new OrderItemRepository();
        $eligible = [];
        $missingCostCount = 0;
        $missingOrderNoCount = 0;

        foreach ($orders as $order) {
            $orderId = (int) ($order['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $lines = $orderItems->findByOrderId($orderId);
            $missingLines = DispatchCostSnapshot::countMissingLineItems($lines);
            if ($missingLines > 0) {
                $missingCostCount++;
            }
            $missingOrderNo = !DispatchCostSnapshot::hasDispatchOrderNo($order);
            if ($missingOrderNo) {
                $missingOrderNoCount++;
            }
            $lineCost = $orderItems->sumSupplierCostByOrderId($orderId);
            $lineQty = $orderItems->sumQuantityByOrderId($orderId);
            $snapshot = DispatchCostSnapshot::forOrder($order, $lineCost, $lineQty);
            $sourceKey = (int) ($order['business_source_id'] ?? 0);

            $eligible[] = array_merge($order, [
                'preview_cost_snapshot' => $snapshot['product_cost_snapshot'],
                'preview_item_count' => $snapshot['item_count'],
                'missing_cost' => $missingLines > 0,
                'missing_line_count' => $missingLines,
                'missing_order_no' => $missingOrderNo,
                'display_order_no' => OrderWorkflowRowPresenter::formatOrderNo($order),
                'business_source_name' => $sourceNames[$sourceKey] ?? '—',
            ]);
        }

        return [
            'orders' => $eligible,
            'summary' => [
                'awaiting' => count($eligible),
                'missing_cost' => $missingCostCount,
                'missing_order_no' => $missingOrderNoCount,
                'shown' => count($eligible),
            ],
            'filters' => [
                'supplier_id' => $supplierId,
                'business_source_id' => $businessSourceId,
            ],
            'business_sources' => $sourceNames,
        ];
    }

    private function buildLatestReports(int $supplierId = 0): array
    {
        try {
            $repository = new DispatchReportRepository();
            $reports = $repository->latestForSupplier($supplierId, 50);
            if ($reports === []) {
                return [];
            }

            $reportIds = array_map(static fn (array $row): int => (int) ($row['dispatch_report_id'] ?? 0), $reports);
            $qtyByReport = $repository->sumItemCountsByReportIds($reportIds);
            $supplierNames = $this->supplierNameMap();
            $sourceNames = $this->businessSourceNameMap();

            foreach ($reports as $index => $report) {
                $reportId = (int) ($report['dispatch_report_id'] ?? 0);
                $supplierKey = (int) ($report['supplier_id'] ?? 0);
                $sourceKey = (int) ($report['business_source_id'] ?? 0);
                $reports[$index]['status_label'] = DispatchReportReference::statusLabel(
                    (string) ($report['status'] ?? '')
                );
                $reports[$index]['supplier_name'] = $supplierNames[$supplierKey] ?? '—';
                $reports[$index]['business_source_name'] = $sourceNames[$sourceKey] ?? '—';
                $reports[$index]['total_qty'] = $qtyByReport[$reportId] ?? 0;
                $reports[$index]['created_by_label'] = $this->resolvePreparedByLabel($report);
            }

            return $reports;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    private function supplierNameMap(): array
    {
        try {
            $repo = new SupplierRepository();
            if (!$repo->tableExists()) {
                return [];
            }

            $map = [];
            foreach ($repo->all(100, 0) as $row) {
                $id = (int) ($row['supplier_id'] ?? 0);
                if ($id > 0) {
                    $map[$id] = (string) ($row['supplier_name'] ?? '');
                }
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    private function businessSourceNameMap(): array
    {
        try {
            $repo = new BusinessSourceRepository();
            if (!$repo->tableExists()) {
                return [];
            }

            $map = [];
            foreach ($repo->all(100, 0) as $row) {
                $id = (int) ($row['business_source_id'] ?? 0);
                if ($id > 0) {
                    $map[$id] = (string) ($row['source_name'] ?? '');
                }
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function buildReportDetailByReference(string $reference, int $supplierId = 0): ?array
    {
        if ($reference === '') {
            return null;
        }

        try {
            $repository = new DispatchReportRepository();
            if (!$repository->tableExists()) {
                return null;
            }

            $report = $repository->findByReference($reference);
            if ($report === null) {
                return null;
            }

            return $this->enrichReportDetail($report, $supplierId);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $report
     * @return array<string, mixed>|null
     */
    private function enrichReportDetail(array $report, int $supplierId = 0): ?array
    {
        if ($supplierId > 0 && (int) ($report['supplier_id'] ?? 0) !== $supplierId) {
            return null;
        }

        $reportId = (int) ($report['dispatch_report_id'] ?? 0);
        $dispatchReference = trim((string) ($report['dispatch_reference'] ?? ''));
        if ($reportId <= 0 && $dispatchReference === '') {
            return null;
        }

        $repository = new DispatchReportRepository();
        $items = $repository->findItemsForReport($report);
        $orderItemRepo = new OrderItemRepository();
        $productRepo = new ProductRepository();
        $variantRepo = new ProductVariantRepository();

        $orderIds = array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['order_id'] ?? 0),
            $items
        ), static fn (int $id): bool => $id > 0));

        $itemsByOrder = $orderItemRepo->groupedByOrderIds($orderIds);

        $productIds = [];
        foreach ($itemsByOrder as $orderLines) {
            foreach ($orderLines as $line) {
                $pid = (int) ($line['product_id'] ?? 0);
                if ($pid > 0) {
                    $productIds[$pid] = $pid;
                }
            }
        }
        $productsById = $productRepo->indexedByIds(array_values($productIds));
        $variantsByProduct = $variantRepo->groupedByProductIds(array_values($productIds));

        $supplierNames = $this->supplierNameMap();
        $sourceNames = $this->businessSourceNameMap();

        $linePayload = DispatchStatementLinePresenter::build(
            $items,
            $sourceNames,
            $itemsByOrder,
            $productsById,
            $variantsByProduct,
            $report
        );

        $flatProductRows = $linePayload['product_rows'];
        $totalQuantity = (int) ($linePayload['total_quantity'] ?? 0);
        $totalAmount = (float) ($linePayload['total_amount'] ?? 0);

        $report['status_label'] = DispatchReportReference::statusLabel(
            (string) ($report['status'] ?? '')
        );

        $supplierKey = (int) ($report['supplier_id'] ?? 0);
        $sourceKey = (int) ($report['business_source_id'] ?? 0);
        $dispatchReference = (string) ($report['dispatch_reference'] ?? '');

        $payableNotice = 'Payable checkpoint not created yet — owner can create from Supplier Payables when ready.';
        $payableDraftRef = null;
        $payableStatus = null;
        $payableLedgerId = null;
        try {
            $payable = (new PayableLedgerWriteService())->payableStatusForDispatch($dispatchReference);
            if ($payable !== null) {
                $payableDraftRef = (string) ($payable['ledger_reference'] ?? ('PCP-' . $dispatchReference));
                $payableStatus = (string) ($payable['status'] ?? 'draft');
                $payableLedgerId = (int) ($payable['payable_ledger_id'] ?? 0);
                $payableNotice = 'Payable checkpoint ' . $payableDraftRef . ' (' . $payableStatus . ') — review on Supplier Payables. No ledger posting from this page.';
            }
        } catch (\Throwable $e) {
            // non-blocking
        }

        return [
            'report' => $report,
            'items' => $items,
            'product_rows' => $flatProductRows,
            'total_quantity' => $totalQuantity,
            'total_amount' => $totalAmount,
            'legacy_warning' => $linePayload['legacy_warning'] ?? null,
            'lines_empty' => (bool) ($linePayload['lines_empty'] ?? true),
            'supplier_name' => $supplierNames[$supplierKey] ?? '—',
            'business_source_name' => $sourceNames[$sourceKey] ?? '—',
            'prepared_by' => $this->resolvePreparedByLabel($report),
            'payable_notice' => $payableNotice,
            'payable_draft_ref' => $payableDraftRef,
            'payable_status' => $payableStatus,
            'payable_ledger_id' => $payableLedgerId > 0 ? $payableLedgerId : null,
            'payable_url' => url('/supplier-payables'),
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function resolvePreparedByLabel(array $report): string
    {
        $lockedBy = (int) ($report['locked_by'] ?? 0);
        $createdBy = (int) ($report['created_by'] ?? 0);
        $userId = $createdBy > 0 ? $createdBy : $lockedBy;

        if ($userId <= 0) {
            return '—';
        }

        try {
            $user = (new \App\Repositories\UserRepository())->findById($userId);

            return trim((string) ($user['username'] ?? '')) !== ''
                ? (string) $user['username']
                : '—';
        } catch (\Throwable $e) {
            return '—';
        }
    }
}
