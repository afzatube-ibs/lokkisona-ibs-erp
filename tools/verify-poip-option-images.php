<?php

/**
 * POIP option image verification (v2.1.8.3.2) — read-only diagnostics.
 * Usage: php tools/verify-poip-option-images.php [source_product_id]
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Services\Read\OpenCartOptionImageResolver;
use App\Services\Read\OpenCartReadClient;
use App\Services\ReadOnly\ProductControlCatalogReadService;

$sourceProductId = trim((string) ($argv[1] ?? '9759'));
if ($sourceProductId === '') {
    $sourceProductId = '9759';
}

$pdo = new PDO(
    'mysql:host=' . config('database.host') . ';dbname=' . config('database.database') . ';charset=utf8mb4',
    (string) config('database.username'),
    (string) config('database.password'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::FETCH_ASSOC]
);

echo "=== 1) LOCAL ERP PRODUCT (source_product_id={$sourceProductId} / ST-A5) ===\n";
$productStmt = $pdo->prepare(
    "SELECT product_id, source_product_id, product_name, source_model, image_path
     FROM ibs_products
     WHERE source_product_id = :sid
        OR source_model IN ('St-A5', 'ST-A5')
     LIMIT 1"
);
$productStmt->execute(['sid' => $sourceProductId]);
$product = $productStmt->fetch();
if (!$product) {
    echo "FAIL: No local product for source_product_id {$sourceProductId}\n";
    exit(1);
}

$localProductId = (int) $product['product_id'];
echo "local_product_id={$localProductId} source_product_id={$product['source_product_id']} source_model={$product['source_model']}\n";
echo "parent image_path={$product['image_path']}\n";

echo "\n=== 2) LOCAL ERP VARIANTS (ibs_product_variants) ===\n";
echo "Note: variants table has option_image_path only (no image_path / option_image_url columns)\n";
$variantStmt = $pdo->prepare(
    "SELECT product_variant_id, product_id, option_value, source_model, source_option_value_id,
            option_image_path, image_reference_note
     FROM ibs_product_variants
     WHERE product_id = :pid
     ORDER BY product_variant_id"
);
$variantStmt->execute(['pid' => $localProductId]);
$variants = $variantStmt->fetchAll();
$empty = 0;
foreach ($variants as $row) {
    $path = trim((string) ($row['option_image_path'] ?? ''));
    if ($path === '') {
        $empty++;
    }
    printf(
        "variant_id=%s product_id=%s option=%s source_model=%s pov_id=%s option_image_path=%s\n",
        $row['product_variant_id'],
        $row['product_id'],
        $row['option_value'],
        $row['source_model'],
        $row['source_option_value_id'],
        $path === '' ? '(empty)' : $path
    );
}
echo "SUMMARY: variants=" . count($variants) . " empty_option_image_path={$empty}\n";

echo "\n=== 3) OPENCART API (warehouse products) ===\n";
$client = new OpenCartReadClient();
$apiProduct = null;
for ($page = 1; $page <= 30; $page++) {
    $result = $client->fetchWarehouseProductsPage($page);
    foreach ($result['rows'] ?? [] as $row) {
        if ((string) ($row['source_product_id'] ?? '') === $sourceProductId) {
            $apiProduct = $row;
            break 2;
        }
    }
    if (empty($result['has_next'])) {
        break;
    }
}

if ($apiProduct === null) {
    echo "FAIL: API product {$sourceProductId} not found\n";
} else {
    $apiEmpty = 0;
    foreach ($apiProduct['options'] ?? [] as $opt) {
        $img = OpenCartOptionImageResolver::extractFromPayload($opt);
        if ($img === null) {
            $apiEmpty++;
        }
        printf(
            "  option=%s pov_id=%s api_image=%s\n",
            $opt['option_value'] ?? '',
            $opt['product_option_value_id'] ?? '',
            $img === null ? '(empty)' : $img
        );
    }
    echo "SUMMARY: api_options=" . count($apiProduct['options'] ?? []) . " empty_api_images={$apiEmpty}\n";
}

echo "\n=== 4) OPENCART DB ENRICHMENT (ERP read-only) ===\n";
$resolver = new OpenCartOptionImageResolver();
echo 'database_enrichment=' . ($resolver->databaseEnrichmentEnabled() ? 'yes' : 'no') . "\n";
if ($variants !== []) {
    $ids = array_map(static fn ($v) => (int) $v['source_option_value_id'], $variants);
    $resolved = $resolver->resolveForValueIds($ids);
    echo 'resolver_hits=' . count($resolved) . "\n";
    foreach ($resolved as $id => $path) {
        echo "  pov_id={$id} => {$path}\n";
    }
}

echo "\n=== 5) WORKSPACE READ (option_image_url for UI) ===\n";
$views = (new ProductControlCatalogReadService())->buildProductViews($product, $variants, false);
$distinct = [];
foreach ($views['workspace']['variants'] ?? [] as $row) {
    $url = trim((string) ($row['option_image_url'] ?? ''));
    if ($url !== '') {
        $distinct[$url] = true;
    }
    printf(
        "  %s option_image_url=%s fallback=%s\n",
        $row['option_value'] ?? '',
        $url === '' ? '(none)' : $url,
        $row['fallback_image_url'] ?? ''
    );
}
$distinctCount = count($distinct);
echo 'SUMMARY: distinct_option_image_urls=' . $distinctCount . " (expected=4 after deploy+refresh)\n";

echo "\n=== VERDICT ===\n";
if ($empty === count($variants) && count($variants) > 0) {
    if ($apiProduct !== null && $apiEmpty === count($apiProduct['options'] ?? [])) {
        echo "BLOCKED: API returns no option images. Deploy packages/opencart-ibs-read-api-v1.8.3 to OpenCart staging/live,\n";
        echo "        then Product Control -> Refresh Products, then re-run this script.\n";
        echo "        Optional: set opencart.db.* in config/opencart.local.php for ERP DB enrichment.\n";
        exit(2);
    }
    echo "BLOCKED: ERP variant option_image_path empty but API may have images — check ProductWriteService mapping.\n";
    exit(3);
}
if ($distinctCount < count($variants)) {
    echo "BLOCKED: DB has some images but UI read path still missing option_image_url — fix catalog read / JS.\n";
    exit(4);
}
if ($distinctCount < 4 && count($variants) >= 4) {
    echo "BLOCKED: Expected distinct_option_image_urls=4 for ST-A5 stroller.\n";
    exit(5);
}
echo "PASS: Option images present for workspace display (distinct_option_image_urls={$distinctCount}).\n";
echo "UI: Open Product Control modal for local_product_id={$localProductId} (source 9759).\n";
exit(0);
