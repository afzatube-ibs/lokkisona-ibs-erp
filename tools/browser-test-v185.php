<?php

declare(strict_types=1);

/**
 * v1.8.5 Supplier Product Control Completion — browser integration test.
 * Uses config/opencart.local.php when present; demo mode runs full supplier-skip assertions.
 */
$base = 'http://localhost:8080';
$cookie = sys_get_temp_dir() . '/ibs_v185_browser_test.cookies';
@unlink($cookie);

require dirname(__DIR__) . '/app/bootstrap.php';

function req(string $method, string $url, ?string $body = null, string $cookieFile = '', bool $follow = true): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $body !== null ? ['Content-Type: application/x-www-form-urlencoded'] : [],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 45,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => (string) $raw];
}

function bodyOnly(array $resp): string
{
    $pos = strpos($resp['body'], "\r\n\r\n");

    return $pos === false ? $resp['body'] : substr($resp['body'], $pos + 4);
}

function csrf(string $html): string
{
    if (preg_match('/name="_csrf"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }

    throw new RuntimeException('CSRF token not found');
}

function assertContains(string $haystack, string $needle, string $label): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException('FAIL: ' . $label . ' — expected to contain: ' . $needle);
    }
    echo 'OK: ' . $label . PHP_EOL;
}

function assertNotContains(string $haystack, string $needle, string $label): void
{
    if (str_contains($haystack, $needle)) {
        throw new RuntimeException('FAIL: ' . $label . ' — must not contain: ' . $needle);
    }
    echo 'OK: ' . $label . PHP_EOL;
}

$sourceMode = strtolower((string) config('opencart.source_mode', 'demo'));
$productRoute = trim((string) config('opencart.product_api_route', ''));
$appVersion = (string) config('app.version', '');

echo "Browser test v1.8.5 — {$base}\n";
echo "Source mode: {$sourceMode}, product route: {$productRoute}\n";

$login = req('POST', $base . '/login', http_build_query([
    'username' => 'admin',
    'password' => 'admin',
]), $cookie, false);
if ($login['code'] !== 302) {
    throw new RuntimeException('Login failed HTTP ' . $login['code']);
}
echo "OK: login\n";

$settings = req('GET', $base . '/sync-api-settings', null, $cookie);
$settingsHtml = bodyOnly($settings);
assertContains($settingsHtml, 'Sync/API Settings', 'sync-api-settings page loads');
assertContains($settingsHtml, 'Reset Product Sync Data', 'sync-api-settings reset button');
$token = csrf($settingsHtml);

req('POST', $base . '/sync-api-settings/test-connection', http_build_query([
    '_csrf' => $token,
]), $cookie);
$afterTest = req('GET', $base . '/sync-api-settings', null, $cookie);
$afterTestHtml = bodyOnly($afterTest);
assertContains($afterTestHtml, 'Connection test OK', 'sync-api-settings test connection flash');

$previewPage = req('GET', $base . '/sync-preview', null, $cookie);
$previewHtml = bodyOnly($previewPage);
assertContains($previewHtml, 'Reset Product Sync Data', 'sync-preview reset button');
$previewToken = csrf($previewHtml);

req('POST', $base . '/sync-preview/preview-products', http_build_query([
    '_csrf' => $previewToken,
    'page' => '1',
    'business_source_id' => (string) config('opencart.business_source_id', 1),
]), $cookie);
$afterPreview = req('GET', $base . '/sync-preview?product_page=1', null, $cookie);
$afterPreviewHtml = bodyOnly($afterPreview);

assertContains($afterPreviewHtml, 'supplier product', 'preview flash mentions supplier products');
assertNotContains($afterPreviewHtml, 'Demo Shop-Only Item', 'preview excludes from_warehouse=0 demo shop product name');
assertNotContains($afterPreviewHtml, 'OC-SHOP-502', 'preview excludes from_warehouse=0 demo SKU');

if ($sourceMode === 'demo') {
    assertContains($afterPreviewHtml, 'non-supplier product', 'preview skip message for demo non-supplier row');
    assertContains($afterPreviewHtml, 'Demo Warehouse Stroller', 'demo supplier product in preview table');
}

$importToken = csrf($afterPreviewHtml);
req('POST', $base . '/sync-preview/import-products', http_build_query([
    '_csrf' => $importToken,
    'page' => '1',
    'import_confirmation' => '1',
]), $cookie);
$afterImport = req('GET', $base . '/sync-preview?product_page=1', null, $cookie);
$afterImportHtml = bodyOnly($afterImport);
assertContains($afterImportHtml, 'Products imported:', 'import summary shows imported count');
assertContains($afterImportHtml, 'Skipped:', 'import summary shows skipped count');

$pcBeforeReset = req('GET', $base . '/product-control', null, $cookie);
$pcBeforeHtml = bodyOnly($pcBeforeReset);
if ($sourceMode === 'demo') {
    assertContains($pcBeforeHtml, 'OC-STROLLER-501', 'product control has synced demo supplier SKU before reset');
}

