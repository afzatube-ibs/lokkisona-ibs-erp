<?php

declare(strict_types=1);

/**
 * v1.9.3 Vendor Fulfillment IBS-LK Parity — route and UI smoke test.
 */
$base = getenv('IBS_TEST_BASE') ?: 'http://127.0.0.1:8010';
$cookie = sys_get_temp_dir() . '/ibs_v193_browser_test.cookies';
@unlink($cookie);

require dirname(__DIR__) . '/app/bootstrap.php';

function req193(string $method, string $url, ?string $body = null, string $cookieFile = '', bool $follow = true): array
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

function bodyOnly193(array $resp): string
{
    $pos = strpos($resp['body'], "\r\n\r\n");

    return $pos === false ? $resp['body'] : substr($resp['body'], $pos + 4);
}

function assertContains193(string $haystack, string $needle, string $label): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException('FAIL: ' . $label . ' — expected: ' . $needle);
    }
    echo 'OK: ' . $label . PHP_EOL;
}

function assertNotContains193(string $haystack, string $needle, string $label): void
{
    if (str_contains($haystack, $needle)) {
        throw new RuntimeException('FAIL: ' . $label . ' — must not contain: ' . $needle);
    }
    echo 'OK: ' . $label . PHP_EOL;
}

$version = (string) config('app.version', '');
echo "Browser test v1.9.3 — {$base} (app {$version})\n";

$login = req193('POST', $base . '/login', http_build_query([
    'username' => 'admin',
    'password' => 'admin',
]), $cookie, false);
if ($login['code'] !== 302) {
    throw new RuntimeException('Login failed HTTP ' . $login['code']);
}
echo "OK: login\n";

$vf = req193('GET', $base . '/order-workflow', null, $cookie);
$html = bodyOnly193($vf);
assertContains193($html, 'Vendor Fulfillment', 'page title');
assertContains193($html, 'v1.9.3', 'version in subtitle');
assertContains193($html, 'id="vfToolbar"', 'unified toolbar');
assertContains193($html, 'id="vfBulkForwardBtn"', 'single bulk forward button');
assertContains193($html, 'id="vfPackModal"', 'pack modal');
assertContains193($html, 'id="vfDispatchModal"', 'dispatch modal');
assertContains193($html, 'id="vfHubReturnModal"', 'hub return modal');
assertContains193($html, 'id="vfSuccessToast"', 'success toast');
assertNotContains193($html, 'data-bulk-action="bulk_receive"', 'no inline bulk_receive button in table bar');
assertNotContains193($html, 'Bulk Receive Order</button>', 'no always-visible bulk receive in table');
assertContains193($html, 'Test Sync', 'owner test sync link');
assertContains193($html, 'Fulfillment stages', 'compact stage cards header');

$history = req193('GET', $base . '/order-workflow/history?id=1', null, $cookie);
$historyBody = bodyOnly193($history);
if (!str_contains($historyBody, '"rows"')) {
    throw new RuntimeException('FAIL: history JSON endpoint');
}
echo "OK: history JSON endpoint\n";

$preview = req193('GET', $base . '/order-workflow/selection-preview?ids=1,2', null, $cookie);
$previewBody = bodyOnly193($preview);
assertContains193($previewBody, '"order_count"', 'selection preview JSON');

$invoiceBatch = req193('GET', $base . '/invoice-printing?order_ids=1,2', null, $cookie);
$invoiceHtml = bodyOnly193($invoiceBatch);
assertContains193($invoiceHtml, 'Batch Packing', 'invoice batch view when order_ids set');

$dispatch = req193('GET', $base . '/dispatch-reports', null, $cookie);
$dispatchHtml = bodyOnly193($dispatch);
assertContains193($dispatchHtml, 'Total Vendor Cost', 'dispatch list cost column label');
assertContains193($dispatchHtml, 'Vendor', 'dispatch list vendor column');

echo "ALL OK: v1.9.3 vendor fulfillment smoke passed\n";
