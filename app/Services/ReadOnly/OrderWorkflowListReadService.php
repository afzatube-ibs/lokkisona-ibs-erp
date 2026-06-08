<?php

namespace App\Services\ReadOnly;

use App\Database\Connection;
use App\Database\QueryGuard;
use App\Database\TableName;
use App\Domain\OrderWorkflowStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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

        [$whereSql, $params, $joinSql] = $this->buildWhere($normalized, $supplierId);
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
            $rawStatus = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? 'new_order'));
            $dispatchReference = $dispatchReferences[$orderId] ?? null;
            $dispatchReportId = isset($dispatchMeta[$orderId])
                ? (int) ($dispatchMeta[$orderId]['dispatch_report_id'] ?? 0)
                : null;
            $batchLocked = $dispatchReference !== null && $dispatchReference !== '';
            $displayStatus = ($batchLocked || $rawStatus === 'dispatch_report_created')
                ? 'dispatch_report_created'
                : $rawStatus;

            $productLines = OrderWorkflowRowPresenter::formatProductLines(
                $itemsByOrder[$orderId] ?? [],
                $productsById,
                $variantsByProduct
            );

            $rows[] = OrderWorkflowRowPresenter::buildRow(
                $order,
                $displayStatus,
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
        $cacheKey = 'stage_counts_' . $supplierId;
        $cached = $this->cache->get($cacheKey, self::CACHE_TTL);
        if (is_array($cached)) {
            return $cached;
        }

        if ($dispatchReferences === [] && $this->dispatchReports->tableExists()) {
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
                $normalized = OrderWorkflowStatus::normalize((string) ($row['ibs_status'] ?? 'new_order'));
                $group = isset($dispatchReferences[$orderId]) || $normalized === 'dispatch_report_created'
                    ? 'dispatch_report_created'
                    : $normalized;
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

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $status = OrderWorkflowStatus::normalize($status);
            $known = array_merge(OrderWorkflowStatus::groupOrder(), array_column(OrderWorkflowStatus::exceptionStages(), 'code'));
            if (!in_array($status, $known, true)) {
                $status = '';
            }
        }

        $perPage = (int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE);
        $perPage = min(self::MAX_PER_PAGE, max(self::DEFAULT_PER_PAGE, $perPage));

        return [
            'status' => $status,
            'supplier_id' => max(0, (int) ($filters['supplier_id'] ?? 0)),
            'courier_status' => trim((string) ($filters['courier_status'] ?? '')),
            'date_from' => trim((string) ($filters['date_from'] ?? '')),
            'date_to' => trim((string) ($filters['date_to'] ?? '')),
            'q' => trim((string) ($filters['q'] ?? '')),
            'per_page' => $perPage,
        ];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>, 2: string}
     */
    private function buildWhere(array $filters, int $supplierId): array
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
            if ($filters['status'] === 'dispatch_report_created' && $this->dispatchReports->tableExists()) {
                $where .= ' AND (o.ibs_status = :dispatch_status OR o.order_id IN (SELECT order_id FROM dispatch_items_sub))';
                $params['dispatch_status'] = 'dispatch_report_created';
            } elseif ($filters['status'] === 'shipped' && $this->dispatchReports->tableExists()) {
                $where .= ' AND o.ibs_status = :ibs_status AND o.order_id NOT IN (SELECT order_id FROM dispatch_items_sub)';
                $params['ibs_status'] = 'shipped';
            } else {
                $where .= ' AND o.ibs_status = :ibs_status';
                $params['ibs_status'] = $filters['status'];
            }
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

        return [$where, $params, $joinSql];
    }

    /**
     * @param array<string, mixed> $params
     */
    private function countOrders(string $joinSql, string $whereSql, array $params, ?RequestTimer $timer): int
    {
        try {
            $orderTable = TableName::forModel(Order::class);
            $whereSql = $this->replaceDispatchSubquery($whereSql);
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
            $whereSql = $this->replaceDispatchSubquery($whereSql);
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

    private function replaceDispatchSubquery(string $whereSql): string
    {
        if (!str_contains($whereSql, 'dispatch_items_sub')) {
            return $whereSql;
        }

        if (!$this->dispatchReports->tableExists()) {
            return str_replace(
                ' IN (SELECT order_id FROM dispatch_items_sub)',
                ' IN (SELECT NULL WHERE 1 = 0)',
                str_replace(
                    ' NOT IN (SELECT order_id FROM dispatch_items_sub)',
                    '',
                    $whereSql
                )
            );
        }

        $prefix = config('database.prefix', 'ibs_');
        $itemsTable = $prefix . 'dispatch_report_items';

        return str_replace(
            'dispatch_items_sub',
            '(SELECT DISTINCT order_id FROM `' . str_replace('`', '``', $itemsTable) . '` WHERE order_id IS NOT NULL)',
            $whereSql
        );
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
