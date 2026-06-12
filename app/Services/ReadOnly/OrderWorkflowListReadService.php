<?php

namespace App\Services\ReadOnly;

use App\Database\Connection;
use App\Database\QueryGuard;
use App\Database\TableName;
use App\Domain\OrderWorkflowStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\SchemaColumnProbe;
use App\Repositories\DispatchReportRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderWorkflowHistoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductVariantRepository;
use App\Support\OrderWorkflowRowPresenter;
use App\Support\RequestTimer;
use App\Support\SimpleFileCache;
use PDO;

/**
 * SQL-paginated vendor fulfillment list (v1.9.2) — local ERP snapshot only.
 */
class OrderWorkflowListReadService
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 50;

    private const CACHE_TTL = 60;

    private OrderItemRepository $orderItems;

    private ProductRepository $products;

    private ProductVariantRepository $variants;

    private DispatchReportRepository $dispatchReports;

    private OrderWorkflowHistoryRepository $histories;

    private SimpleFileCache $cache;

    public function __construct(
        ?OrderItemRepository $orderItems = null,
        ?ProductRepository $products = null,
        ?ProductVariantRepository $variants = null,
        ?DispatchReportRepository $dispatchReports = null,
        ?OrderWorkflowHistoryRepository $histories = null,
        ?SimpleFileCache $cache = null
    ) {
        $this->orderItems = $orderItems ?? new OrderItemRepository();
        $this->products = $products ?? new ProductRepository();
        $this->variants = $variants ?? new ProductVariantRepository();
        $this->dispatchReports = $dispatchReports ?? new DispatchReportRepository();
        $this->histories = $histories ?? new OrderWorkflowHistoryRepository();
        $this->cache = $cache ?? new SimpleFileCache('order-workflow');
    }

    public function tableExists(): bool
    {
        try {
            return (new OrderReadService())->tableExists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function listPage(array $filters, int $page, int $supplierId, ?RequestTimer $timer = null): array
    {
        $normalized = $this->normalizeFilters($filters);
        $page = max(1, $page);
        $perPage = min(self::MAX_PER_PAGE, max(self::DEFAULT_PER_PAGE, (int) ($normalized['per_page'] ?? self::DEFAULT_PER_PAGE)));
        $offset = ($page - 1) * $perPage;

        if (!$this->tableExists()) {
            return [
                'rows' => [],
                'pagination' => $this->emptyPagination($page, $perPage),
                'filters' => $normalized,
                'stage_counts' => [],
            ];
        }

        $dispatchMeta = $this->dispatchReports->findIncludedOrderMeta(500);
        $dispatchReferences = [];
        foreach ($dispatchMeta as $orderId => $meta) {
            $dispatchReferences[$orderId] = $meta['dispatch_reference'];
        }
        if ($timer !== null) {
            $timer->lap('dispatch_refs');
        }

        $includedOrderIds = array_map('intval', array_keys($dispatchMeta));
        [$whereSql, $params, $joinSql] = $this->buildWhere($normalized, $supplierId, $includedOrderIds);
        $total = $this->countOrders($joinSql, $whereSql, $params, $timer);
        $orders = $this->fetchOrderPage($joinSql, $whereSql, $params, $offset, $perPage, $timer);

        $orderIds = array_map(static fn (array $row): int => (int) ($row['order_id'] ?? 0), $orders);
        $itemsByOrder = $this->orderItems->groupedByOrderIds($orderIds);
        if ($timer !== null) {
            $timer->lap('order_items');
        }

        $productIds = [];
        foreach ($itemsByOrder as $items) {
            foreach ($items as $item) {
                $pid = (int) ($item['product_id'] ?? 0);
                if ($pid > 0) {
                    $productIds[$pid] = $pid;
                }
            }
        }
        $productsById = $this->products->indexedByIds(array_values($productIds));
        $variantsByProduct = $this->variants->groupedByProductIds(array_values($productIds));
        if ($timer !== null) {
            $timer->lap('product_enrich');
        }

        $importHistories = $this->histories->findImportNotesByOrderIds($orderIds);

        $rows = [];
        foreach ($orders as $order) {
            $orderId = (int) ($order['order_id'] ?? 0);
            $dispatchReference = $dispatchReferences[$orderId] ?? null;
            $dispatchReportId = isset($dispatchMeta[$orderId])
                ? (int) ($dispatchMeta[$orderId]['dispatch_report_id'] ?? 0)
                : null;
            $batchLocked = $dispatchReference !== null && $dispatchReference !== '';

            $productLines = OrderWorkflowRowPresenter::formatProductLines(
                $itemsByOrder[$orderId] ?? [],
                $productsById,
                $variantsByProduct
            );

            $rows[] = OrderWorkflowRowPresenter::buildRow(
                $order,
                $dispatchReference,
                ($dispatchReportId ?? 0) > 0 ? $dispatchReportId : null,
                $batchLocked,
                $productLines,
                OrderWorkflowRowPresenter::resolveSourceOrderStatus($order, $importHistories[$orderId] ?? null)
            );
        }

        if ($timer !== null) {
            $timer->lap('row_build');
        }

        return [
            'rows' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
                'has_previous' => $page > 1,
                'has_next' => ($offset + $perPage) < $total,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
            'filters' => $normalized,
            'stage_counts' => $this->stageCounts($supplierId, $dispatchReferences, $timer),
        ];
    }

    /**
     * @param array<int, string> $dispatchReferences
     * @return array<string, int>
     */
    public function stageCounts(int $supplierId, array $dispatchReferences = [], ?RequestTimer $timer = null): array
    {
        $cacheKey = 'stage_counts_v2_' . $supplierId;
        $cached = $this->cache->get($cacheKey, self::CACHE_TTL);
        if (is_array($cached)) {
            return $cached;
        }

        if ($dispatchReferences === [] && $this->dispatchTablesReady()) {
            $dispatchReferences = $this->dispatchReports->findIncludedOrderReferences(500);
        }

        $counts = [];
        foreach (OrderWorkflowStatus::groupOrder() as $code) {
            $counts[$code] = 0;
        }

        if (!$this->tableExists()) {
            return $counts;
        }

        try {
            $orderTable = TableName::forModel(Order::class);
            $where = 'WHERE 1=1';
            $params = [];
            if ($supplierId > 0) {
                $where .= ' AND o.supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }

            $sql = 'SELECT o.order_id, o.ibs_status FROM `' . $this->escapeIdentifier($orderTable) . '` o ' . $where;
            QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo()->prepare($sql);
            $statement->execute($params);
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                $group = OrderWorkflowStatus::filterBucket(
                    (string) ($row['ibs_status'] ?? 'new_order'),
                    isset($dispatchReferences[$orderId])
                );
                if (isset($counts[$group])) {
                    $counts[$group]++;
                } else {
                    $counts[$group] = 1;
                }
            }
        } catch (\Throwable $e) {
            return $counts;
        }

        if ($timer !== null) {
            $timer->lap('stage_counts');
        }

        $this->cache->set($cacheKey, $counts);

        return $counts;
    }

    public function invalidateCache(): void
    {
        $this->cache->flush();
    }

    public function dispatchTablesReady(): bool
    {
        return $this->tryDispatchTablesExist();
    }

    /**
     * @return array<int, int>
     */
    public function loadIncludedDispatchOrderIds(int $limit = 500): array
    {
        $ids = [];
        foreach (array_keys($this->dispatchReports->findIncludedOrderMeta($limit)) as $orderId) {
            $ids[(int) $orderId] = (int) $orderId;
        }

        return $ids;
    }

    /**
     * Raw ibs_status histogram for dev debug (supplier-scoped).
     *
     * @return array<string, int>
     */
    public function rawStatusHistogram(int $supplierId = 0, int $limit = 10): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        try {
            $orderTable = TableName::forModel(Order::class);
            $where = 'WHERE 1=1';
            $params = [];
            if ($supplierId > 0) {
                $where .= ' AND supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }

            $sql = 'SELECT ibs_status, COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($orderTable) . '` '
                . $where . ' GROUP BY ibs_status ORDER BY row_count DESC LIMIT ' . max(1, min($limit, 50));
            QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo()->prepare($sql);
            $statement->execute($params);
            $histogram = [];
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $key = trim((string) ($row['ibs_status'] ?? ''));
                if ($key === '') {
                    $key = '(empty)';
                }
                $histogram[$key] = (int) ($row['row_count'] ?? 0);
            }

            return $histogram;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function tryDispatchTablesExist(): bool
    {
        try {
            $database = config('database.database', '');
            $prefix = config('database.prefix', 'ibs_');
            $tables = [$prefix . 'dispatch_reports', $prefix . 'dispatch_report_items'];
            $check = $this->pdo()->prepare(
                'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
            );
            foreach ($tables as $tableName) {
                $check->execute(['schema' => $database, 'table' => $tableName]);
                $row = $check->fetch(PDO::FETCH_ASSOC);
                if (((int) ($row['table_count'] ?? 0)) === 0) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Compact operational stats for vendor fulfillment header (read-only UI).
     *
     * @param array<string, int> $stageCounts
     * @return array<string, int>
     */
    public function operationalSummary(array $stageCounts, int $supplierId = 0): array
    {
        $waiting = (int) ($stageCounts['new_order'] ?? 0) + (int) ($stageCounts['order_received'] ?? 0);

        $summary = [
            'orders_waiting' => $waiting,
            'packaging_queue' => (int) ($stageCounts['packaging'] ?? 0),
            'ready_for_dispatch' => (int) ($stageCounts['shipped'] ?? 0),
            'low_cost_data' => 0,
            'missing_models' => 0,
        ];

        if (!$this->tableExists() || !$this->orderItems->tableExists()) {
            return $summary;
        }

        try {
            $orderTable = TableName::forModel(Order::class);
            $itemTable = TableName::forModel(OrderItem::class);
            $productTable = TableName::forModel(Product::class);
            $supplierSql = '';
            $params = [];
            if ($supplierId > 0) {
                $supplierSql = ' AND o.supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }

            $lowCostSql = 'SELECT COUNT(DISTINCT o.order_id) AS c FROM `' . $this->escapeIdentifier($orderTable) . '` o '
                . 'INNER JOIN `' . $this->escapeIdentifier($itemTable) . '` oi ON oi.order_id = o.order_id '
                . 'WHERE (oi.supplier_cost_snapshot IS NULL OR oi.supplier_cost_snapshot <= 0)'
                . " AND o.ibs_status NOT IN ('cancelled', 'delivered')" . $supplierSql;
            QueryGuard::assertReadOnly($lowCostSql);
            $stmt = $this->pdo()->prepare($lowCostSql);
            $stmt->execute($params);
            $summary['low_cost_data'] = (int) ($stmt->fetchColumn() ?: 0);

            $missingModelSql = 'SELECT COUNT(DISTINCT o.order_id) AS c FROM `' . $this->escapeIdentifier($orderTable) . '` o '
                . 'INNER JOIN `' . $this->escapeIdentifier($itemTable) . '` oi ON oi.order_id = o.order_id '
                . 'LEFT JOIN `' . $this->escapeIdentifier($productTable) . '` p ON p.product_id = oi.product_id '
                . "WHERE TRIM(COALESCE(oi.product_name, '')) = '' "
                . "AND TRIM(COALESCE(p.supplier_model, p.source_model, '')) = '' "
                . "AND o.ibs_status NOT IN ('cancelled', 'delivered')" . $supplierSql;
            QueryGuard::assertReadOnly($missingModelSql);
            $stmt = $this->pdo()->prepare($missingModelSql);
            $stmt->execute($params);
            $summary['missing_models'] = (int) ($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            return $summary;
        }

        return $summary;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && !OrderWorkflowStatus::isKnownBucket($status)) {
            $status = '';
        } elseif ($status !== '') {
            $status = OrderWorkflowStatus::normalize($status);
        }

        $perPage = (int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE);
        $perPage = min(self::MAX_PER_PAGE, max(self::DEFAULT_PER_PAGE, $perPage));

        $showDemo = in_array((string) ($filters['show_demo'] ?? ''), ['1', 'on', 'yes'], true);

        return [
            'status' => $status,
            'supplier_id' => max(0, (int) ($filters['supplier_id'] ?? 0)),
            'courier_status' => trim((string) ($filters['courier_status'] ?? '')),
            'date_from' => trim((string) ($filters['date_from'] ?? '')),
            'date_to' => trim((string) ($filters['date_to'] ?? '')),
            'q' => trim((string) ($filters['q'] ?? '')),
            'per_page' => $perPage,
            'show_demo' => $showDemo,
        ];
    }

    /**
     * @param array<int, int> $includedOrderIds
     * @return array{0: string, 1: array<string, mixed>, 2: string}
     */
    private function buildWhere(array $filters, int $supplierId, array $includedOrderIds = []): array
    {
        $joinSql = '';
        $where = 'WHERE 1=1';
        $params = [];

        $effectiveSupplier = $supplierId > 0 ? $supplierId : (int) ($filters['supplier_id'] ?? 0);
        if ($effectiveSupplier > 0) {
            $where .= ' AND o.supplier_id = :supplier_id';
            $params['supplier_id'] = $effectiveSupplier;
        }

        if ($filters['status'] !== '') {
            $this->appendStatusBucketFilter($where, $params, (string) $filters['status'], $includedOrderIds);
        }

        if ($filters['courier_status'] !== '') {
            $where .= ' AND o.courier_status = :courier_status';
            $params['courier_status'] = $filters['courier_status'];
        }

        if ($filters['date_from'] !== '') {
            $where .= ' AND DATE(COALESCE(o.ordered_at, o.created_at)) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if ($filters['date_to'] !== '') {
            $where .= ' AND DATE(COALESCE(o.ordered_at, o.created_at)) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if ($filters['q'] !== '') {
            $orderItemTable = TableName::forModel(OrderItem::class);
            $productTable = TableName::forModel(Product::class);
            $joinSql = ' LEFT JOIN `' . $this->escapeIdentifier($orderItemTable) . '` oi ON oi.order_id = o.order_id'
                . ' LEFT JOIN `' . $this->escapeIdentifier($productTable) . '` p ON p.product_id = oi.product_id';
            $where .= ' AND (o.order_reference LIKE :q OR o.source_order_reference LIKE :q OR o.customer_name LIKE :q'
                . ' OR o.customer_phone LIKE :q OR p.supplier_model LIKE :q OR p.source_model LIKE :q OR oi.product_name LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $this->appendDemoExclusion($where, $params, (bool) ($filters['show_demo'] ?? false));

        return [$where, $params, $joinSql];
    }

    /**
     * @param array<string, mixed> $params
     */
    private function countOrders(string $joinSql, string $whereSql, array $params, ?RequestTimer $timer): int
    {
        try {
            $orderTable = TableName::forModel(Order::class);
            $sql = 'SELECT COUNT(DISTINCT o.order_id) AS row_count FROM `' . $this->escapeIdentifier($orderTable) . '` o'
                . $joinSql . ' ' . $whereSql;
            QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo()->prepare($sql);
            $statement->execute($params);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if ($timer !== null) {
                $timer->lap('count');
            }

            return (int) ($row['row_count'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function fetchOrderPage(string $joinSql, string $whereSql, array $params, int $offset, int $perPage, ?RequestTimer $timer): array
    {
        try {
            $orderTable = TableName::forModel(Order::class);
            $groupBy = $joinSql !== '' ? ' GROUP BY o.order_id' : '';
            $sql = 'SELECT o.* FROM `' . $this->escapeIdentifier($orderTable) . '` o'
                . $joinSql . ' ' . $whereSql . $groupBy
                . ' ORDER BY o.order_id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
            QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo()->prepare($sql);
            $statement->execute($params);
            if ($timer !== null) {
                $timer->lap('fetch_page');
            }

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<int, int> $includedOrderIds
     */
    private function appendStatusBucketFilter(string &$where, array &$params, string $filterBucket, array $includedOrderIds): void
    {
        $filterBucket = OrderWorkflowStatus::normalize($filterBucket);
        [$statusSql, $statusParams] = $this->statusMatchClause($filterBucket, 'ibs_status');

        if ($filterBucket === 'shipped') {
            $where .= ' AND ' . $statusSql;
            $params = array_merge($params, $statusParams);
            if ($includedOrderIds !== []) {
                [$idSql, $idParams] = $this->orderIdNotInClause($includedOrderIds, 'ship_excl');
                $where .= ' AND ' . $idSql;
                $params = array_merge($params, $idParams);
            }

            return;
        }

        if ($filterBucket === 'dispatch_report_created') {
            $params = array_merge($params, $statusParams);
            if ($includedOrderIds !== []) {
                [$idSql, $idParams] = $this->orderIdInClause($includedOrderIds, 'cre_incl');
                $where .= ' AND (' . $statusSql . ' OR ' . $idSql . ')';
                $params = array_merge($params, $idParams);
            } else {
                $where .= ' AND ' . $statusSql;
            }

            return;
        }

        $where .= ' AND ' . $statusSql;
        $params = array_merge($params, $statusParams);
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function statusMatchClause(string $bucket, string $paramPrefix): array
    {
        $codes = OrderWorkflowStatus::statusCodesForBucket($bucket);
        if (count($codes) === 1) {
            return ['o.ibs_status = :' . $paramPrefix, [$paramPrefix => $codes[0]]];
        }

        $placeholders = [];
        $params = [];
        foreach ($codes as $index => $code) {
            $key = $paramPrefix . '_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $code;
        }

        return ['o.ibs_status IN (' . implode(', ', $placeholders) . ')', $params];
    }

    /**
     * @param array<int, int> $orderIds
     * @return array{0: string, 1: array<string, int>}
     */
    private function orderIdInClause(array $orderIds, string $paramPrefix): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0));
        $placeholders = [];
        $params = [];
        foreach ($orderIds as $index => $orderId) {
            $key = $paramPrefix . '_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $orderId;
        }

        return ['o.order_id IN (' . implode(', ', $placeholders) . ')', $params];
    }

    /**
     * @param array<int, int> $orderIds
     * @return array{0: string, 1: array<string, int>}
     */
    private function orderIdNotInClause(array $orderIds, string $paramPrefix): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0));
        $placeholders = [];
        $params = [];
        foreach ($orderIds as $index => $orderId) {
            $key = $paramPrefix . '_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $orderId;
        }

        return ['o.order_id NOT IN (' . implode(', ', $placeholders) . ')', $params];
    }

    private function appendDemoExclusion(string &$where, array &$params, bool $showDemo): void
    {
        if ($showDemo || !(bool) config('opencart.hide_demo_orders_in_workflow', true)) {
            return;
        }

        $orderTable = TableName::forModel(Order::class);
        if (SchemaColumnProbe::tableHasColumn($orderTable, 'sync_source', $this->pdo())) {
            $where .= " AND (o.sync_source IS NULL OR o.sync_source NOT IN ('demo', 'opencart_demo'))";
        }

        $prefixes = config('opencart.demo_source_order_reference_prefixes', ['OC-1000']);
        if (is_array($prefixes)) {
            foreach (array_values($prefixes) as $index => $prefix) {
                $prefix = trim((string) $prefix);
                if ($prefix === '') {
                    continue;
                }
                $key = 'demo_prefix_' . $index;
                $where .= ' AND (o.source_order_reference IS NULL OR o.source_order_reference NOT LIKE :' . $key . ')';
                $params[$key] = $prefix . '%';
            }
        }

        $floor = (int) config('opencart.order_sync_min_source_order_id', 0);
        if ($floor > 0) {
            $where .= ' AND (o.source_order_id IS NULL OR o.source_order_id = \'\' OR CAST(o.source_order_id AS UNSIGNED) >= :sync_floor)';
            $params['sync_floor'] = $floor;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPagination(int $page, int $perPage): array
    {
        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => 0,
            'total_pages' => 1,
            'has_previous' => false,
            'has_next' => false,
            'from' => 0,
            'to' => 0,
        ];
    }

    private function pdo(): PDO
    {
        return Connection::pdo();
    }

    private function escapeIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }
}
