param()

$ErrorActionPreference = "Stop"
$root = Resolve-Path (Join-Path $PSScriptRoot "..")

function Ok($message) { Write-Host "[OK] $message" }
function Warn($message) { Write-Host "[WARN] $message" }

Write-Host "IBS-LK Staging Product Sync - readiness check"
Write-Host "Repo: $root"
Write-Host ""

$requiredMigrations = @(
    "0003_business_sources_suppliers_products.sql",
    "0004_status_mapping_sync_preview.sql",
    "0011_supplier_product_category.sql"
)

foreach ($file in $requiredMigrations) {
    $path = Join-Path $root "database/migrations/$file"
    if (-not (Test-Path $path)) {
        throw "Missing migration: database/migrations/$file"
    }
    Ok "Migration present: $file"
}

$requiredRoutes = @(
    "app/Services/Read/OpenCartReadClient.php",
    "app/Services/Write/SyncPreviewWriteService.php",
    "app/Services/Write/SyncImportWriteService.php",
    "app/Controllers/SyncPreviewController.php",
    "app/Controllers/ProductControlController.php",
    "resources/views/sync-preview/index.php",
    "resources/views/product-control/index.php"
)

foreach ($rel in $requiredRoutes) {
    $path = Join-Path $root $rel
    if (-not (Test-Path $path)) {
        throw "Missing sync file: $rel"
    }
    Ok "Sync file present: $rel"
}

$examples = @(
    "config/opencart.staging.example.php",
    "config/app.staging.example.php",
    "config/database.staging.example.php"
)

foreach ($rel in $examples) {
    $path = Join-Path $root $rel
    if (-not (Test-Path $path)) {
        throw "Missing staging example: $rel"
    }
    Ok "Staging example present: $rel"
}

$handoff = Join-Path $root "docs/STAGING-PRODUCT-SYNC.md"
if (-not (Test-Path $handoff)) {
    throw "Missing docs/STAGING-PRODUCT-SYNC.md"
}
Ok "Runbook present: docs/STAGING-PRODUCT-SYNC.md"

try {
    $hash = git -C $root rev-parse --short HEAD 2>$null
    if ($hash) { Ok "Deploy commit: $hash" }
} catch {
    Warn "Could not read git commit hash"
}

Write-Host ""
Write-Host "Next steps on ERP staging server:"
Write-Host "  1. FTP: tools/staging-ftp-package.ps1 -Zip  (see docs/STAGING-FTP-DEPLOY.md)"
Write-Host "     Git: git pull --ff-only origin main"
Write-Host "  2. Copy config staging examples to config/*.php and fill credentials"
Write-Host "  3. Apply migrations 0003, 0004, 0011 (or full chain if fresh DB)"
Write-Host "  4. OpenCart staging: API key + product_api_route + From Warehouse = Yes"
Write-Host "  5. QA: status-mapping, sync-preview pull, product-control, Test Sync, import"
Write-Host ""
Write-Host "[OK] Staging product sync repo readiness passed"
