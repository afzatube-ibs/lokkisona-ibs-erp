<?php

namespace App\Services\ReadOnly;

use App\Database\Connection;
use App\Database\QueryGuard;
use App\Database\TableName;
use App\Domain\ProductControlIbsCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\ProductVariantRepository;
use App\Support\RequestTimer;
use App\Support\SimpleFileCache;
use PDO;

/**
 * SQL-paginated Product Control listing (v1.9.1) — local ERP snapshot only.
 */
class ProductControlListReadService
{
    public const PER_PAGE = 20;

    public const MAX_PER_PAGE = 50;

    private const CACHE_TTL = 60;

    private ProductControlCatalogReadService $catalog;

    private ProductCatalogPageService $filters;

    private ProductVariantRepository $variantRepository;

    private SimpleFileCache $cache;

    public function __construct(
        ?ProductControlCatalogReadService $catalog = null,
        ?ProductCatalogPageService $filters = null,
        ?ProductVariantRepository $variantRepository = null,
        ?SimpleFileCache $cache = null
    ) {
        $this->catalog = $catalog ?? new ProductControlCatalogReadService();
        $this->filters = $filters ?? new ProductCatalogPageService();
        $this->variantRepository = $variantRepository ?? new ProductVariantRepository();
        $this->cache = $cache ?? new SimpleFileCache('product-control');
    }

