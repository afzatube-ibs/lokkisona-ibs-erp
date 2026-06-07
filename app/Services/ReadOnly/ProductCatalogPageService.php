<?php

namespace App\Services\ReadOnly;

/**
 * Paginated Product Control catalog (v1.8.0) — filters + 20 rows per page in PHP.
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

        $filtered = $this->applyFilters($allRows, $filters);
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
            'rows' => $pageRows,
            'workspaces' => $pageWorkspaces,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_previous' => $page > 1,
                'has_next' => ($offset + $perPage) < $total,
            ],
            'filters' => $this->normalizedFilters($filters),
        ];
    }

    public function normalizedFilters(array $filters): array
    {
        $chip = trim((string) ($filters['chip'] ?? 'all'));
        $allowedChips = [
            'all', 'variable', 'simple', 'low_stock', 'missing_cost',
            'missing_model', 'needs_work', 'sync_required', 'synced_today',
        ];

        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'product_id' => trim((string) ($filters['product_id'] ?? '')),
            'product_name' => trim((string) ($filters['product_name'] ?? '')),
            'model' => trim((string) ($filters['model'] ?? '')),
            'supplier_model' => trim((string) ($filters['supplier_model'] ?? '')),
            'chip' => in_array($chip, $allowedChips, true) ? $chip : 'all',
        ];
    }

    private function applyFilters(array $rows, array $filters): array
    {
        $f = $this->normalizedFilters($filters);
        $chip = $f['chip'];

        return array_values(array_filter($rows, function (array $row) use ($f, $chip): bool {
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

            $flags = $row['filter_flags'] ?? [];

            return match ($chip) {
                'variable' => ($row['type'] ?? '') === 'variable',
                'simple' => ($row['type'] ?? '') === 'simple',
                'low_stock' => !empty($flags['low_stock']),
                'missing_cost' => !empty($flags['missing_cost']),
                'missing_model' => !empty($flags['missing_model']),
                'needs_work' => !empty($flags['needs_work']),
                'sync_required' => !empty($flags['sync_required']),
                'synced_today' => !empty($flags['synced_today']),
                default => true,
            };
        }));
    }
}
