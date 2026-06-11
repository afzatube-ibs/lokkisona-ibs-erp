<?php

require __DIR__ . '/../app/bootstrap.php';

$baseUrl = rtrim((string) config('opencart.api_base_url', ''), '/');
$apiKey = (string) config('opencart.api_key', '');
$route = trim((string) config('opencart.connection_test_api_route', 'api/ibs/connection_test'));
$url = $baseUrl . '/index.php?route=' . ltrim($route, '=') . '&api_token=' . rawurlencode($apiKey);

$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
$response = curl_exec($ch);
curl_close($ch);

$decoded = json_decode((string) $response, true);
if (!is_array($decoded)) {
    echo "connection_test: invalid response\n";
    exit(1);
}

$payload = $decoded;
foreach (['data', 'response'] as $wrap) {
    if (isset($decoded[$wrap]) && is_array($decoded[$wrap])) {
        $payload = $decoded[$wrap];
    }
}

$version = (string) ($payload['connector_version'] ?? $payload['version'] ?? '');
echo 'version=' . ($version !== '' ? $version : 'unknown') . "\n";
echo 'connector_version=' . ($payload['connector_version'] ?? '(none)') . "\n";
echo 'message=' . ($payload['message'] ?? '') . "\n";
echo 'bridge_available=' . json_encode($payload['bridge_available'] ?? null) . "\n";
$probe = $payload['option_image_probe'] ?? null;
if (is_array($probe)) {
    echo "option_image_probe:\n" . json_encode($probe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "option_image_probe: (not present — install packages/opencart-ibs-sync-connector zip on OpenCart)\n";
}

echo "\n=== PROBE CHECKS ===\n";
$versionOk = in_array($version, ['1.0.0', '1.8.3.2'], true);
$probeOk = is_array($probe);
$sampleOk = $probeOk && (int) ($probe['sample_images_non_empty'] ?? 0) > 0;
echo 'connector_version_ok=' . ($versionOk ? 'PASS' : 'FAIL') . " (expected 1.0.0 or 1.8.3.2)\n";
echo 'option_image_probe_present=' . ($probeOk ? 'PASS' : 'FAIL') . "\n";
echo 'sample_images_non_empty=' . ($sampleOk ? 'PASS' : 'FAIL')
    . ' (count=' . (int) ($probe['sample_images_non_empty'] ?? 0) . ")\n";

if (!$versionOk || !$probeOk || !$sampleOk) {
    echo "\nSee packages/opencart-ibs-sync-connector/README-INSTALL.md\n";
    exit(2);
}
echo "\nAPI probe OK — run Refresh Products, then: php tools/verify-poip-option-images.php 9759\n";
