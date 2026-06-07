<?php

namespace App\Services\ReadOnly;

use App\Repositories\Write\ProductWriteRepository;
use App\Services\Read\OpenCartReadClient;
use App\Services\Read\OpenCartSchemaProbe;

/**
 * Paginated read-only product sync preview (v1.7.1, v1.8.4 real API integration).
 */
class ProductSyncPreviewService
{
    private OpenCartReadClient $client;
    private ProductWriteRepository $products;

    public function __construct(?OpenCartReadClient $client = null, ?ProductWriteRepository $products = null)
    {
        $this->client = $client ?? new OpenCartReadClient();
        $this->products = $products ?? new ProductWriteRepository();
    }

    public function previewPage(int $page, int $businessSourceId): array
    {
        $page = max(1, $page);
        $sourceId = max(1, $businessSourceId);
        $fetch = $this->client->fetchWarehouseProductsPage($page);
        $probe = (new OpenCartSchemaProbe())->probeExtensions();
        $message = trim((string) ($fetch['message'] ?? ''));
        $skipStats = is_array($fetch['skip_stats'] ?? null)
            ? $fetch['skip_stats']
            : OpenCartReadClient::emptySkipStats();
        $importRows = is_array($fetch['rows'] ?? null) ? $fetch['rows'] : [];
        $importRows = array_values(array_filter(
            $importRows,
            static fn ($row): bool => is_array($row) && OpenCartReadClient::isStrictSupplierProduct($row)
        ));

        if ($importRows === [] && $message !== '') {
            return [
                'rows' => [],
                'import_rows' => [],
                'page' => $page,
                'per_page' => (int) ($fetch['per_page'] ?? 20),
                'has_previous' => $page > 1,
                'has_next' => false,
                'bridge_available' => $fetch['bridge_available'] ?? null,
                'bridge_warning' => (string) ($fetch['bridge_warning'] ?? $message),
                'message' => $message,
                'extensions' => $probe,
            ];
        }

        $rows = [];
        foreach ($importRows as $product) {
            if (!is_array($product)) {
                continue;
            }
            $sourceProductId = trim((string) ($product['source_product_id'] ?? ''));
            $existing = ($sourceProductId !== '' && $this->products->tableExists())
                ? $this->products->findBySourceProductId($sourceId, $sourceProductId)
                : null;

            $syncStatus = 'new';
            if ($existing !== null) {
                $syncStatus = 'existing';
            }

            $optionRows = [];
            foreach ($product['options'] ?? [] as $option) {
                if (!is_array($option)) {
                    continue;
                }
                $optionRows[] = [
                    'product_option_id' => (string) ($option['product_option_id'] ?? ''),
                    'product_option_value_id' => (string) ($option['product_option_value_id'] ?? ''),
                    'option_name' => (string) ($option['option_name'] ?? ''),
                    'option_value' => (string) ($option['option_value'] ?? ''),
                    'option_image_path' => (string) ($option['option_image_path'] ?? ''),
                    'source_model' => (string) ($option['source_model'] ?? ''),
                    'source_stock' => $option['source_stock'] ?? null,
                    'price_display' => (string) ($option['price_display'] ?? ''),
                    'subtract' => $option['subtract'] ?? null,
                    'required' => $option['required'] ?? null,
                ];
            }

            $rows[] = [
                'source_product_id' => $sourceProductId,
                'product_name' => (string) ($product['product_name'] ?? ''),
                'image_path' => (string) ($product['image_path'] ?? ''),
                'source_model' => (string) ($product['source_model'] ?? ''),
                'source_price' => $product['source_price'] ?? null,
                'source_status' => (string) ($product['source_status'] ?? ''),
                'source_stock' => $product['source_stock'] ?? null,
                'sync_status' => $syncStatus,
                'sync_options_state' => (string) ($product['sync_options_state'] ?? 'simple'),
                'option_count' => count($optionRows),
                'options' => $optionRows,
                'importable' => true,
            ];
        }

        return [
            'rows' => $rows,
            'import_rows' => $importRows,
            'page' => (int) ($fetch['page'] ?? $page),
            'per_page' => (int) ($fetch['per_page'] ?? 20),
            'has_previous' => (bool) ($fetch['has_previous'] ?? false),
            'has_next' => (bool) ($fetch['has_next'] ?? false),
            'bridge_available' => $fetch['bridge_available'] ?? null,
            'bridge_warning' => $fetch['bridge_warning'] ?? null,
            'message' => $message !== '' ? $message : OpenCartReadClient::formatSupplierSkipMessage($skipStats, count($rows)),
            'skip_stats' => $skipStats,
            'extensions' => $probe,
        ];
    }
}
