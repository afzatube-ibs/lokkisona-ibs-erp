<?php

namespace App\Services\Read;

/**
 * Read-only OpenCart/PIT client (v0.5.7, v1.7.0 product sync, v1.7.1 paginated live test, v1.8.4 real API repair, v1.8.5 supplier-only filter).
 */
class OpenCartReadClient
{
    public const BRIDGE_WARNING = 'Dispatch Location bridge not found. Product sync cannot safely identify supplier products.';

    public static function emptySkipStats(): array
    {
        return [
            'total_received' => 0,
            'supplier_products' => 0,
            'skipped_not_supplier' => 0,
            'skipped_missing_from_warehouse' => 0,
        ];
    }

    /**
     * Import/preview gate: from_warehouse must be present and equal to integer 1 only.
     */
    public static function isStrictSupplierProduct(array $row): bool
    {
        if (!array_key_exists('from_warehouse', $row)) {
            return false;
        }

        return self::isStrictFromWarehouseValue($row['from_warehouse']);
    }

    /**
     * @param mixed $value
     */
    public static function isStrictFromWarehouseValue($value): bool
    {
        if ($value === null || $value === '' || $value === false) {
            return false;
        }

        if (is_bool($value)) {
            return false;
        }

        return (int) $value === 1;
    }

    public static function formatSupplierSkipMessage(array $skipStats, int $supplierCount): string
    {
        $skipped = (int) ($skipStats['skipped_not_supplier'] ?? 0)
            + (int) ($skipStats['skipped_missing_from_warehouse'] ?? 0);
        $message = $supplierCount . ' supplier product' . ($supplierCount === 1 ? '' : 's') . ' loaded.';
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' non-supplier product' . ($skipped === 1 ? '' : 's') . ' skipped.';
        }

