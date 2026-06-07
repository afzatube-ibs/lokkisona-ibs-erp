<?php

namespace App\Services\Read;

/**
 * Read-only OpenCart/PIT client (v0.5.7, v1.7.0 product sync, v1.7.1 paginated live test, v1.8.4 real API repair).
 */
class OpenCartReadClient
{
    public const BRIDGE_WARNING = 'Dispatch Location bridge not found. Product sync cannot safely identify IBS supplier products.';

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
            return $this->paginateResult(
                $this->fetchLiveOrdersPage($page, $limit),
                $page,
                $limit,
                null
            );
        }

        if ($this->isDemoMode()) {
            $all = $this->normalizeOrders(config('opencart.demo_orders', []));

            return $this->paginateArray($all, $page, $limit);
        }

        return $this->emptyPageWithMessage($page, $limit, 'OpenCart connection disabled.');
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
        $normalized = $this->applyBridgeFilter($this->normalizeWarehouseProducts(is_array($products) ? $products : []), true);

        return $this->paginateArray($normalized, $page, $limit, true);
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
                ];
            }

            return $this->emptyProductPage($page, $limit, null, 'Product API returned an unexpected response shape.');
        }

        $rows = $this->filterWarehouseRows($this->normalizeWarehouseProducts($products));
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

    private function fetchLiveOrdersPage(int $page, int $limit): array
    {
        $baseUrl = rtrim((string) config('opencart.api_base_url', ''), '/');
        $apiKey = (string) config('opencart.api_key', '');
        if ($baseUrl === '' || $apiKey === '') {
            return [];
        }

        $route = trim((string) config('opencart.order_api_route', 'api/order'));
        $pageParam = (string) config('opencart.api_page_param', 'page');
        $limitParam = (string) config('opencart.api_limit_param', 'limit');
        $url = $baseUrl . '/index.php?route=' . ltrim($route, '=')
            . '&api_token=' . rawurlencode($apiKey)
            . '&' . rawurlencode($limitParam) . '=' . $limit
            . '&' . rawurlencode($pageParam) . '=' . $page;

        $response = $this->httpGet($url);
        if ($response === null) {
            return [];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [];
        }

        $orders = $decoded['orders'] ?? $decoded['data'] ?? $decoded;

        return is_array($orders) ? $this->normalizeOrders($orders) : [];
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
                ];
            }

            $normalized[] = [
                'source_order_id' => (string) ($order['order_id'] ?? $order['source_order_id'] ?? ''),
                'source_order_reference' => (string) ($order['order_id'] ?? $order['source_order_reference'] ?? ''),
                'source_invoice_reference' => $order['invoice_no'] ?? $order['source_invoice_reference'] ?? null,
                'source_status_id' => (string) ($order['order_status_id'] ?? $order['source_status_id'] ?? ''),
                'source_status' => (string) ($order['order_status'] ?? $order['source_status'] ?? ''),
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
        $normalized = [];
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }

            $fromWarehouse = (int) ($product['from_warehouse'] ?? $product['fromWarehouse'] ?? -1);
            $optionsRaw = $product['options'] ?? $product['option_lines'] ?? $product['variants'] ?? null;
            $options = $this->normalizeOptions(is_array($optionsRaw) ? $optionsRaw : []);
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
                'option_image_path' => trim((string) ($option['image'] ?? $option['option_image_path'] ?? '')) ?: null,
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

    private function applyBridgeFilter(array $products, bool $bridgeConfirmed): array
    {
        $bridgeRequired = (bool) config('opencart.dispatch_bridge_required', true);
        if (!$bridgeConfirmed && $bridgeRequired) {
            return [];
        }

        return $this->filterWarehouseRows($products);
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<int, array<string, mixed>>
     */
    private function filterWarehouseRows(array $products): array
    {
        return array_values(array_filter(
            $products,
            static fn (array $row): bool => (int) ($row['from_warehouse'] ?? 0) === 1
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
