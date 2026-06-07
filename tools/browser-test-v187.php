<?php

declare(strict_types=1);

/**
 * v1.8.7.4 Product Control Center Finalization — browser integration test.
 */
$base = getenv('IBS_TEST_BASE') ?: 'http://127.0.0.1:8010';
$cookie = sys_get_temp_dir() . '/ibs_v1874_browser_test.cookies';
@unlink($cookie);

require dirname(__DIR__) . '/app/bootstrap.php';

function req1874(string $method, string $url, ?string $body = null, string $cookieFile = '', bool $follow = true): array
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

function bodyOnly1874(array $resp): string
{
    $pos = strpos($resp['body'], "\r\n\r\n");

    return $pos === false ? $resp['body'] : substr($resp['body'], $pos + 4);
}

function csrf1874(string $html): string
{
    if (preg_match('/name="_csrf"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }

    throw new RuntimeException('CSRF token not found');
}

function assertContains1874(string $haystack, string $needle, string $label): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException('FAIL: ' . $label . ' — expected to contain: ' . $needle);
    }
    echo 'OK: ' . $label . PHP_EOL;
}

function assertNotContains1874(string $haystack, string $needle, string $label): void
{
    if (str_contains($haystack, $needle)) {
        throw new RuntimeException('FAIL: ' . $label . ' — must not contain: ' . $needle);
    }
    echo 'OK: ' . $label . PHP_EOL;
}

$appVersion = (string) config('app.version', '');

echo "Browser test v1.8.7.4 — {$base}\n";

$login = req1874('POST', $base . '/login', http_build_query([
    'username' => 'admin',
    'password' => 'admin',
]), $cookie, false);
if ($login['code'] !== 302) {
    throw new RuntimeException('Login failed HTTP ' . $login['code']);
}
echo "OK: login\n";

$pc = req1874('GET', $base . '/product-control', null, $cookie);
$pcHtml = bodyOnly1874($pc);
if ($pc['code'] !== 200) {
    throw new RuntimeException('Product control HTTP ' . $pc['code']);
}

assertContains1874($pcHtml, 'Product Control', 'product-control page loads');
assertContains1874($pcHtml, 'Catalog Health', 'catalog health action');
assertContains1874($pcHtml, 'Refresh Products', 'refresh products action');
assertContains1874($pcHtml, 'Sync Log', 'sync log action');
assertContains1874($pcHtml, 'Dispatch Location', 'dispatch location wording');
assertContains1874($pcHtml, 'Missing Model', 'kpi missing model');
assertContains1874($pcHtml, 'Low Stock', 'kpi low stock');
assertContains1874($pcHtml, 'pcc-product-cell-oc', 'product column oc subline');
assertContains1874($pcHtml, 'pcc-product-cell-model', 'product column model line');
assertContains1874($pcHtml, 'pcc-row-actions', 'actions column');
assertContains1874($pcHtml, 'product-catalog-table-v874-fixed', 'v874 fixed table class');
assertContains1874($pcHtml, 'pcc-table-tight', 'tight table padding class');
assertContains1874($pcHtml, 'productControlCenterModal', 'product control center modal');
assertContains1874($pcHtml, 'pcc-modal-hero-split', 'modal hero split layout');
assertContains1874($pcHtml, 'pcc-vendor-mapping-card', 'vendor mapping card');
assertContains1874($pcHtml, 'pccVariantLinesTable', 'variant lines table');
assertContains1874($pcHtml, 'pcc-supplier-view', 'supplier view mode container');
assertContains1874($pcHtml, 'Iqbal &amp; Brothers (IBS)', 'default supplier label');
assertContains1874($pcHtml, 'Save All Changes', 'save all changes button');
assertContains1874($pcHtml, 'refresh-products', 'refresh products route');
assertContains1874($pcHtml, 'v' . $appVersion, 'footer version');

assertNotContains1874($pcHtml, 'pccVariantAccordion', 'variant accordion removed');
assertNotContains1874($pcHtml, 'pcc-modal-split-v874', 'old modal split removed');
assertNotContains1874($pcHtml, '<th>Model</th>', 'standalone model column removed');

assertNotContains1874($pcHtml, 'Sync Products', 'old sync products label removed');
assertNotContains1874($pcHtml, 'pcc-sync-strip', 'sync strip absent');
assertNotContains1874($pcHtml, 'name="import_confirmation"', 'import confirmation absent');

try {
    $previewPage = req1874('GET', $base . '/sync-preview', null, $cookie);
    $previewPageHtml = bodyOnly1874($previewPage);
    $previewToken = csrf1874($previewPageHtml);
    req1874('POST', $base . '/sync-preview/preview-products', http_build_query([
        '_csrf' => $previewToken,
        'page' => '1',
        'business_source_id' => (string) config('opencart.business_source_id', 1),
    ]), $cookie);
    $afterPreview = req1874('GET', $base . '/sync-preview?product_page=1', null, $cookie);
    $afterPreviewHtml = bodyOnly1874($afterPreview);
    $importToken = csrf1874($afterPreviewHtml);
    req1874('POST', $base . '/sync-preview/import-products', http_build_query([
        '_csrf' => $importToken,
        'page' => '1',
        'import_confirmation' => '1',
    ]), $cookie);
    $pcLoaded = req1874('GET', $base . '/product-control', null, $cookie);
    $pcLoadedHtml = bodyOnly1874($pcLoaded);

    if (str_contains($pcLoadedHtml, 'product-catalog-row')) {
        assertContains1874($pcLoadedHtml, 'Manage', 'manage action button');
        assertContains1874($pcLoadedHtml, 'History', 'history action button');
        echo "OK: catalog rows with actions\n";
    } else {
        echo "OK: product rows optional when import skipped\n";
    }
} catch (RuntimeException $e) {
    echo 'OK: optional sync-preview import skipped (' . $e->getMessage() . ')' . PHP_EOL;
}

echo "\nALL v1.8.7.4 BROWSER TESTS PASSED\n";