        return $message;
    }

    public function testConnection(): array
    {
        if ($this->isDemoMode()) {
            return [
                'ok' => true,
                'mode' => 'demo',
                'message' => 'Demo mode active — safe local preview.',
                'bridge_available' => true,
            ];
        }

        if (!$this->isLiveReadEnabled()) {
            return [
                'ok' => false,
                'mode' => 'off',
                'message' => 'OpenCart connection disabled. Set Source Mode to Staging/Live in System → Sync/API Settings, or use Demo mode.',
                'bridge_available' => null,
            ];
        }

        $baseUrl = rtrim((string) config('opencart.api_base_url', ''), '/');
        $apiKey = (string) config('opencart.api_key', '');
        if ($baseUrl === '' || $apiKey === '') {
            return [
                'ok' => false,
                'mode' => $this->resolvedSourceMode(),
                'message' => 'Staging/live mode requires Source URL and API key in System → Sync/API Settings.',
                'bridge_available' => null,
            ];
        }

        $probe = $this->fetchConnectionTest();
        if (!($probe['ok'] ?? false)) {
            return [
                'ok' => false,
                'mode' => $this->resolvedSourceMode(),
                'message' => (string) ($probe['message'] ?? 'Connection test failed.'),
                'bridge_available' => $probe['bridge_available'] ?? null,
            ];
        }

        return [
            'ok' => true,
            'mode' => $this->resolvedSourceMode(),
            'message' => (string) ($probe['message'] ?? ucfirst($this->resolvedSourceMode()) . ' OpenCart read connection OK.'),
            'bridge_available' => $probe['bridge_available'] ?? null,
        ];
    }

    public function fetchSupplierOrdersPage(int $page = 1): array
    {
        $page = max(1, $page);
        $limit = $this->pageLimit();

        if (!(bool) config('opencart.order_sync_enabled', true)) {
            return $this->emptyPageWithMessage($page, $limit, 'Order sync is disabled in System → Sync/API Settings.');
        }

        if ($this->isLiveReadEnabled()) {
            return $this->fetchLiveSupplierOrdersPage($page, $limit);
        }

        if ($this->isDemoMode()) {
            return $this->fetchDemoSupplierOrdersPage($page, $limit);
        }

        return $this->emptyPageWithMessage($page, $limit, 'OpenCart connection disabled.');
    }

    /**
     * @return array{ok: bool, statuses: array<int, array<string, mixed>>, queue_status_ids: array<int, string>, message: ?string, bridge_available: ?bool}
     */
    public function fetchOrderQueueStatuses(): array
    {
        if ($this->isDemoMode()) {
            $statuses = config('opencart.demo_queue_statuses', []);
            if (!is_array($statuses)) {
                $statuses = [];
            }
            $queueIds = [];
            foreach ($statuses as $row) {
                if (!is_array($row) || empty($row['selected'])) {
                    continue;
                }
                $id = trim((string) ($row['status_id'] ?? ''));
                if ($id !== '') {
                    $queueIds[] = $id;
                }
            }

            return [
                'ok' => true,
                'statuses' => $statuses,
                'queue_status_ids' => $queueIds,
                'message' => null,
                'bridge_available' => true,
            ];
        }

        if (!$this->isLiveReadEnabled()) {
            return [
                'ok' => false,
                'statuses' => [],
                'queue_status_ids' => [],
                'message' => 'OpenCart connection disabled.',
                'bridge_available' => null,
            ];
        }

        $baseUrl = rtrim((string) config('opencart.api_base_url', ''), '/');
        $apiKey = (string) config('opencart.api_key', '');
        if ($baseUrl === '' || $apiKey === '') {
            return [
                'ok' => false,
                'statuses' => [],
                'queue_status_ids' => [],
                'message' => 'Missing api_base_url or api_key.',
                'bridge_available' => null,
            ];
        }

        $route = trim((string) config('opencart.order_queue_api_route', 'api/ibs/order_queue_statuses'));
        if ($route === '') {
            return [
                'ok' => false,
                'statuses' => [],
                'queue_status_ids' => [],
                'message' => 'Order queue API route is not configured.',
                'bridge_available' => null,
            ];
        }

        $url = $baseUrl . '/index.php?route=' . ltrim($route, '=')
            . '&api_token=' . rawurlencode($apiKey);

        $response = $this->httpGet($url);
        if ($response === null) {
            return [
                'ok' => false,
                'statuses' => [],
                'queue_status_ids' => [],
                'message' => 'Order queue API request failed or timed out.',
                'bridge_available' => null,
            ];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || !$this->isApiResponseOk($decoded)) {
            $message = is_array($decoded) ? $this->extractApiMessage($decoded) : 'Invalid JSON';

            return [
                'ok' => false,
                'statuses' => [],
                'queue_status_ids' => [],
                'message' => $message !== '' ? $message : 'Order queue API returned an error.',
                'bridge_available' => is_array($decoded) ? ($decoded['bridge_available'] ?? null) : null,
            ];
        }

        $statuses = $decoded['queue_statuses'] ?? [];
        if (!is_array($statuses)) {
            $statuses = [];
        }

        $queueIds = $decoded['queue_status_ids'] ?? [];
        if (!is_array($queueIds)) {
            $queueIds = [];
        }

        return [
            'ok' => true,
            'statuses' => $statuses,
            'queue_status_ids' => array_values(array_map('strval', $queueIds)),
            'message' => null,
            'bridge_available' => $decoded['bridge_available'] ?? null,
        ];
    }

    /** @deprecated Use fetchWarehouseProductsPage for v1.7.1 preview flow */
    public function fetchWarehouseProducts(): array
    {
        $result = $this->fetchWarehouseProductsPage(1);

        return $result['rows'] ?? [];
    }

    public function fetchWarehouseProductsPage(int $page = 1): array
    {
        $page = max(1, $page);
        $limit = $this->pageLimit();
        $route = trim((string) config('opencart.product_api_route', ''));

        if (!(bool) config('opencart.product_sync_enabled', true)) {
            return $this->emptyProductPage($page, $limit, null, 'Product sync is disabled in System → Sync/API Settings.');
        }

        if ($route === '') {
            return $this->emptyProductPage($page, $limit, null, 'Product API route is not configured. Set it in System → Sync/API Settings.');
        }

        if ($this->isLiveReadEnabled()) {
            return $this->fetchLiveWarehouseProductsPage($route, $page, $limit);
        }

        if ($this->isDemoMode()) {
            return $this->fetchDemoWarehouseProductsPage($page, $limit);
        }

        return $this->emptyProductPage($page, $limit, false, 'OpenCart connection disabled.');
    }

    public function warehouseProductPullAvailable(): bool
    {
        if (!(bool) config('opencart.product_sync_enabled', true)) {
            return false;
        }

        $route = trim((string) config('opencart.product_api_route', ''));

        return $route !== '' && ($this->isLiveReadEnabled() || $this->isDemoMode());
    }

    public function connectionStatus(): array
    {
        $test = $this->testConnection();
        $productRoute = trim((string) config('opencart.product_api_route', ''));
        $productPull = $this->warehouseProductPullAvailable();

        return [
            'status' => ($test['ok'] ?? false) ? ($test['mode'] === 'demo' ? 'demo' : 'connected') : 'not_connected',
            'message' => (string) ($test['message'] ?? ''),
            'mode' => (string) ($test['mode'] ?? 'off'),
            'product_route_configured' => $productRoute !== '',
            'product_pull_available' => $productPull,
            'warehouse_product_count' => 0,
            'bridge_available' => $test['bridge_available'] ?? null,
        ];
    }

    public function productSyncStatus(): array
    {
        $connection = $this->connectionStatus();
        $route = trim((string) config('opencart.product_api_route', ''));

        return [
            'mode' => $connection['mode'] ?? 'off',
            'status' => $connection['status'] ?? 'not_connected',
            'message' => $connection['message'] ?? '',
            'product_route' => $route !== '' ? $route : '(not configured)',
            'product_pull_available' => (bool) ($connection['product_pull_available'] ?? false),
            'warehouse_product_count' => (int) ($connection['warehouse_product_count'] ?? 0),
            'max_products_per_page' => $this->pageLimit(),
            'bridge_available' => $connection['bridge_available'] ?? null,
            'read_only' => true,
        ];
    }

    private function fetchDemoWarehouseProductsPage(int $page, int $limit): array
    {
        $products = config('opencart.demo_warehouse_products', []);
        $partition = $this->partitionSupplierProducts(is_array($products) ? $products : []);
        $normalized = $this->normalizeWarehouseProducts($partition['rows']);
        $result = $this->paginateArray($normalized, $page, $limit, true);
        $result['skip_stats'] = $partition['skip_stats'];

        return $result;
    }

    private function fetchLiveWarehouseProductsPage(string $route, int $page, int $limit): array
    {
        $baseUrl = rtrim((string) config('opencart.api_base_url', ''), '/');
        $apiKey = (string) config('opencart.api_key', '');
        if ($baseUrl === '' || $apiKey === '') {
            return $this->emptyProductPage($page, $limit, false, 'Missing api_base_url or api_key.');
        }

        $pageParam = (string) config('opencart.api_page_param', 'page');
        $limitParam = (string) config('opencart.api_limit_param', 'limit');
        $url = $baseUrl . '/index.php?route=' . ltrim($route, '=')
            . '&api_token=' . rawurlencode($apiKey)
            . '&' . rawurlencode($pageParam) . '=' . $page
            . '&' . rawurlencode($limitParam) . '=' . $limit;

        $response = $this->httpGet($url);
        if ($response === null) {
            return $this->emptyProductPage($page, $limit, false, 'Product API request failed or timed out.');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return $this->emptyProductPage($page, $limit, null, 'Product API returned invalid JSON.');
        }

        if ($this->isExplicitApiFailure($decoded)) {
            $message = $this->extractApiMessage($decoded);

            return $this->emptyProductPage(
                $page,
                $limit,
                $this->messageIndicatesBridgeProblem($message) ? false : null,
                $this->messageIndicatesBridgeProblem($message) ? ($message !== '' ? $message : self::BRIDGE_WARNING) : ($message !== '' ? $message : 'Product API returned an error.')
            );
        }

        $products = $decoded['products'] ?? $decoded['data'] ?? null;
        if (!is_array($products)) {
            if ($this->isApiResponseOk($decoded)) {
                return [
                    'bridge_available' => $this->resolveBridgeAvailable($decoded),
                    'bridge_warning' => null,
                    'rows' => [],
                    'page' => $page,
                    'per_page' => $limit,
                    'has_previous' => $page > 1,
                    'has_next' => false,
                    'message' => null,
                    'skip_stats' => self::emptySkipStats(),
                ];
            }

            return $this->emptyProductPage($page, $limit, null, 'Product API returned an unexpected response shape.');
        }

        $partition = $this->partitionSupplierProducts($products);
        $rows = $this->filterWarehouseRows($this->normalizeWarehouseProducts($partition['rows']));
        $bridgeAvailable = $this->resolveBridgeAvailable($decoded);
        if (!$bridgeAvailable && $rows !== []) {
            $bridgeAvailable = true;
        }

        $hasNext = (bool) ($decoded['has_next'] ?? false);
        if (!$hasNext && count($rows) >= $limit) {
            $hasNext = true;
        }

        return [
            'bridge_available' => $bridgeAvailable,
            'bridge_warning' => null,
            'rows' => array_slice($rows, 0, $limit),
            'page' => $page,
            'per_page' => $limit,
            'has_previous' => $page > 1,
            'has_next' => $hasNext,
            'message' => null,
            'skip_stats' => $partition['skip_stats'],
        ];
    }

    private function fetchConnectionTest(): array
    {
        $baseUrl = rtrim((string) config('opencart.api_base_url', ''), '/');
        $apiKey = (string) config('opencart.api_key', '');
        if ($baseUrl === '' || $apiKey === '') {
            return [
                'ok' => false,
                'message' => 'Missing api_base_url or api_key.',
                'bridge_available' => null,
            ];
        }

        $route = trim((string) config('opencart.connection_test_api_route', 'api/ibs/connection_test'));
        if ($route === '') {
            return [
                'ok' => false,
                'message' => 'Connection test route is not configured.',
                'bridge_available' => null,
            ];
        }

        $url = $baseUrl . '/index.php?route=' . ltrim($route, '=')
            . '&api_token=' . rawurlencode($apiKey);

        $response = $this->httpGet($url);
        if ($response === null) {
            return [
                'ok' => false,
                'message' => 'Connection test request failed or timed out.',
                'bridge_available' => null,
            ];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'message' => 'Connection test returned invalid JSON.',
                'bridge_available' => null,
            ];
        }

        if (!$this->isApiResponseOk($decoded)) {
            $message = $this->extractApiMessage($decoded);

            return [
                'ok' => false,
                'message' => $message !== '' ? $message : 'Connection test reported failure.',
                'bridge_available' => $decoded['bridge_available'] ?? null,
            ];
        }

        if (!$this->isReadOnlyApiResponse($decoded)) {
            return [
                'ok' => false,
                'message' => 'Connection test did not confirm read_only=true.',
                'bridge_available' => $decoded['bridge_available'] ?? null,
            ];
        }

        $message = trim((string) ($decoded['message'] ?? ''));
        if ($message === '') {
            $message = ucfirst($this->resolvedSourceMode()) . ' OpenCart read connection OK.';
        }

        return [
            'ok' => true,
            'message' => $message,
            'bridge_available' => $decoded['bridge_available'] ?? null,
        ];
    }

    private function fetchLiveSupplierOrdersPage(int $page, int $limit): array
    {
        $baseUrl = rtrim((string) config('opencart.api_base_url', ''), '/');
        $apiKey = (string) config('opencart.api_key', '');
        if ($baseUrl === '' || $apiKey === '') {
            return $this->emptyPageWithMessage($page, $limit, 'Missing api_base_url or api_key.');
        }

        $route = trim((string) config('opencart.order_api_route', 'api/ibs/orders'));
        $pageParam = (string) config('opencart.api_page_param', 'page');
        $limitParam = (string) config('opencart.api_limit_param', 'limit');
        $url = $baseUrl . '/index.php?route=' . ltrim($route, '=')
            . '&api_token=' . rawurlencode($apiKey)
            . '&' . rawurlencode($limitParam) . '=' . $limit
            . '&' . rawurlencode($pageParam) . '=' . $page;

        $response = $this->httpGet($url);
        if ($response === null) {
            return $this->emptyPageWithMessage($page, $limit, 'Order API request failed or timed out.');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return $this->emptyPageWithMessage($page, $limit, 'Order API returned invalid JSON.');
        }

        if ($this->isExplicitApiFailure($decoded)) {
            $message = $this->extractApiMessage($decoded);

            return $this->emptyPageWithMessage(
                $page,
                $limit,
                $message !== '' ? $message : 'Order API returned an error.'
            );
        }

        $orders = $decoded['orders'] ?? $decoded['data'] ?? [];
        if (!is_array($orders)) {
            $orders = [];
        }

        $filterApplied = (string) ($decoded['filter_applied'] ?? '');
        $message = null;
        if ($filterApplied === 'queue_empty') {
            $message = trim((string) ($decoded['warning'] ?? 'No supplier queue statuses configured in OpenCart connector admin.'));
        }

        $rows = $this->normalizeOrders($orders);

        return [
            'bridge_available' => $decoded['bridge_available'] ?? null,
            'bridge_warning' => null,
            'rows' => array_slice($rows, 0, $limit),
            'page' => $page,
            'per_page' => $limit,
            'has_previous' => (bool) ($decoded['has_previous'] ?? $page > 1),
            'has_next' => (bool) ($decoded['has_next'] ?? false),
            'message' => $message,
            'filter_applied' => $filterApplied !== '' ? $filterApplied : null,
            'queue_status_ids' => is_array($decoded['queue_status_ids'] ?? null) ? $decoded['queue_status_ids'] : [],
        ];
    }

    private function fetchDemoSupplierOrdersPage(int $page, int $limit): array
    {
        $queueFetch = $this->fetchOrderQueueStatuses();
        $queueIds = $queueFetch['queue_status_ids'] ?? [];
        $rawOrders = config('opencart.demo_orders', []);
        if (!is_array($rawOrders)) {
            $rawOrders = [];
        }

        $filtered = [];
        foreach ($rawOrders as $order) {
            if (!is_array($order)) {
                continue;
            }

            $queueStatus = trim((string) ($order['connector_queue_status'] ?? $order['order_status_id'] ?? $order['source_status_id'] ?? ''));
            if ($queueIds !== [] && !in_array($queueStatus, $queueIds, true)) {
                continue;
            }

            if (empty($order['in_supplier_queue']) && $queueIds !== []) {
                continue;
            }

            $products = is_array($order['products'] ?? null) ? $order['products'] : ($order['items'] ?? []);
            $warehouseLines = [];
            foreach ($products as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $fromWarehouse = $line['from_warehouse'] ?? null;
                if ($fromWarehouse === null || self::isStrictFromWarehouseValue($fromWarehouse)) {
                    if ($fromWarehouse !== null && !self::isStrictFromWarehouseValue($fromWarehouse)) {
                        continue;
                    }
                    $warehouseLines[] = $line;
                }
            }

            if ($warehouseLines === [] && $products !== []) {
                continue;
            }

            $orderCopy = $order;
            $orderCopy['products'] = $warehouseLines;
            $filtered[] = $orderCopy;
        }

        $normalized = $this->normalizeOrders($filtered);
        $result = $this->paginateArray($normalized, $page, $limit, true);
        if ($queueIds === []) {
            $result['message'] = 'No supplier queue statuses configured in demo data.';
        }

        return $result;
    }

    private function normalizeOrders(array $orders): array
    {
        $normalized = [];
        foreach ($orders as $order) {
            if (!is_array($order)) {
                continue;
            }

            $items = [];
            $totalQty = 0;
            foreach (($order['products'] ?? $order['items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $fromWarehouse = $item['from_warehouse'] ?? null;
                if ($fromWarehouse !== null && !self::isStrictFromWarehouseValue($fromWarehouse)) {
                    continue;
                }
                $qty = (int) ($item['quantity'] ?? 1);
                $totalQty += $qty;
                $items[] = [
                    'product_id' => isset($item['product_id']) ? (string) $item['product_id'] : null,
                    'product_name' => (string) ($item['name'] ?? $item['product_name'] ?? 'Order item'),
                    'variant_label' => $item['variant_label'] ?? $item['option'] ?? null,
                    'quantity' => $qty,
                    'selling_price' => (float) ($item['price'] ?? $item['selling_price'] ?? 0),
                    'sku' => $item['sku'] ?? $item['model'] ?? null,
                    'image' => trim((string) ($item['image'] ?? '')) ?: null,
                    'from_warehouse' => 1,
                ];
            }

            if ($items === [] && ($order['products'] ?? $order['items'] ?? []) !== []) {
                continue;
            }

            $queueStatusId = (string) ($order['connector_queue_status'] ?? $order['order_status_id'] ?? $order['source_status_id'] ?? '');
            $queueStatusLabel = (string) ($order['connector_queue_label'] ?? $order['order_status'] ?? $order['source_status'] ?? '');

            $normalized[] = [
                'source_order_id' => (string) ($order['order_id'] ?? $order['source_order_id'] ?? ''),
                'source_order_reference' => (string) ($order['order_id'] ?? $order['source_order_reference'] ?? ''),
                'source_invoice_reference' => $order['invoice_no'] ?? $order['source_invoice_reference'] ?? null,
                'source_status_id' => $queueStatusId,
                'source_status' => $queueStatusLabel,
                'connector_queue_status' => $queueStatusId,
                'connector_queue_label' => $queueStatusLabel,
                'in_supplier_queue' => (bool) ($order['in_supplier_queue'] ?? true),
                'has_warehouse_product' => $items !== [],
                'customer_name' => trim((string) ($order['firstname'] ?? '') . ' ' . (string) ($order['lastname'] ?? $order['customer_name'] ?? '')),
                'customer_phone' => (string) ($order['telephone'] ?? $order['customer_phone'] ?? ''),
                'customer_address' => (string) ($order['shipping_address_1'] ?? $order['customer_address'] ?? ''),
                'order_total' => (float) ($order['total'] ?? $order['order_total'] ?? 0),
                'courier_status' => (string) ($order['courier_status'] ?? $order['shipping_status'] ?? ''),
                'consignment_id' => (string) ($order['consignment_id'] ?? $order['tracking_number'] ?? $order['tracking_no'] ?? ''),
                'courier_name' => (string) ($order['courier_name'] ?? $order['shipping_method'] ?? ''),
                'total_quantity' => $totalQty,
                'items' => $items,
            ];
        }

        return $normalized;
    }

    private function normalizeWarehouseProducts(array $products): array
    {
        $resolver = new OpenCartOptionImageResolver();
        $normalized = [];
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }

            $rawFromWarehouse = $product['from_warehouse'] ?? $product['fromWarehouse'] ?? null;
            $fromWarehouse = self::isStrictFromWarehouseValue($rawFromWarehouse) ? 1 : -1;
            $optionsRaw = $product['options'] ?? $product['option_lines'] ?? $product['variants'] ?? null;
            $options = $resolver->enrichOptions($this->normalizeOptions(is_array($optionsRaw) ? $optionsRaw : []));
            $hasOptionsKey = array_key_exists('options', $product)
                || array_key_exists('option_lines', $product)
                || array_key_exists('variants', $product);
            $productType = strtolower(trim((string) ($product['type'] ?? $product['product_type'] ?? '')));
            $variableIntent = $hasOptionsKey || in_array($productType, ['variable', 'variant', 'options'], true);

            $normalized[] = [
                'source_product_id' => (string) ($product['product_id'] ?? $product['source_product_id'] ?? ''),
                'product_name' => (string) ($product['name'] ?? $product['product_name'] ?? ''),
                'source_model' => (string) ($product['model'] ?? $product['source_model'] ?? ''),
                'source_stock' => isset($product['quantity']) ? (int) $product['quantity'] : (isset($product['source_stock']) ? (int) $product['source_stock'] : null),
                'source_price' => isset($product['price']) ? round((float) $product['price'], 2) : null,
                'source_status' => (string) ($product['status'] ?? $product['source_status'] ?? ''),
                'image_path' => trim((string) ($product['image'] ?? $product['image_path'] ?? $product['thumb'] ?? '')) ?: null,
                'from_warehouse' => $fromWarehouse,
                'options' => $options,
                'variable_intent' => $variableIntent,
                'sync_options_state' => $this->resolveSyncOptionsState($variableIntent, $options),
            ];
        }

        return $normalized;
    }

    private function normalizeOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            $sourceOptionId = trim((string) ($option['option_id'] ?? $option['product_option_id'] ?? $option['source_option_id'] ?? ''));
            $sourceOptionValueId = trim((string) ($option['option_value_id'] ?? $option['product_option_value_id'] ?? $option['source_option_value_id'] ?? ''));
            if ($sourceOptionId === '' && $sourceOptionValueId === '') {
                continue;
            }

            $price = isset($option['price']) ? round((float) $option['price'], 2) : null;
            $prefix = trim((string) ($option['price_prefix'] ?? ''));

            $normalized[] = [
                'product_option_id' => $sourceOptionId !== '' ? $sourceOptionId : ('opt-' . (count($normalized) + 1)),
                'product_option_value_id' => $sourceOptionValueId !== '' ? $sourceOptionValueId : ('val-' . (count($normalized) + 1)),
                'source_option_id' => $sourceOptionId !== '' ? $sourceOptionId : ('opt-' . (count($normalized) + 1)),
                'source_option_value_id' => $sourceOptionValueId !== '' ? $sourceOptionValueId : ('val-' . (count($normalized) + 1)),
                'option_name' => (string) ($option['option_name'] ?? $option['name'] ?? 'Option'),
                'option_value' => (string) ($option['option_value'] ?? $option['value'] ?? ''),
                'source_model' => trim((string) ($option['model'] ?? $option['source_model'] ?? '')) ?: null,
                'source_stock' => isset($option['quantity']) ? (int) $option['quantity'] : (isset($option['source_stock']) ? (int) $option['source_stock'] : null),
                'option_image_path' => OpenCartOptionImageResolver::extractFromPayload($option),
                'price' => $price,
                'price_prefix' => $prefix !== '' ? $prefix : null,
                'price_display' => $this->formatOptionPrice($price, $prefix),
                'subtract' => isset($option['subtract']) ? (int) $option['subtract'] : null,
                'required' => isset($option['required']) ? (int) $option['required'] : null,
            ];
        }

        return $normalized;
    }

    private function formatOptionPrice(?float $price, string $prefix): ?string
    {
        if ($price === null) {
            return null;
        }

        $sign = $prefix === '-' ? '-' : '+';

        return $sign . number_format($price, 2);
    }

    /**
     * @param array<int, mixed> $products
     * @return array{rows: array<int, array<string, mixed>>, skip_stats: array<string, int>}
     */
    public function partitionSupplierProducts(array $products): array
    {
        $totalReceived = 0;
        $supplierRows = [];
        $skippedNotSupplier = 0;
        $skippedMissing = 0;

        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }

            $totalReceived++;
            if (!array_key_exists('from_warehouse', $product) && !array_key_exists('fromWarehouse', $product)) {
                $skippedMissing++;
                continue;
            }

            $fromWarehouse = $product['from_warehouse'] ?? $product['fromWarehouse'] ?? null;
            if (!self::isStrictFromWarehouseValue($fromWarehouse)) {
                if ($fromWarehouse === null || $fromWarehouse === '' || $fromWarehouse === false) {
                    $skippedMissing++;
                } else {
                    $skippedNotSupplier++;
                }
                continue;
            }

            $supplierRows[] = $product;
        }

        return [
            'rows' => $supplierRows,
            'skip_stats' => [
                'total_received' => $totalReceived,
                'supplier_products' => count($supplierRows),
                'skipped_not_supplier' => $skippedNotSupplier,
                'skipped_missing_from_warehouse' => $skippedMissing,
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<int, array<string, mixed>>
     */
    private function filterWarehouseRows(array $products): array
    {
        return array_values(array_filter(
            $products,
            [self::class, 'isStrictSupplierProduct']
        ));
    }

    private function isApiResponseOk(array $decoded): bool
    {
        if (($decoded['success'] ?? null) === true) {
            return true;
        }

        if (($decoded['ok'] ?? null) === true) {
            return true;
        }

        return false;
    }

    private function isReadOnlyApiResponse(array $decoded): bool
    {
        return ($decoded['read_only'] ?? false) === true;
    }

    private function isExplicitApiFailure(array $decoded): bool
    {
        if (($decoded['success'] ?? null) === false) {
            return true;
        }

        if (($decoded['ok'] ?? null) === false) {
            return true;
        }

        return false;
    }

    private function extractApiMessage(array $decoded): string
    {
        $message = trim((string) ($decoded['error'] ?? $decoded['message'] ?? ''));

        return $message;
    }

    private function messageIndicatesBridgeProblem(string $message): bool
    {
        if ($message === '') {
            return false;
        }

        $lower = strtolower($message);

        return str_contains($lower, 'bridge')
            || str_contains($lower, 'dispatch')
            || str_contains($lower, 'from_warehouse');
    }

    private function resolveBridgeAvailable(array $decoded): bool
    {
        if (array_key_exists('bridge_available', $decoded)) {
            return (bool) $decoded['bridge_available'];
        }
        if (array_key_exists('dispatch_location_bridge', $decoded)) {
            return (bool) $decoded['dispatch_location_bridge'];
        }

        $products = $decoded['products'] ?? $decoded['data'] ?? [];
        if (!is_array($products) || $products === []) {
            return false;
        }

        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }
            if (array_key_exists('from_warehouse', $product) || array_key_exists('fromWarehouse', $product)) {
                return true;
            }
        }

        return false;
    }

    private function resolveSyncOptionsState(bool $variableIntent, array $options): string
    {
        if ($options !== []) {
            return 'has_options';
        }

        if ($variableIntent) {
            return 'missing_options';
        }

        return 'simple';
    }

    private function paginateArray(array $rows, int $page, int $limit, ?bool $bridgeAvailable = null): array
    {
        $offset = ($page - 1) * $limit;
        $slice = array_slice($rows, $offset, $limit);

        return [
            'bridge_available' => $bridgeAvailable ?? true,
            'bridge_warning' => ($bridgeAvailable === false) ? self::BRIDGE_WARNING : null,
            'rows' => $slice,
            'page' => $page,
            'per_page' => $limit,
            'has_previous' => $page > 1,
            'has_next' => ($offset + $limit) < count($rows),
            'message' => null,
        ];
    }

    private function paginateResult(array $rows, int $page, int $limit, ?bool $bridgeAvailable): array
    {
        return [
            'bridge_available' => $bridgeAvailable,
            'bridge_warning' => null,
            'rows' => array_slice($rows, 0, $limit),
            'page' => $page,
            'per_page' => $limit,
            'has_previous' => $page > 1,
            'has_next' => count($rows) >= $limit,
            'message' => null,
        ];
    }

    private function emptyPage(int $page, int $limit): array
    {
        return $this->emptyPageWithMessage($page, $limit, 'OpenCart connection disabled.');
    }

    private function emptyPageWithMessage(int $page, int $limit, string $message): array
    {
        return [
            'bridge_available' => null,
            'bridge_warning' => null,
            'rows' => [],
            'page' => $page,
            'per_page' => $limit,
            'has_previous' => $page > 1,
            'has_next' => false,
            'message' => $message,
        ];
    }

    private function isDemoMode(): bool
    {
        $mode = $this->resolvedSourceMode();

        return $mode === 'demo';
    }

    private function isLiveReadEnabled(): bool
    {
        $mode = $this->resolvedSourceMode();
        if ($mode === 'demo') {
            return false;
        }

        return (bool) config('opencart.enabled', false);
    }

    private function resolvedSourceMode(): string
    {
        $mode = strtolower(trim((string) config('opencart.source_mode', '')));
        if (in_array($mode, ['demo', 'staging', 'live'], true)) {
            return $mode;
        }

        if ((bool) config('opencart.demo_mode', false)) {
            return 'demo';
        }

        if ((bool) config('opencart.enabled', false)) {
            return 'staging';
        }

        return 'off';
    }

    private function emptyProductPage(int $page, int $limit, ?bool $bridgeAvailable, ?string $message): array
    {
        return [
            'bridge_available' => $bridgeAvailable ?? false,
            'bridge_warning' => ($bridgeAvailable === false) ? self::BRIDGE_WARNING : null,
            'rows' => [],
            'page' => $page,
            'per_page' => $limit,
            'has_previous' => $page > 1,
            'has_next' => false,
            'message' => $message,
            'skip_stats' => self::emptySkipStats(),
        ];
    }

    private function pageLimit(): int
    {
        $limit = (int) config('opencart.max_rows_per_page', 20);

        return max(1, min($limit, 20));
    }

    private function httpGet(string $url): ?string
    {
        if (!function_exists('curl_init')) {
            $context = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true]]);
            $body = @file_get_contents($url, false, $context);

            return $body === false ? null : $body;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        return $body === false ? null : (string) $body;
    }
}
