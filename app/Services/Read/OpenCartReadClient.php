<?php

namespace App\Services\Read;

/**
 * Read-only OpenCart/PIT client (v0.5.7). SELECT/API read only — no ERP writes.
 */
class OpenCartReadClient
{
    public function fetchSupplierOrders(): array
    {
        if ((bool) config('opencart.enabled', false)) {
            return $this->fetchLiveOrders();
        }

        if ((bool) config('opencart.demo_mode', false)) {
            return $this->demoOrders();
        }

        return [];
    }

    public function fetchWarehouseProducts(): array
    {
        $route = trim((string) config('opencart.product_api_route', ''));
        if ($route === '') {
            return [];
        }

        if ((bool) config('opencart.enabled', false)) {
            return $this->fetchLiveWarehouseProducts($route);
        }

        if ((bool) config('opencart.demo_mode', false)) {
            return $this->demoWarehouseProducts();
        }

        return [];
    }

    public function warehouseProductPullAvailable(): bool
    {
        $route = trim((string) config('opencart.product_api_route', ''));

        return $route !== '' && ((bool) config('opencart.enabled', false) || (bool) config('opencart.demo_mode', false));
    }

    public function connectionStatus(): array
    {
        if ((bool) config('opencart.enabled', false)) {
            $orders = $this->fetchLiveOrders();

            return [
                'status' => 'connected',
                'message' => 'OpenCart API enabled. Fetched ' . count($orders) . ' supplier-handled order(s).',
                'mode' => 'live',
            ];
        }

        if ((bool) config('opencart.demo_mode', false)) {
            return [
                'status' => 'demo',
                'message' => 'Demo mode active. Set opencart.enabled=true on staging with API credentials for live reads.',
                'mode' => 'demo',
            ];
        }

        return [
            'status' => 'not_connected',
            'message' => 'OpenCart connection disabled. Enable demo_mode or enabled in config/opencart.php.',
            'mode' => 'off',
        ];
    }

    private function demoOrders(): array
    {
        $orders = config('opencart.demo_orders', []);

        return is_array($orders) ? array_slice($orders, 0, $this->maxOrders()) : [];
    }

    private function fetchLiveOrders(): array
    {
        $baseUrl = rtrim((string) config('opencart.api_base_url', ''), '/');
        $apiKey = (string) config('opencart.api_key', '');
        if ($baseUrl === '' || $apiKey === '') {
            return [];
        }

        $url = $baseUrl . '/index.php?route=api/order&api_token=' . rawurlencode($apiKey) . '&limit=' . $this->maxOrders();
        $response = $this->httpGet($url);
        if ($response === null) {
            return [];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [];
        }

        $orders = $decoded['orders'] ?? $decoded['data'] ?? $decoded;
        if (!is_array($orders)) {
            return [];
        }

        return array_slice($this->normalizeOrders($orders), 0, $this->maxOrders());
    }

    private function normalizeOrders(array $orders): array
    {
        $normalized = [];
        foreach ($orders as $order) {
            if (!is_array($order)) {
                continue;
            }

            $items = [];
            foreach (($order['products'] ?? $order['items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $items[] = [
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => (string) ($item['name'] ?? $item['product_name'] ?? 'Order item'),
                    'variant_label' => $item['variant_label'] ?? null,
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'selling_price' => (float) ($item['price'] ?? $item['selling_price'] ?? 0),
                    'sku' => $item['sku'] ?? $item['model'] ?? null,
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
                'items' => $items,
            ];
        }

        return $normalized;
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

    private function maxOrders(): int
    {
        return max(1, min((int) config('opencart.max_orders_per_request', 50), 50));
    }

    private function demoWarehouseProducts(): array
    {
        $products = config('opencart.demo_warehouse_products', []);

        return is_array($products) ? array_slice($this->normalizeWarehouseProducts($products), 0, 50) : [];
    }

    private function fetchLiveWarehouseProducts(string $route): array
    {
        $baseUrl = rtrim((string) config('opencart.api_base_url', ''), '/');
        $apiKey = (string) config('opencart.api_key', '');
        if ($baseUrl === '' || $apiKey === '') {
            return [];
        }

        $separator = str_contains($route, '?') ? '&' : '?';
        $url = $baseUrl . '/index.php?route=' . ltrim($route, '=') . $separator . 'api_token=' . rawurlencode($apiKey);
        $response = $this->httpGet($url);
        if ($response === null) {
            return [];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [];
        }

        $products = $decoded['products'] ?? $decoded['data'] ?? $decoded;
        if (!is_array($products)) {
            return [];
        }

        return array_slice($this->normalizeWarehouseProducts($products), 0, 50);
    }

    private function normalizeWarehouseProducts(array $products): array
    {
        $normalized = [];
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }

            $fromWarehouse = (int) ($product['from_warehouse'] ?? $product['fromWarehouse'] ?? 0);
            $normalized[] = [
                'source_product_id' => (string) ($product['product_id'] ?? $product['source_product_id'] ?? ''),
                'product_name' => (string) ($product['name'] ?? $product['product_name'] ?? ''),
                'source_model' => (string) ($product['model'] ?? $product['source_model'] ?? ''),
                'source_stock' => isset($product['quantity']) ? (int) $product['quantity'] : (isset($product['source_stock']) ? (int) $product['source_stock'] : null),
                'from_warehouse' => $fromWarehouse,
            ];
        }

        return $normalized;
    }
}