    public function tableExists(): bool
    {
        try {
            return (new ProductReadService())->tableExists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function listPage(array $filters, int $page, int $supplierId, bool $isSupplierView, ?RequestTimer $timer = null): array
    {
        $normalized = $this->filters->normalizedFilters($filters);
        $page = max(1, $page);
        $perPage = $this->resolvePerPage($normalized);
        $offset = ($page - 1) * $perPage;

        if (!$this->tableExists()) {
            return [
                'rows' => [],
                'workspaces' => [],
                'kpis' => $this->emptyKpis(),
                'summary_kpis' => $this->emptyKpis(),
                'pagination' => $this->emptyPagination($page, $perPage),
                'filters' => $normalized,
            ];
        }

        $where = $this->baseWhereSql($supplierId);
        $params = $this->baseWhereParams($supplierId);
        $this->appendFilterSql($normalized, $where, $params);

        $total = $this->countProducts($where, $params, $timer);
        $productRows = $this->fetchProductPage($where, $params, $normalized['sort'], $offset, $perPage, $timer);

        $productIds = array_map(static fn (array $row): int => (int) ($row['product_id'] ?? 0), $productRows);
        $variantsByProduct = $this->variantRepository->groupedByProductIds($productIds);
        if ($timer !== null) {
            $timer->lap('variant_aggregate');
        }

        $catalogRows = [];
        foreach ($productRows as $product) {
            $pid = (int) ($product['product_id'] ?? 0);
            $variants = $variantsByProduct[$pid] ?? [];
            $catalogRows[] = $this->catalog->buildProductViews($product, $variants, $isSupplierView)['catalog'];
        }

        if ($timer !== null) {
            $timer->lap('list_enrich');
        }

        $kpis = $this->summaryKpis($supplierId, $isSupplierView, $timer);

        return [
            'rows' => $catalogRows,
            'workspaces' => [],
            'kpis' => $kpis,
            'summary_kpis' => $kpis,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
                'has_previous' => $page > 1,
                'has_next' => ($offset + $perPage) < $total,
            ],
            'filters' => $normalized,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function summaryKpis(int $supplierId, bool $isSupplierView, ?RequestTimer $timer = null): array
    {
        unset($isSupplierView);

        $cacheKey = 'kpis:' . $supplierId;
        $cached = $this->cache->get($cacheKey, self::CACHE_TTL);
        if ($cached !== null) {
            if ($timer !== null) {
                $timer->lap('kpi_cache_hit');
            }

            return $cached;
        }

        if (!$this->tableExists()) {
            return $this->emptyKpis();
        }

        try {
            $pdo = Connection::pdo();
            $productsTable = TableName::forModel(Product::class);
            $variantsTable = TableName::forModel(ProductVariant::class);
            $where = $this->baseWhereSql($supplierId);
            $params = $this->baseWhereParams($supplierId);

            $sql = 'SELECT '
                . 'COUNT(*) AS total_products, '
                . 'SUM(CASE WHEN ' . $this->readySql('p', 'vagg') . ' THEN 1 ELSE 0 END) AS ready, '
                . 'SUM(CASE WHEN ' . $this->needsWorkSql('p', 'vagg') . ' THEN 1 ELSE 0 END) AS needs_work, '
                . 'SUM(CASE WHEN ' . $this->missingCostSql('p', 'vagg') . ' THEN 1 ELSE 0 END) AS missing_cost, '
                . 'SUM(CASE WHEN ' . $this->missingModelSql('p', 'vagg') . ' THEN 1 ELSE 0 END) AS missing_model, '
                . 'SUM(CASE WHEN ' . $this->lowStockSql('p', 'vagg') . ' THEN 1 ELSE 0 END) AS low_stock, '
                . 'SUM(CASE WHEN p.last_synced_at IS NOT NULL AND DATE(p.last_synced_at) = CURDATE() THEN 1 ELSE 0 END) AS synced_today '
                . 'FROM `' . $this->esc($productsTable) . '` p '
                . 'LEFT JOIN ' . $this->variantAggregateSubquery($variantsTable) . ' vagg ON vagg.product_id = p.product_id '
                . 'WHERE ' . $where;

            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $variantSql = 'SELECT COUNT(*) AS c, COALESCE(SUM(pv.vendor_stock), 0) AS qty '
                . 'FROM `' . $this->esc($variantsTable) . '` pv '
                . 'INNER JOIN `' . $this->esc($productsTable) . '` p ON p.product_id = pv.product_id '
                . 'WHERE ' . $where;
            QueryGuard::assertReadOnly($variantSql);
            $vstmt = $pdo->prepare($variantSql);
            $vstmt->execute($params);
            $vrow = $vstmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $kpis = [
                'total_products' => (int) ($row['total_products'] ?? 0),
                'ready' => (int) ($row['ready'] ?? 0),
                'needs_work' => (int) ($row['needs_work'] ?? 0),
                'missing_cost' => (int) ($row['missing_cost'] ?? 0),
                'missing_model' => (int) ($row['missing_model'] ?? 0),
                'low_stock' => (int) ($row['low_stock'] ?? 0),
                'synced_today' => (int) ($row['synced_today'] ?? 0),
                'variants' => (int) ($vrow['c'] ?? 0),
                'variant_qty_total' => (int) ($vrow['qty'] ?? 0),
            ];

            $this->cache->set($cacheKey, $kpis);

            if ($timer !== null) {
                $timer->lap('kpi_query');
            }

            return $kpis;
        } catch (\Throwable $e) {
            return $this->emptyKpis();
        }
    }

    /**
     * @return array{last_synced_at: string, is_stale: bool, age_hours: float|null}
     */
    public function snapshotFreshness(int $supplierId): array
    {
        if (!$this->tableExists()) {
            return ['last_synced_at' => '', 'is_stale' => false, 'age_hours' => null];
        }

        try {
            $pdo = Connection::pdo();
            $productsTable = TableName::forModel(Product::class);
            $where = $this->baseWhereSql($supplierId);
            $params = $this->baseWhereParams($supplierId);
            $sql = 'SELECT MAX(p.last_synced_at) AS latest FROM `' . $this->esc($productsTable) . '` p WHERE ' . $where;
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $latest = trim((string) ($stmt->fetch(PDO::FETCH_ASSOC)['latest'] ?? ''));
            $isStale = false;
            $ageHours = null;
            if ($latest !== '') {
                $ts = strtotime($latest);
                if ($ts !== false) {
                    $ageHours = round((time() - $ts) / 3600, 1);
                    $isStale = (time() - $ts) > (24 * 3600);
                }
            }

            return [
                'last_synced_at' => $latest,
                'is_stale' => $isStale,
                'age_hours' => $ageHours,
            ];
        } catch (\Throwable $e) {
            return ['last_synced_at' => '', 'is_stale' => false, 'age_hours' => null];
        }
    }

    /**
     * Distinct supplier product categories for filter dropdown (local snapshot only).
     *
     * @return array<int, string>
     */
    public function listCategoryOptions(int $supplierId): array
    {
        if (!$this->tableExists() || !$this->categoryColumnReady()) {
            return [];
        }

        try {
            $pdo = Connection::pdo();
            $productsTable = TableName::forModel(Product::class);
            $where = $this->baseWhereSql($supplierId);
            $params = $this->baseWhereParams($supplierId);
            $sql = 'SELECT DISTINCT TRIM(p.supplier_product_category) AS category '
                . 'FROM `' . $this->esc($productsTable) . '` p '
                . 'WHERE ' . $where . ' AND p.supplier_product_category IS NOT NULL '
                . 'AND TRIM(p.supplier_product_category) != \'\' '
                . 'ORDER BY category ASC LIMIT 200';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $fromDb = array_values(array_filter(array_map(
                static fn (array $row): string => trim((string) ($row['category'] ?? '')),
                $rows
            )));

            return ProductControlIbsCategory::mergeOptions($fromDb);
        } catch (\Throwable $e) {
            return ProductControlIbsCategory::mergeOptions([]);
        }
    }

    public static function invalidateCache(): void
    {
        (new SimpleFileCache('product-control'))->flush();
    }

    /**
     * @param array<string, mixed> $normalized
     */
    private function appendFilterSql(array $normalized, string &$where, array &$params): void
    {
        if ($normalized['q'] !== '') {
            $qClause = '(p.product_name LIKE :q OR p.source_model LIKE :q OR p.supplier_model LIKE :q OR p.source_product_id LIKE :q OR CAST(p.product_id AS CHAR) LIKE :q';
            if ($this->categoryColumnReady()) {
                $qClause .= ' OR p.supplier_product_category LIKE :q';
            }
            $where .= ' AND ' . $qClause . ')';
            $params['q'] = '%' . $normalized['q'] . '%';
        }
        if ($normalized['model'] !== '') {
            $where .= ' AND p.source_model LIKE :model';
            $params['model'] = '%' . $normalized['model'] . '%';
        }
        if ($normalized['supplier_model'] !== '') {
            $where .= ' AND p.supplier_model LIKE :supplier_model';
            $params['supplier_model'] = '%' . $normalized['supplier_model'] . '%';
        }
        if ($normalized['category'] !== '' && $this->categoryColumnReady()) {
            $where .= ' AND TRIM(p.supplier_product_category) = :category';
            $params['category'] = $normalized['category'];
        }

        if ($normalized['type'] === 'simple') {
            $where .= ' AND COALESCE(vagg.variant_count, 0) = 0 AND COALESCE(p.sync_options_state, \'\') != \'missing_options\'';
        } elseif ($normalized['type'] === 'variable') {
            $where .= ' AND (COALESCE(vagg.variant_count, 0) > 0 OR COALESCE(p.sync_options_state, \'\') = \'missing_options\')';
        }

        $chip = $normalized['chip'];
        if ($chip === 'ready') {
            $where .= ' AND ' . $this->readySql('p', 'vagg');
        } elseif ($chip === 'needs_work') {
            $where .= ' AND ' . $this->needsWorkSql('p', 'vagg');
        } elseif ($chip === 'missing_cost') {
            $where .= ' AND ' . $this->missingCostSql('p', 'vagg');
        } elseif ($chip === 'missing_model') {
            $where .= ' AND ' . $this->missingModelSql('p', 'vagg');
        } elseif ($chip === 'low_stock') {
            $where .= ' AND ' . $this->lowStockSql('p', 'vagg');
        }
    }

    /**
     * @param array<string, mixed> $normalized
     */
    private function resolvePerPage(array $normalized): int
    {
        $perPage = (int) ($normalized['per_page'] ?? self::PER_PAGE);

        return in_array($perPage, [self::PER_PAGE, self::MAX_PER_PAGE], true) ? $perPage : self::PER_PAGE;
    }

    private function categoryColumnReady(): bool
    {
        return $this->tableExists();
    }

    private function baseWhereSql(int $supplierId): string
    {
        $sql = 'p.source_product_id IS NOT NULL AND TRIM(p.source_product_id) != \'\' '
            . 'AND p.last_synced_at IS NOT NULL';
        $excluded = (new SupplierProductFilter())->nonSupplierSourceIds();
        if ($excluded !== []) {
            $placeholders = [];
            foreach ($excluded as $i => $id) {
                unset($id);
                $placeholders[] = ':excluded_' . $i;
            }
            $sql .= ' AND p.source_product_id NOT IN (' . implode(', ', $placeholders) . ')';
        }
        if ($supplierId > 0) {
            $sql .= ' AND p.supplier_id = :supplier_id';
        }

        return $sql;
    }

    /**
     * @return array<string, mixed>
     */
    private function baseWhereParams(int $supplierId): array
    {
        $params = [];
        $excluded = (new SupplierProductFilter())->nonSupplierSourceIds();
        foreach ($excluded as $i => $id) {
            $params['excluded_' . $i] = $id;
        }
        if ($supplierId > 0) {
            $params['supplier_id'] = $supplierId;
        }

        return $params;
    }

    private function variantAggregateSubquery(string $variantsTable): string
    {
        return '(SELECT product_id, COUNT(*) AS variant_count, '
            . 'COALESCE(SUM(vendor_stock), 0) AS variant_vendor_stock, '
            . 'MAX(CASE WHEN TRIM(COALESCE(supplier_model, \'\')) = \'\' THEN 1 ELSE 0 END) AS variant_missing_model, '
            . 'MAX(CASE WHEN product_cost IS NULL THEN 1 ELSE 0 END) AS variant_missing_cost '
            . 'FROM `' . $this->esc($variantsTable) . '` GROUP BY product_id) ';
    }

    private function missingCostSql(string $p, string $v): string
    {
        return '((' . $this->variantCountExpr($v) . ' = 0 AND (' . $p . '.product_cost IS NULL)) OR (' . $this->variantCountExpr($v) . ' > 0 AND COALESCE(' . $v . '.variant_missing_cost, 0) = 1))';
    }

    private function missingModelSql(string $p, string $v): string
    {
        return '((' . $this->variantCountExpr($v) . ' = 0 AND TRIM(COALESCE(' . $p . '.supplier_model, \'\')) = \'\') OR (' . $this->variantCountExpr($v) . ' > 0 AND COALESCE(' . $v . '.variant_missing_model, 0) = 1))';
    }

    private function lowStockSql(string $p, string $v): string
    {
        return '(' . $p . '.low_warning_threshold > 0 AND ((' . $this->variantCountExpr($v) . ' > 0 AND COALESCE(' . $v . '.variant_vendor_stock, 0) <= ' . $p . '.low_warning_threshold) OR (' . $this->variantCountExpr($v) . ' = 0 AND ' . $p . '.vendor_stock <= ' . $p . '.low_warning_threshold)))';
    }

    private function needsWorkSql(string $p, string $v): string
    {
        return '(' . $this->missingCostSql($p, $v) . ' OR ' . $this->missingModelSql($p, $v) . ')';
    }

    private function readySql(string $p, string $v): string
    {
        return '(NOT (' . $this->needsWorkSql($p, $v) . ') AND NOT (' . $this->lowStockSql($p, $v) . '))';
    }

    private function variantCountExpr(string $v): string
    {
        return 'COALESCE(' . $v . '.variant_count, 0)';
    }

    private function countProducts(string $where, array $params, ?RequestTimer $timer): int
    {
        try {
            $pdo = Connection::pdo();
            $productsTable = TableName::forModel(Product::class);
            $variantsTable = TableName::forModel(ProductVariant::class);
            $sql = 'SELECT COUNT(*) AS c FROM `' . $this->esc($productsTable) . '` p '
                . 'LEFT JOIN ' . $this->variantAggregateSubquery($variantsTable) . ' vagg ON vagg.product_id = p.product_id '
                . 'WHERE ' . $where;
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($timer !== null) {
                $timer->lap('list_count');
            }

            return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchProductPage(string $where, array $params, string $sort, int $offset, int $limit, ?RequestTimer $timer): array
    {
        try {
            $pdo = Connection::pdo();
            $productsTable = TableName::forModel(Product::class);
            $variantsTable = TableName::forModel(ProductVariant::class);
            $order = $this->orderBySql($sort);
            $sql = 'SELECT p.* FROM `' . $this->esc($productsTable) . '` p '
                . 'LEFT JOIN ' . $this->variantAggregateSubquery($variantsTable) . ' vagg ON vagg.product_id = p.product_id '
                . 'WHERE ' . $where . ' ORDER BY ' . $order . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($timer !== null) {
                $timer->lap('list_query');
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function orderBySql(string $sort): string
    {
        return match ($sort) {
            'product_id_desc' => 'p.product_id DESC',
            'name_asc' => 'p.product_name ASC',
            'name_desc' => 'p.product_name DESC',
            'model_asc' => 'p.source_model ASC',
            'category_asc' => $this->categoryColumnReady()
                ? 'p.supplier_product_category ASC, p.product_id ASC'
                : 'p.product_id ASC',
            'synced_desc' => 'p.last_synced_at DESC',
            'health' => 'p.supplier_model ASC',
            default => 'p.product_id ASC',
        };
    }

    /**
     * @return array<string, int>
     */
    private function emptyKpis(): array
    {
        return [
            'total_products' => 0,
            'ready' => 0,
            'needs_work' => 0,
            'missing_cost' => 0,
            'missing_model' => 0,
            'low_stock' => 0,
            'synced_today' => 0,
            'variants' => 0,
            'variant_qty_total' => 0,
        ];
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
        ];
    }

    private function esc(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }
}
