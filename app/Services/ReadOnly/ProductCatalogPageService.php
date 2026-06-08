<?php

namespace App\Services\ReadOnly;

/**
 * Paginated Product Control catalog (v1.8.0+) — filters + 20 rows per page in PHP.
 */
class ProductCatalogPageService
{
    public const PER_PAGE = 20;

    private ProductControlCatalogReadService $catalog;

    public function __construct(?ProductControlCatalogReadService $catalog = null)
    {
        $this->catalog = $catalog ?? new ProductControlCatalogReadService();
    }

    public function page(array $productRows, array $variantRows, array $filters, int $page, bool $isSupplierView = false): array
    {
        $page = max(1, $page);
        $built = $this->catalog->build($productRows, $variantRows, $isSupplierView);
        $allRows = $built['rows'] ?? [];
        $workspaces = $built['workspaces'] ?? [];
        $summaryKpis = $this->catalog->summarizeKpis($allRows);

        $filtered = $this->applyFilters($allRows, $filters);
        $filtered = $this->sortRows($filtered, $this->normalizedFilters($filters)['sort']);
        $total = count($filtered);
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;
        $pageRows = array_slice($filtered, $offset, $perPage);

        $pageWorkspaces = [];
        foreach ($pageRows as $row) {
            $pid = (string) ($row['product_id'] ?? '');
            if ($pid !== '' && isset($workspaces[$pid])) {
                $pageWorkspaces[$pid] = $workspaces[$pid];
            }
        }

        return [
            'kpis' => $this->catalog->summarizeKpis($filtered),
            'summary_kpis' => $summaryKpis,
            'rows' => $pageRows,
            'workspaces' => $pageWorkspaces,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
                'has_previous' => $page > 1,
                'has_next' => ($offset + $perPage) < $total,
            ],
            'filters' => $this->normalizedFilters($filters),
        ];
    }

    public function normalizedFilters(array $filters): array
    {
        $chip = trim((string) ($filters['chip'] ?? 'all'));
        $type = trim((string) ($filters['type'] ?? 'all'));
        $sort = trim((string) ($filters['sort'] ?? 'product_id_asc'));
        $allowedChips = [
            'all', 'ready', 'low_stock', 'missing_cost',
            'missing_model', 'needs_work',
        ];
        $allowedTypes = ['all', 'simple', 'variable'];
        $allowedSorts = [
            'product_id_asc', 'product_id_desc', 'name_asc', 'name_desc',
            'model_asc', 'synced_desc', 'health',
        ];

        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'product_id' => trim((string) ($filters['product_id'] ?? '')),
            'product_name' => trim((string) ($filters['product_name'] ?? '')),
            'model' => trim((string) ($filters['model'] ?? '')),
            'supplier_model' => trim((string) ($filters['supplier_model'] ?? '')),
            'category' => trim((string) ($filters['category'] ?? '')),
            'type' => in_array($type, $allowedTypes, true) ? $type : 'all',
            'sort' => in_array($sort, $allowedSorts, true) ? $sort : 'product_id_asc',
            'chip' => in_array($chip, $allowedChips, true) ? $chip : 'all',
            'per_page' => $this->normalizePerPage($filters['per_page'] ?? ProductControlListReadService::PER_PAGE),
        ];
    }

    private function normalizePerPage(mixed $perPage): int
    {
        $value = (int) $perPage;

        return in_array($value, [ProductControlListReadService::PER_PAGE, ProductControlListReadService::MAX_PER_PAGE], true)
            ? $value
            : ProductControlListReadService::PER_PAGE;
    }

    private function applyFilters(array $rows, array $filters): array
    {
        $f = $this->normalizedFilters($filters);
        $chip = $f['chip'];
        $type = $f['type'];

        return array_values(array_filter($rows, function (array $row) use ($f, $chip, $type): bool {
            if ($f['q'] !== '') {
                $blob = (string) ($row['search_blob'] ?? '');
                if (!str_contains($blob, strtolower($f['q']))) {
                    return false;
                }
            }
            if ($f['product_id'] !== '' && !str_contains((string) ($row['product_id'] ?? ''), $f['product_id'])) {
                return false;
            }
            if ($f['product_name'] !== '' && !str_contains(strtolower((string) ($row['product_name'] ?? '')), strtolower($f['product_name']))) {
                return false;
            }
            if ($f['model'] !== '' && !str_contains(strtolower((string) ($row['source_model'] ?? '')), strtolower($f['model']))) {
                return false;
            }
            if ($f['supplier_model'] !== '' && !str_contains(strtolower((string) ($row['supplier_model'] ?? '')), strtolower($f['supplier_model']))) {
                return false;
            }
            if ($f['category'] !== '' && strcasecmp((string) ($row['supplier_product_category'] ?? ''), $f['category']) !== 0) {
                return false;
            }
            if ($type === 'simple' && ($row['type'] ?? '') !== 'simple') {
                return false;
            }
            if ($type === 'variable' && ($row['type'] ?? '') !== 'variable') {
                return false;
            }

            $flags = $row['filter_flags'] ?? [];

            return match ($chip) {
                'ready' => !empty($flags['ready']),
                'low_stock' => !empty($flags['low_stock']),
                'missing_cost' => !empty($flags['missing_cost']),
                'missing_model' => !empty($flags['missing_model']),
                'needs_work' => !empty($flags['needs_work']),
                default => true,
            };
        }));
    }

    private function sortRows(array $rows, string $sort): array
    {
        usort($rows, function (array $a, array $b) use ($sort): int {
            return match ($sort) {
                'product_id_desc' => ((int) ($b['product_id'] ?? 0)) <=> ((int) ($a['product_id'] ?? 0)),
                'name_asc' => strcasecmp((string) ($a['product_name'] ?? ''), (string) ($b['product_name'] ?? '')),
                'name_desc' => strcasecmp((string) ($b['product_name'] ?? ''), (string) ($a['product_name'] ?? '')),
                'model_asc' => strcasecmp((string) ($a['source_model'] ?? ''), (string) ($b['source_model'] ?? '')),
                'synced_desc' => strcmp((string) ($b['last_synced_at'] ?? ''), (string) ($a['last_synced_at'] ?? '')),
                'health' => strcasecmp((string) ($a['health_status_display'] ?? ''), (string) ($b['health_status_display'] ?? '')),
                default => ((int) ($a['product_id'] ?? 0)) <=> ((int) ($b['product_id'] ?? 0)),
            };
        });

        return $rows;
    }
}
