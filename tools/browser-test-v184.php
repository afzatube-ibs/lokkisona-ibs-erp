<?php

declare(strict_types=1);

/**
 * v1.8.4 staging integration browser test — uses existing config/opencart.local.php when present.
 * Does not modify or delete local OpenCart config.
 */
$base = 'http://localhost:8080';
$cookie = sys_get_temp_dir() . '/ibs_v184_browser_test.cookies';
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
        CURLOPT_TIMEOUT => 30,
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

echo "Browser test v1.8.4 — {$base}\n";
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
$token = csrf($settingsHtml);

req('POST', $base . '/sync-api-settings/test-connection', http_build_query([
    '_csrf' => $token,
]), $cookie);
$afterTest = req('GET', $base . '/sync-api-settings', null, $cookie);
$afterTestHtml = bodyOnly($afterTest);
assertContains($afterTestHtml, 'Connection test OK', 'sync-api-settings test connection flash');

if ($sourceMode === 'staging' || $sourceMode === 'live') {
    assertContains($productRoute, 'api/ibs/products', 'staging product route configured');
}

$previewPage = req('GET', $base . '/sync-preview', null, $cookie);
$previewHtml = bodyOnly($previewPage);
$previewToken = csrf($previewHtml);

req('POST', $base . '/sync-preview/preview-products', http_build_query([
    '_csrf' => $previewToken,
    'page' => '1',
    'business_source_id' => (string) config('opencart.business_source_id', 1),
]), $cookie);
$afterPreview = req('GET', $base . '/sync-preview?product_page=1', null, $cookie);
$afterPreviewHtml = bodyOnly($afterPreview);

if ($sourceMode === 'demo') {
    assertContains($afterPreviewHtml, 'Demo Warehouse', 'demo product preview row');
} else {
    assertNotContains($afterPreviewHtml, 'Demo Warehouse Stroller', 'staging preview excludes demo stroller name');
    assertNotContains($afterPreviewHtml, 'OC-STROLLER-501', 'staging preview excludes demo SKU');
}

assertContains($afterPreviewHtml, 'Product preview loaded', 'product preview success message');

$importToken = csrf($afterPreviewHtml);
req('POST', $base . '/sync-preview/import-products', http_build_query([
    '_csrf' => $importToken,
    'page' => '1',
    'import_confirmation' => '1',
]), $cookie);
$afterImport = req('GET', $base . '/sync-preview?product_page=1', null, $cookie);
$afterImportHtml = bodyOnly($afterImport);

if ($sourceMode !== 'demo') {
    assertContains($afterImportHtml, 'Products imported:', 'staging product import summary');
}

$productControl = req('GET', $base . '/product-control', null, $cookie);
$pcHtml = bodyOnly($productControl);
assertContains($pcHtml, 'Product Control', 'product control page loads');
if ($sourceMode === 'demo') {
    assertContains($pcHtml, 'OC-STROLLER-501', 'product control shows demo synced product');
}

echo "\nALL v1.8.4 BROWSER TESTS PASSED\n";
