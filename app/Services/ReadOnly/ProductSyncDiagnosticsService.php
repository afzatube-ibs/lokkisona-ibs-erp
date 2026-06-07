<?php

namespace App\Services\ReadOnly;

use App\ReadFoundation\WriteGate;
use App\Services\Read\OpenCartReadClient;

/**
 * Product sync diagnostics for Sync Preview help block (v1.8.2).
 */
class ProductSyncDiagnosticsService
{
    public function analyze(?array $productPreview = null): array
    {
        $settings = (new SyncApiSettingsReadService())->formState();
        $sourceMode = (string) ($settings['source_mode'] ?? 'demo');
        $productEnabled = (bool) ($settings['product_sync_enabled'] ?? true);
        $bridgeRequired = (bool) ($settings['dispatch_bridge_required'] ?? true);
        $productRoute = trim((string) ($settings['product_api_route'] ?? ''));
        $apiUrl = trim((string) ($settings['api_base_url'] ?? ''));
        $apiKeySet = (bool) ($settings['api_key_set'] ?? false);

        $client = new OpenCartReadClient();
        $connection = $client->testConnection();
        $fetch = $client->fetchWarehouseProductsPage(1);
        $gate = WriteGate::productSyncImport();

        $issues = [];
        $checks = [];

        $checks['product_sync_enabled'] = [
            'ok' => $productEnabled,
            'label' => 'Product sync enabled',
            'detail' => $productEnabled ? 'Enabled in Sync/API Settings.' : 'Disabled in Sync/API Settings.',
        ];
        if (!$productEnabled) {
            $issues[] = $this->issue(
                'product_sync_disabled',
                'Product sync disabled',
                'Product sync is turned off in System → Sync/API Settings.',
                'Open Sync/API Settings and enable Product Sync, or stay on demo mode for local testing.'
            );
        }

        $checks['api_route'] = [
            'ok' => $productRoute !== '',
            'label' => 'Product API route',
            'detail' => $productRoute !== '' ? $productRoute : 'Not configured.',
        ];
        if ($productRoute === '') {
            $issues[] = $this->issue(
                'api_route_missing',
                'Product API route missing',
                'Product API route is empty in Sync/API Settings.',
                'Set the OpenCart warehouse product route in System → Sync/API Settings.'
            );
        }

        if ($sourceMode !== 'demo') {
            $checks['source_url'] = [
                'ok' => $apiUrl !== '',
                'label' => 'Source URL',
                'detail' => $apiUrl !== '' ? $apiUrl : 'Missing.',
            ];
            if ($apiUrl === '') {
                $issues[] = $this->issue(
                    'source_url_missing',
                    'Source URL missing',
                    'Staging/live mode requires a Source URL (for example https://www.staging.lokkisona.com).',
                    'Set Source URL in System → Sync/API Settings.'
                );
            }

            $checks['api_key'] = [
                'ok' => $apiKeySet,
                'label' => 'API key saved',
                'detail' => $apiKeySet ? 'Key is stored on server (hidden).' : 'No key saved yet.',
            ];
            if (!$apiKeySet) {
                $issues[] = $this->issue(
                    'api_key_missing',
                    'API key missing',
                    'Staging/live connection requires an API key/token saved on the server.',
                    'Enter the API key in System → Sync/API Settings. It will not be shown again after save.'
                );
            }
        }

        $connectionOk = (bool) ($connection['ok'] ?? false);
        $checks['connection'] = [
            'ok' => $connectionOk,
            'label' => 'Connection test',
            'detail' => (string) ($connection['message'] ?? 'Not tested'),
        ];
        if ($productEnabled && !$connectionOk) {
            $issues[] = $this->issue(
                'connection_problem',
                'Connection problem',
                (string) ($connection['message'] ?? 'OpenCart read connection failed.'),
                'Check Source Mode, Source URL, API key, and OpenCart API availability in System → Sync/API Settings, then use Test Connection.'
            );
        }

        $bridgeAvailable = ($fetch['bridge_available'] ?? null) === true;
        $checks['bridge'] = [
            'ok' => !$bridgeRequired || $bridgeAvailable,
            'label' => 'Dispatch Location bridge',
            'detail' => $bridgeAvailable ? 'Bridge reported (from_warehouse available).' : 'Bridge missing or not confirmed.',
        ];
        if ($productEnabled && $bridgeRequired && !$bridgeAvailable) {
            $issues[] = $this->issue(
                'bridge_missing',
                'Dispatch Location bridge missing',
                OpenCartReadClient::BRIDGE_WARNING,
                'Ensure OpenCart exposes oc_dispatch_location_product with from_warehouse=1 on the product API. Bridge is required — ERP will not fallback to all products.'
            );
        }

        $warehouseCount = count($fetch['rows'] ?? []);
        $checks['warehouse_products'] = [
            'ok' => $warehouseCount > 0,
            'label' => 'from_warehouse products',
            'detail' => $warehouseCount . ' product(s) on page 1 with from_warehouse = 1.',
        ];
        if ($productEnabled && $connectionOk && ($bridgeAvailable || !$bridgeRequired) && $warehouseCount === 0) {
            $issues[] = $this->issue(
                'no_from_warehouse_products',
                'No from_warehouse products',
                'Connection succeeded but no Dispatch Location warehouse products (from_warehouse = 1) were returned.',
                'Verify OpenCart dispatch bridge rows for supplier warehouse products, or check the product API filter on the OpenCart side.'
            );
        }

        $optionIssue = $this->detectOptionLoadIssue($fetch['rows'] ?? [], $productPreview);
        $checks['options'] = [
            'ok' => $optionIssue === null,
            'label' => 'Product options/variants',
            'detail' => $optionIssue !== null ? $optionIssue['detail'] : 'Option lines loaded or not required.',
        ];
        if ($optionIssue !== null) {
            $issues[] = $optionIssue;
        }

        $erpReady = (bool) ($gate['ready'] ?? false);
        $checks['erp_tables'] = [
            'ok' => $erpReady,
            'label' => 'ERP product tables',
            'detail' => $erpReady ? 'Required tables ready.' : 'Missing: ' . implode(', ', $gate['missing_tables'] ?? []),
        ];
        if (!$erpReady) {
            $issues[] = $this->issue(
                'erp_table_column',
                'Missing required ERP table/column',
                'Product import requires ERP tables before preview/import can write.',
                'Apply migrations from Dev DB Activation (ibs_products, ibs_product_variants, ibs_business_sources).'
            );
        }

        $categoryGate = WriteGate::supplierProductCategoryColumn();
        if ($erpReady && !($categoryGate['ready'] ?? false)) {
            $checks['erp_category_column'] = [
                'ok' => true,
                'label' => 'Optional category column',
                'detail' => 'Migration 0011 not applied — category field skipped on import (non-blocking).',
            ];
        }

        return [
            'ready' => $issues === [],
            'source_mode' => $sourceMode,
            'checks' => $checks,
            'issues' => $issues,
            'rows' => $this->displayRows($checks, $issues),
            'settings_path' => url('/sync-api-settings'),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $checks
     * @param array<int, array<string, string>> $issues
     * @return array<int, array<string, mixed>>
     */
    private function displayRows(array $checks, array $issues): array
    {
        $fixes = [];
        foreach ($issues as $issue) {
            $fixes[(string) ($issue['code'] ?? '')] = (string) ($issue['fix'] ?? '');
        }

        $order = [
            'connection' => ['code' => 'connection_problem', 'label' => 'Connection problem'],
            'bridge' => ['code' => 'bridge_missing', 'label' => 'Bridge missing'],
            'warehouse_products' => ['code' => 'no_from_warehouse_products', 'label' => 'No warehouse products'],
            'api_route' => ['code' => 'api_route_missing', 'label' => 'API route missing'],
            'options' => ['code' => 'option_variant_not_loaded', 'label' => 'Option/variant not loaded'],
            'erp_tables' => ['code' => 'erp_table_column', 'label' => 'Missing ERP table/column'],
        ];

        $rows = [];
        foreach ($order as $key => $meta) {
            if (!isset($checks[$key])) {
                continue;
            }
            $check = $checks[$key];
            $ok = (bool) ($check['ok'] ?? false);
            $rows[] = [
                'label' => $meta['label'],
                'ok' => $ok,
                'status' => $ok ? 'OK' : 'Issue',
                'fix' => $ok ? '—' : ($fixes[$meta['code']] ?? (string) ($check['detail'] ?? '')),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, mixed> $fetchRows
     * @param array<string, mixed>|null $productPreview
     * @return array<string, string>|null
     */
    private function detectOptionLoadIssue(array $fetchRows, ?array $productPreview): ?array
    {
        $rows = $fetchRows;
        if ($rows === [] && is_array($productPreview) && ($productPreview['rows'] ?? []) !== []) {
            $rows = $productPreview['rows'];
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $state = (string) ($row['sync_options_state'] ?? '');
            $variableIntent = !empty($row['variable_intent']) || $state === 'missing_options';
            $optionCount = count($row['options'] ?? []);

            if ($state === 'missing_options' || ($variableIntent && $optionCount === 0)) {
                $name = trim((string) ($row['product_name'] ?? $row['source_product_id'] ?? 'Product'));

                return $this->issue(
                    'option_variant_not_loaded',
                    'Product option/variant not loaded',
                    $name . ' looks like a variable product but no option lines were returned from OpenCart.',
                    'Check OpenCart option/value joins on the warehouse product API route. Parent product can still import; variants need option_id and option_value_id.'
                );
            }
        }

        return null;
    }

    /**
     * @return array{code:string,title:string,detail:string,fix:string}
     */
    private function issue(string $code, string $title, string $detail, string $fix): array
    {
        return [
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
            'fix' => $fix,
        ];
    }
}
