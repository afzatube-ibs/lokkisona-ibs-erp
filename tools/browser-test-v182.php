<?php

declare(strict_types=1);

$base = 'http://localhost:8080';
$cookie = sys_get_temp_dir() . '/ibs_v182_browser_test.cookies';
@unlink($cookie);

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

echo "Browser test v1.8.2.2 — {$base}\n";

$login = req('POST', $base . '/login', http_build_query([
    'username' => 'admin',
    'password' => 'admin',
]), $cookie, false);
if ($login['code'] !== 302) {
    throw new RuntimeException('Login failed HTTP ' . $login['code'] . ' body: ' . substr(bodyOnly($login), 0, 300));
}
$dash = req('GET', $base . '/dashboard', null, $cookie);
if ($dash['code'] >= 400) {
    throw new RuntimeException('Dashboard failed HTTP ' . $dash['code'] . ' after login');
}
echo "OK: login\n";

$settingsGet = req('GET', $base . '/sync-api-settings', null, $cookie);
$settingsHtml = bodyOnly($settingsGet);
assertContains($settingsHtml, 'Sync/API Settings', 'settings page title');
assertContains($settingsHtml, 'Connect Lokkisona product/order sync in read-only mode.', 'settings subtitle');
assertContains($settingsHtml, 'Read-only OpenCart sync only', 'ops safety strip');
assertContains($settingsHtml, 'Open Sync Preview', 'header action sync preview');
assertContains($settingsHtml, 'Product Control', 'header action product control');
assertContains($settingsHtml, 'sync-settings-kpi-row', 'kpi status row');
assertContains($settingsHtml, 'sync-settings-layout', 'two column layout');
assertContains($settingsHtml, 'Demo Mode', 'header demo badge');
assertContains($settingsHtml, 'Read-only sync', 'workflow chip');
assertContains($settingsHtml, 'form-input', 'form input styling');
assertContains($settingsHtml, 'Read-Only Lock', 'read-only lock label');
assertContains($settingsHtml, 'Test Connection', 'test connection button');
assertContains($settingsHtml, 'read-only — no product or order import', 'test connection help');
assertContains($settingsHtml, 'Not configured', 'api key not configured initially');
if (str_contains($settingsHtml, 'type="password"') && preg_match('/type="password"[^>]*value="[^"]+"/', $settingsHtml)) {
    throw new RuntimeException('FAIL: API key password field must not contain a saved value');
}
echo "OK: api key field empty (no saved value in HTML)\n";

$saveToken = csrf($settingsHtml);
$save = req('POST', $base . '/sync-api-settings/save', http_build_query([
    '_csrf' => $saveToken,
    'source_mode' => 'demo',
    'api_base_url' => '',
    'api_key' => '',
    'product_api_route' => 'demo/warehouse_product',
    'order_api_route' => 'api/order',
    'product_sync_enabled' => '1',
    'order_sync_enabled' => '1',
    'dispatch_bridge_required' => '1',
]), $cookie);
$afterSave = req('GET', $base . '/sync-api-settings', null, $cookie);
$afterSaveHtml = bodyOnly($afterSave);
assertContains($afterSaveHtml, 'Demo', 'demo mode saved');

$testToken = csrf($afterSaveHtml);
$test = req('POST', $base . '/sync-api-settings/test-connection', http_build_query([
    '_csrf' => $testToken,
]), $cookie);
$afterTest = req('GET', $base . '/sync-api-settings', null, $cookie);
$afterTestHtml = bodyOnly($afterTest);
assertContains($afterTestHtml, 'Connection test OK', 'test connection success flash');

$preview = req('GET', $base . '/sync-preview', null, $cookie);
$previewHtml = bodyOnly($preview);
assertContains($previewHtml, 'Product Sync Help', 'sync preview diagnostics block');
assertContains($previewHtml, 'Status', 'sync preview diagnostics status column');
assertContains($previewHtml, 'Fix', 'sync preview diagnostics fix column');
assertContains($previewHtml, 'Sync/API Settings', 'sync preview settings link');

// Save staging with fake key — verify Configured badge, key not in HTML
$stagingToken = csrf($afterTestHtml);
req('POST', $base . '/sync-api-settings/save', http_build_query([
    '_csrf' => $stagingToken,
    'source_mode' => 'staging',
    'api_base_url' => 'https://www.staging.lokkisona.com',
    'api_key' => 'TEST_KEY_DO_NOT_COMMIT',
    'product_api_route' => 'demo/warehouse_product',
    'order_api_route' => 'api/order',
    'product_sync_enabled' => '1',
    'order_sync_enabled' => '1',
    'dispatch_bridge_required' => '1',
]), $cookie);
$stagingPage = req('GET', $base . '/sync-api-settings', null, $cookie);
$stagingHtml = bodyOnly($stagingPage);
assertContains($stagingHtml, 'Configured', 'api key status configured');
if (str_contains($stagingHtml, 'TEST_KEY_DO_NOT_COMMIT')) {
    throw new RuntimeException('FAIL: API key leaked into HTML response');
}
echo "OK: api key not leaked in HTML\n";

// Verify local file exists and is gitignored path
$localPath = dirname(__DIR__) . '/config/opencart.local.php';
if (!file_exists($localPath)) {
    throw new RuntimeException('FAIL: opencart.local.php was not created');
}
$localContents = file_get_contents($localPath);
if (!str_contains($localContents, 'TEST_KEY_DO_NOT_COMMIT')) {
    throw new RuntimeException('FAIL: key not saved to local file');
}
echo "OK: settings written to config/opencart.local.php only\n";

// Reset to demo without key in repo — delete test local file after test
@unlink($localPath);
echo "OK: cleaned test local config file\n";

echo "\nALL BROWSER TESTS PASSED\n";
