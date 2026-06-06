param(
    [string]$OutputDir = "",
    [switch]$Zip
)

$ErrorActionPreference = "Stop"
$root = Resolve-Path (Join-Path $PSScriptRoot "..")

if ($OutputDir -eq "") {
    $OutputDir = Join-Path $root "dist/staging-ftp-upload"
}

$excludeNames = @(
    ".git",
    ".cursor",
    "dist",
    "vendor",
    ".idea",
    ".vscode"
)

$includeTopLevel = @(
    "app",
    "config",
    "database",
    "docs",
    "public",
    "resources",
    "routes",
    "storage",
    "tools",
    "README.md",
    ".gitignore"
)

function Should-Exclude($name) {
    return $excludeNames -contains $name
}

if (Test-Path $OutputDir) {
    Remove-Item -Recurse -Force $OutputDir
}

New-Item -ItemType Directory -Path $OutputDir | Out-Null

foreach ($name in $includeTopLevel) {
    $source = Join-Path $root $name
    if (-not (Test-Path $source)) {
        throw "Missing required path: $name"
    }
    $target = Join-Path $OutputDir $name
    if ((Get-Item $source).PSIsContainer) {
        Copy-Item -Path $source -Destination $target -Recurse -Force
    } else {
        Copy-Item -Path $source -Destination $target -Force
    }
}

Write-Host "[OK] FTP package folder: $OutputDir"
Write-Host ""
Write-Host "Upload this ENTIRE folder to your ERP staging server."
Write-Host "Then set document root to: .../public"
Write-Host ""
Write-Host "cPanel example:"
Write-Host "  1. FTP upload folder to /home/USERNAME/lokkisona-ibs-erp/"
Write-Host "  2. Subdomain ibs-staging.lokkisona.com -> Document Root = .../lokkisona-ibs-erp/public"
Write-Host "  3. On server: copy config/*.staging.example.php to config/*.php and edit"
Write-Host "  4. chmod 755 storage and storage/logs (writable)"
Write-Host ""
Write-Host "Do NOT upload .git or .cursor folders."
Write-Host "After first deploy, do NOT overwrite server config/*.php on updates."

try {
    $hash = git -C $root rev-parse --short HEAD 2>$null
    if ($hash) {
        $versionFile = Join-Path $OutputDir "DEPLOY-VERSION.txt"
        Set-Content -Path $versionFile -Value "commit=$hash`npackaged=$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
        Write-Host "[OK] Deploy marker: DEPLOY-VERSION.txt ($hash)"
    }
} catch {
    Write-Host "[WARN] Could not write deploy version marker"
}

if ($Zip) {
    $zipPath = Join-Path $root "dist/staging-ftp-upload.zip"
    if (Test-Path $zipPath) {
        Remove-Item -Force $zipPath
    }
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::CreateFromDirectory($OutputDir, $zipPath)
    Write-Host "[OK] ZIP created: $zipPath"
    Write-Host "Upload ZIP to server, extract, then point docroot to public/"
}

Write-Host ""
Write-Host "[OK] Staging FTP package ready"
