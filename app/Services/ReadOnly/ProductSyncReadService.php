<?php

namespace App\Services\ReadOnly;

use App\Services\Read\OpenCartReadClient;

/**
 * Product sync read facade for Sync Preview and Product Control (v1.7.0).
 */
class ProductSyncReadService
{
    public function status(): array
    {
        $client = new OpenCartReadClient();
        $base = $client->productSyncStatus();

        return array_merge($base, [
            'rules' => [
                'Read-only from Lokkisona/OpenCart — no ERP writes back',
                'From Warehouse = Yes products only',
                'Max 50 parent products per pull',
                'Parent + option/variant lines sync when options are returned',
                'Supplier cost/stock/note preserved on re-pull',
                'No manual ERP product create',
            ],
        ]);
    }
}
