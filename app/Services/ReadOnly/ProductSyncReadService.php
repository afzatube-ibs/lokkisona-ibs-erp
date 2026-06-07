<?php

namespace App\Services\ReadOnly;

use App\Services\Read\OpenCartReadClient;

/**
 * Product sync read facade for Sync Preview and Product Control (v1.7.0–v1.7.1).
 */
class ProductSyncReadService
{
    public function status(): array
    {
        $client = new OpenCartReadClient();
        $base = $client->productSyncStatus();
        $connection = $client->testConnection();

        return array_merge($base, [
            'connection_ok' => (bool) ($connection['ok'] ?? false),
            'source_mode' => (new SyncApiSettingsReadService())->resolvedSourceMode(),
            'settings_url' => url('/sync-api-settings'),
            'read_only_lock' => (bool) config('opencart.read_only_lock', true),
            'product_sync_enabled' => (bool) config('opencart.product_sync_enabled', true),
            'order_sync_enabled' => (bool) config('opencart.order_sync_enabled', true),
            'rules' => [
                'Read-only from Lokkisona/OpenCart — no ERP writes back',
                'Dispatch Location bridge required (from_warehouse = 1 only)',
                'Max 20 products per preview page',
                'Preview → owner confirm → import (no silent pull)',
                'Parent + option/variant lines in preview; supplier fields editable in ERP only',
                'Supplier cost/stock/note preserved on re-import',
            ],
        ]);
    }
}
