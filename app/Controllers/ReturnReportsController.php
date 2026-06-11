<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Csrf;
use App\Domain\DispatchCostSnapshot;
use App\Domain\ReturnReceiveReason;
use App\Domain\ReturnReceiveType;
use App\Domain\ReturnReportReference;
use App\Domain\SupplierTerminology;
use App\Permission;
use App\ReadFoundation\WriteGate;
use App\Repositories\BusinessSourceRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductVariantRepository;
use App\Repositories\ReturnReceiveRepository;
use App\Repositories\ReturnReportRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Repositories\Write\ReturnReceiveWriteRepository;
use App\Services\Write\ReturnReportWriteService;
use App\SupplierContext;
use App\Support\OrderWorkflowRowPresenter;
use App\Support\ReturnStatementLinePresenter;

class ReturnReportsController extends Controller
{
    public function index()
    {
        $this->authorize('returns.view');
        ActivityLog::record('return_reports_access', 'Return reports list viewed');

        $supplierId = SupplierContext::enforceSupplierId(0);
        $eligibleContext = SupplierContext::isSupplier() ? [] : $this->buildEligibleReturnsContext();

        $this->render('return-reports.index', [
            'pageTitle' => 'Return Reports',
            'breadcrumbs' => [
                ['label' => 'Fulfillment', 'active' => false],
                ['label' => 'Return Reports', 'active' => true],
            ],
            'eligibleReturns' => $eligibleContext['returns'] ?? [],
            'eligibleSummary' => $eligibleContext['summary'] ?? [],
            'eligibleFilters' => $eligibleContext['filters'] ?? [],
            'businessSources' => $eligibleContext['business_sources'] ?? [],
            'latestReports' => $this->buildLatestReports($supplierId),
            'canManageReturns' => Permission::can('returns.manage'),
            'isSupplierView' => SupplierContext::isSupplier(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGate' => WriteGate::returnReports(),
            'writeGateReady' => WriteGate::returnReports()['ready'],
            'pendingReceiveUrl' => url('/return-receive'),
        ]);
    }

    public function create()
    {
        $this->authorize('returns.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/return-reports');
        }
        $this->redirectWithWriteResult('/return-reports', (new ReturnReportWriteService())->createStatement($_POST));
    }

    public function show($batch)
    {
        $this->authorize('returns.view');

        $supplierId = SupplierContext::enforceSupplierId(0);
        $batchReference = trim(rawurldecode((string) $batch));

        if ($batchReference !== '') {
            ActivityLog::record('return_report_view', 'Return report viewed: ' . $batchReference);
        }

        $reportDetail = $batchReference !== ''
            ? $this->buildReportDetailByReference($batchReference, $supplierId)
            : null;

        $this->render('return-reports.show', [
            'pageTitle' => $reportDetail !== null
                ? SupplierTerminology::supplierReturnStatement() . ' ' . ($reportDetail['report']['return_report_reference'] ?? '')
                : SupplierTerminology::supplierReturnStatement(),
            'breadcrumbs' => [
                ['label' => 'Fulfillment', 'active' => false],
                ['label' => 'Return Reports', 'active' => false, 'url' => '/return-reports'],
                ['label' => 'Statement', 'active' => true],
            ],
            'reportDetail' => $reportDetail,
            'batchReference' => $batchReference,
            'isSupplierView' => SupplierContext::isSupplier(),
        ]);
    }

    public function printStatement($batch)
    {
        $this->authorize('returns.view');

        $supplierId = SupplierContext::enforceSupplierId(0);
        $batchReference = trim(rawurldecode((string) $batch));
        $reportDetail = $batchReference !== ''
            ? $this->buildReportDetailByReference($batchReference, $supplierId)
            : null;

        if ($batchReference !== '' && $reportDetail !== null) {
            ActivityLog::record('return_report_print', 'Supplier return statement printed: ' . $batchReference);
        }

        view('return-reports.print', [
            'reportDetail' => $reportDetail,
            'isSupplierView' => SupplierContext::isSupplier(),
        ]);
        exit;
    }

