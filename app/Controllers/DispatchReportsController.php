<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Csrf;
use App\Domain\DispatchCostSnapshot;
use App\Domain\DispatchReportReference;
use App\Permission;
use App\ReadFoundation\WriteGate;
use App\Repositories\BusinessSourceRepository;
use App\Repositories\DispatchReportRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderWorkflowHistoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductVariantRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Services\Write\DispatchReportWriteService;
use App\Services\Write\PayableLedgerWriteService;
use App\SupplierContext;
use App\Support\OrderWorkflowRowPresenter;

class DispatchReportsController extends Controller
{
    public function index()
    {
        $this->authorize('dispatch_reports.view');
        ActivityLog::record('dispatch_reports_access', 'Dispatch reports list viewed');

        $supplierId = SupplierContext::enforceSupplierId(0);

        $this->render('dispatch-reports.index', [
            'pageTitle' => 'Dispatch Reports',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Dispatch Reports', 'active' => true],
            ],
            'eligibleOrders' => SupplierContext::isSupplier() ? [] : $this->buildEligibleOrders(),
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
                ? 'Dispatch Report ' . ($reportDetail['report']['dispatch_reference'] ?? '')
                : 'Dispatch Report',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Dispatch Reports', 'active' => false, 'url' => '/dispatch-reports'],
                ['label' => 'Report', 'active' => true],
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

    private function buildEligibleOrders(): array
    {
        try {
            $orders = (new OrderWriteRepository())->findShippedEligible(50);
        } catch (\Throwable $e) {
            return [];
        }

        $orderItems = new OrderItemRepository();
        $eligible = [];

        foreach ($orders as $order) {
            $orderId = (int) ($order['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $lines = $orderItems->findByOrderId($orderId);
            $missingLines = DispatchCostSnapshot::countMissingLineItems($lines);
            $lineCost = $orderItems->sumSupplierCostByOrderId($orderId);
            $lineQty = $orderItems->sumQuantityByOrderId($orderId);
            $snapshot = DispatchCostSnapshot::forOrder($order, $lineCost, $lineQty);

            $eligible[] = array_merge($order, [
                'preview_cost_snapshot' => $snapshot['product_cost_snapshot'],
                'preview_item_count' => $snapshot['item_count'],
                'missing_cost' => $missingLines > 0,
                'missing_line_count' => $missingLines,
            ]);
        }

        return $eligible;
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

            foreach ($reports as $index => $report) {
                $reportId = (int) ($report['dispatch_report_id'] ?? 0);
                $supplierKey = (int) ($report['supplier_id'] ?? 0);
                $reports[$index]['status_label'] = DispatchReportReference::statusLabel(
                    (string) ($report['status'] ?? '')
                );
                $reports[$index]['supplier_name'] = $supplierNames[$supplierKey] ?? '—';
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
        if ($reportId <= 0) {
            return null;
        }

        $repository = new DispatchReportRepository();
        $items = $repository->findItemsWithOrders($reportId);
        $orderItemRepo = new OrderItemRepository();
        $productRepo = new ProductRepository();
        $variantRepo = new ProductVariantRepository();
        $historyRepo = new OrderWorkflowHistoryRepository();

        $orderIds = array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['order_id'] ?? 0),
            $items
        ), static fn (int $id): bool => $id > 0));

        $itemsByOrder = $orderItemRepo->groupedByOrderIds($orderIds);
        $importHistories = $historyRepo->tableExists()
            ? $historyRepo->findImportNotesByOrderIds($orderIds)
            : [];

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

        $totalQuantity = 0;
        $flatProductRows = [];

        foreach ($items as $index => $item) {
            $orderId = (int) ($item['order_id'] ?? 0);
            $lineQty = (int) ($item['item_count'] ?? 0);
            $totalQuantity += $lineQty;

            $orderRow = [
                'order_id' => $orderId,
                'origin_order_status_name' => $item['origin_order_status_name'] ?? null,
                'source_order_status' => $item['source_order_status'] ?? null,
            ];
            $ocStatus = OrderWorkflowRowPresenter::resolveSourceOrderStatus(
                $orderRow,
                $importHistories[$orderId] ?? null
            );

            $formattedLines = OrderWorkflowRowPresenter::formatProductLines(
                $itemsByOrder[$orderId] ?? [],
                $productsById,
                $variantsByProduct
            );

            $items[$index]['product_lines'] = $formattedLines;
            $items[$index]['line_cost_total'] = round((float) ($item['product_cost_snapshot'] ?? 0), 2);
            $items[$index]['order_no'] = $this->formatDispatchOrderNo($item);
            $items[$index]['courier_status'] = trim((string) ($item['courier_status'] ?? '')) ?: '—';
            $items[$index]['consignment_id'] = trim((string) ($item['tracking_number'] ?? '')) ?: 'Not Assigned';
            $items[$index]['oc_order_status'] = $ocStatus ?? '—';

            foreach ($formattedLines as $line) {
                $flatProductRows[] = [
                    'order_no' => $items[$index]['order_no'],
                    'customer_name' => (string) ($item['customer_name'] ?? ''),
                    'customer_phone' => trim((string) ($item['customer_phone'] ?? '')) ?: '—',
                    'image_url' => $line['image_url'] ?? null,
                    'model' => (string) ($line['model'] ?? ''),
                    'option_chips' => $line['option_chips'] ?? [],
                    'quantity' => (int) ($line['quantity'] ?? 0),
                    'unit_cost_snapshot' => round((float) ($line['cost_snapshot'] ?? 0), 2),
                    'line_cost_snapshot' => round((float) ($line['line_cost_total'] ?? 0), 2),
                    'courier_status' => $items[$index]['courier_status'],
                    'consignment_id' => $items[$index]['consignment_id'],
                    'oc_order_status' => $items[$index]['oc_order_status'],
                ];
            }
        }

        $report['status_label'] = DispatchReportReference::statusLabel(
            (string) ($report['status'] ?? '')
        );

        $supplierNames = $this->supplierNameMap();
        $sourceNames = $this->businessSourceNameMap();
        $supplierKey = (int) ($report['supplier_id'] ?? 0);
        $sourceKey = (int) ($report['business_source_id'] ?? 0);
        $dispatchReference = (string) ($report['dispatch_reference'] ?? '');

        $payableNotice = 'Payable draft pending finance module completion.';
        $payableDraftRef = null;
        try {
            $payable = (new PayableLedgerWriteService())->payableStatusForDispatch($dispatchReference);
            if ($payable !== null) {
                $payableDraftRef = (string) ($payable['ledger_reference'] ?? ('PCP-' . $dispatchReference));
                $payableNotice = 'Payable draft ' . $payableDraftRef . ' recorded — settlement pending finance module completion.';
            }
        } catch (\Throwable $e) {
            // non-blocking
        }

        return [
            'report' => $report,
            'items' => $items,
            'product_rows' => $flatProductRows,
            'total_quantity' => $totalQuantity,
            'supplier_name' => $supplierNames[$supplierKey] ?? '—',
            'business_source_name' => $sourceNames[$sourceKey] ?? '—',
            'prepared_by' => $this->resolvePreparedByLabel($report),
            'payable_notice' => $payableNotice,
            'payable_draft_ref' => $payableDraftRef,
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function formatDispatchOrderNo(array $item): string
    {
        $ref = trim((string) ($item['erp_order_reference'] ?? $item['order_reference'] ?? ''));
        if ($ref === '') {
            return '#' . (int) ($item['order_id'] ?? 0);
        }

        return str_starts_with($ref, '#') ? $ref : '#' . $ref;
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