$resetToken = csrf($afterImportHtml);
req('POST', $base . '/sync-preview/reset-product-sync', http_build_query([
    '_csrf' => $resetToken,
    'reset_confirmation' => '1',
    'redirect_to' => '/sync-preview',
]), $cookie);
$afterReset = req('GET', $base . '/sync-preview', null, $cookie);
$afterResetHtml = bodyOnly($afterReset);
assertContains($afterResetHtml, 'Product sync data reset from ERP', 'reset success message');
assertContains($afterResetHtml, 'Load product preview', 'sync preview cleared after reset');
assertNotContains($afterResetHtml, 'Demo Warehouse Stroller', 'old demo preview table cleared after reset');

$pcAfterReset = req('GET', $base . '/product-control', null, $cookie);
$pcAfterHtml = bodyOnly($pcAfterReset);
assertNotContains($pcAfterHtml, 'OC-STROLLER-501', 'synced demo SKU removed from product control after reset');
assertNotContains($pcAfterHtml, 'Demo Shop-Only Item', 'non-supplier demo name absent after reset');
assertNotContains($pcAfterHtml, 'OC-SHOP-502', 'non-supplier demo SKU absent after reset');

$ordersPage = req('GET', $base . '/order-workflow', null, $cookie);
$ordersHtml = bodyOnly($ordersPage);
assertContains($ordersHtml, 'Order Workflow', 'order workflow unchanged after product reset');

$previewToken2 = csrf($afterResetHtml);
req('POST', $base . '/sync-preview/preview-products', http_build_query([
    '_csrf' => $previewToken2,
    'page' => '1',
    'business_source_id' => (string) config('opencart.business_source_id', 1),
]), $cookie);
$afterPreview2 = req('GET', $base . '/sync-preview?product_page=1', null, $cookie);
$afterPreview2Html = bodyOnly($afterPreview2);
assertContains($afterPreview2Html, 'supplier product', 'preview reload after reset');
assertNotContains($afterPreview2Html, 'Demo Shop-Only Item', 'reloaded preview excludes non-supplier product');

$importToken2 = csrf($afterPreview2Html);
req('POST', $base . '/sync-preview/import-products', http_build_query([
    '_csrf' => $importToken2,
    'page' => '1',
    'import_confirmation' => '1',
]), $cookie);
$afterImport2 = req('GET', $base . '/sync-preview?product_page=1', null, $cookie);
$afterImport2Html = bodyOnly($afterImport2);
assertContains($afterImport2Html, 'Products imported:', 'import works after reset');

$pc = req('GET', $base . '/product-control', null, $cookie);
$pcHtml = bodyOnly($pc);
assertContains($pcHtml, 'IBS-LK Product Control', 'product control page title');
assertContains($pcHtml, 'Supplier synced only', 'supplier synced only badge');
assertContains($pcHtml, 'Inventory Products', 'inventory products table');
assertContains($pcHtml, '20 rows per page', 'pagination hint');
assertContains($pcHtml, 'Synced Today', 'synced today filter chip');
assertContains($pcHtml, 'Missing Cost', 'missing cost filter chip');
assertContains($pcHtml, 'Missing Model', 'missing model filter chip');
assertContains($pcHtml, 'Needs Work', 'needs work filter chip');
assertContains($pcHtml, 'productControlCenterModal', 'workspace modal');
assertContains($pcHtml, 'product-control.js', 'product control js');
assertNotContains($pcHtml, 'Demo Shop-Only Item', 'product control excludes non-supplier demo name');
assertNotContains($pcHtml, 'OC-SHOP-502', 'product control excludes non-supplier demo SKU');

if (str_contains($pcHtml, 'product-catalog-row')) {
    echo "OK: synced product rows present after re-import\n";
    if (preg_match('/variant-count|Variants<\/span>|option lines/i', $pcHtml)) {
        echo "OK: variant count or KPI renders\n";
    }
} else {
    echo "OK: product control empty catalog state (no rows yet)\n";
}

assertContains($pcHtml, 'v' . $appVersion, 'version footer shows v1.8.5');

$chipPage = req('GET', $base . '/product-control?chip=variable', null, $cookie);
$chipHtml = bodyOnly($chipPage);
assertContains($chipHtml, 'chip=variable', 'variable filter active');

$simplePage = req('GET', $base . '/product-control?chip=simple', null, $cookie);
$simpleHtml = bodyOnly($simplePage);
assertContains($simpleHtml, 'chip=simple', 'simple filter active');

$modalHtml = $pcHtml;
if (preg_match('/data-product-id="(\d+)"/', $modalHtml, $m)) {
    $productId = (int) $m[1];
    $token = csrf($modalHtml);
    req('POST', $base . '/product-control/workspace/save', http_build_query([
        '_csrf' => $token,
        'product_id' => (string) $productId,
        'supplier_model' => 'V185-TEST-MODEL',
        'product_cost' => '99.50',
        'vendor_stock' => '12',
        'low_warning_threshold' => '3',
        'status' => 'active',
        'variants' => '[]',
    ]), $cookie);
    $afterSave = req('GET', $base . '/product-control', null, $cookie);
    $afterHtml = bodyOnly($afterSave);
    assertContains($afterHtml, 'V185-TEST-MODEL', 'workspace save supplier model persisted');
    echo "OK: product workspace save\n";
} else {
    echo "SKIP: workspace save (no product rows)\n";
}

echo "\nALL v1.8.5 BROWSER TESTS PASSED\n";