    /**
     * @return array{returns: array<int, array<string, mixed>>, summary: array<string, int>, filters: array<string, int>, business_sources: array<int, string>}
     */
    private function buildEligibleReturnsContext(): array
    {
        $defaultSupplierId = (int) config('app.auth.supplier_id', 0);
        $supplierId = (int) ($_GET['supplier_id'] ?? $defaultSupplierId);
        $businessSourceId = (int) ($_GET['business_source_id'] ?? 0);

        $sourceNames = $this->businessSourceNameMap();
        $supplierNames = $this->supplierNameMap();
        $orderItems = new OrderItemRepository();
        $receiveWriter = new ReturnReceiveWriteRepository();

        try {
            $returns = (new ReturnReceiveRepository())->findEligibleForReport(50);
        } catch (\Throwable $e) {
            return [
                'returns' => [],
                'summary' => ['awaiting' => 0, 'missing_cost' => 0, 'missing_reason' => 0, 'shown' => 0],
                'filters' => ['supplier_id' => $supplierId, 'business_source_id' => $businessSourceId],
                'business_sources' => $sourceNames,
            ];
        }

        $eligible = [];
        $missingCostCount = 0;
        $missingReasonCount = 0;
        $missingOrderNoCount = 0;

        foreach ($returns as $return) {
            $returnReceiveId = (int) ($return['return_receive_id'] ?? 0);
            if ($returnReceiveId <= 0) {
                continue;
            }

            $returnSupplierId = (int) ($return['supplier_id'] ?? 0);
            $returnSourceId = (int) ($return['business_source_id'] ?? 0);
            if ($supplierId > 0 && $returnSupplierId !== $supplierId) {
                continue;
            }
            if ($businessSourceId > 0 && $returnSourceId !== $businessSourceId) {
                continue;
            }

            $orderId = $receiveWriter->resolveOrderId($returnReceiveId, $return);
            $order = $orderId > 0 ? (new OrderWriteRepository())->find($orderId) : null;

            $missingReason = trim((string) ($return['return_reason'] ?? '')) === ''
                || !ReturnReceiveReason::isKnown((string) ($return['return_reason'] ?? ''));
            if ($missingReason) {
                $missingReasonCount++;
            }

            $missingCost = false;
            $missingOrderNo = true;
            $previewCost = (float) ($return['total_cost_snapshot'] ?? 0);
            $previewQty = (int) ($return['total_items'] ?? 0);
            $displayOrderNo = '—';

            if ($order !== null) {
                $lines = $orderItems->findByOrderId($orderId);
                $missingCost = DispatchCostSnapshot::countMissingLineItems($lines) > 0 || $previewCost <= 0;
                if ($missingCost) {
                    $missingCostCount++;
                }
                $missingOrderNo = !DispatchCostSnapshot::hasDispatchOrderNo($order);
                if ($missingOrderNo) {
                    $missingOrderNoCount++;
                }
                $lineCost = $orderItems->sumSupplierCostByOrderId($orderId);
                $lineQty = $orderItems->sumQuantityByOrderId($orderId);
                $snapshot = DispatchCostSnapshot::forOrder($order, $lineCost, $lineQty);
                $previewCost = (float) $snapshot['product_cost_snapshot'];
                $previewQty = (int) $snapshot['item_count'];
                $displayOrderNo = OrderWorkflowRowPresenter::formatOrderNo($order);
            } elseif ($previewCost <= 0) {
                $missingCost = true;
                $missingCostCount++;
            }

            $eligible[] = array_merge($return, [
                'order_id' => $orderId,
                'preview_cost_snapshot' => $previewCost,
                'preview_item_count' => $previewQty,
                'missing_cost' => $missingCost,
                'missing_reason' => $missingReason,
                'missing_order_no' => $missingOrderNo,
                'display_order_no' => $displayOrderNo,
                'return_type_label' => ReturnReceiveType::label((string) ($return['return_type'] ?? '')),
                'return_reason_label' => $missingReason
                    ? '—'
                    : ReturnReceiveReason::label((string) ($return['return_reason'] ?? '')),
                'supplier_name' => $supplierNames[$returnSupplierId] ?? '—',
                'business_source_name' => $sourceNames[$returnSourceId] ?? '—',
            ]);
        }

        return [
            'returns' => $eligible,
            'summary' => [
                'awaiting' => count($eligible),
                'missing_cost' => $missingCostCount,
                'missing_reason' => $missingReasonCount,
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
            $repository = new ReturnReportRepository();
            $reports = $repository->latestForSupplier($supplierId, 50);
            if ($reports === []) {
                return [];
            }

            $supplierNames = $this->supplierNameMap();
            $sourceNames = $this->businessSourceNameMap();

            foreach ($reports as $index => $report) {
                $supplierKey = (int) ($report['supplier_id'] ?? 0);
                $sourceKey = (int) ($report['business_source_id'] ?? 0);
                $reports[$index]['status_label'] = ReturnReportReference::statusLabel(
                    (string) ($report['status'] ?? '')
                );
                $reports[$index]['supplier_name'] = $supplierNames[$supplierKey] ?? '—';
                $reports[$index]['business_source_name'] = $sourceNames[$sourceKey] ?? '—';
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
            $repository = new ReturnReportRepository();
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

        $reportId = (int) ($report['return_report_id'] ?? 0);
        if ($reportId <= 0) {
            return null;
        }

        $repository = new ReturnReportRepository();
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

        $linePayload = ReturnStatementLinePresenter::build(
            $items,
            $sourceNames,
            $itemsByOrder,
            $productsById,
            $variantsByProduct,
            $report
        );

        $report['status_label'] = ReturnReportReference::statusLabel(
            (string) ($report['status'] ?? '')
        );

        $supplierKey = (int) ($report['supplier_id'] ?? 0);
        $sourceKey = (int) ($report['business_source_id'] ?? 0);

        return [
            'report' => $report,
            'items' => $items,
            'product_rows' => $linePayload['product_rows'],
            'total_quantity' => (int) ($linePayload['total_quantity'] ?? 0),
            'total_amount' => (float) ($linePayload['total_amount'] ?? 0),
            'legacy_warning' => $linePayload['legacy_warning'] ?? null,
            'lines_empty' => (bool) ($linePayload['lines_empty'] ?? true),
            'supplier_name' => $supplierNames[$supplierKey] ?? '—',
            'business_source_name' => $sourceNames[$sourceKey] ?? '—',
            'prepared_by' => $this->resolvePreparedByLabel($report),
            'ledger_notice' => ReturnReportReference::LEDGER_HOOK_NOTE,
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
