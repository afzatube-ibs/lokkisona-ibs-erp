# Build installable OpenCart extension zip: dist/ibs-opencart-sync-connector-v1.0.0.ocmod.zip
# Uses forward-slash zip entry paths for Linux OpenCart servers.
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot = Resolve-Path (Join-Path $root '..\..')
$distDir = Join-Path $repoRoot 'dist'
$version = '1.0.0'
$zipName = "ibs-opencart-sync-connector-v$version.ocmod.zip"
$zipPath = Join-Path $distDir $zipName
$stage = Join-Path $env:TEMP "ibs-sync-connector-build-$version"

$requiredAdminFiles = @(
    'admin/controller/extension/module/ibs_sync_connector.php',
    'admin/language/en-gb/extension/module/ibs_sync_connector.php',
    'admin/view/template/extension/module/ibs_sync_connector.twig'
)

function Add-FileToZip {
    param(
        [System.IO.Compression.ZipArchive]$Zip,
        [string]$SourceFile,
        [string]$EntryName
    )
    $normalized = ($EntryName -replace '\\', '/').TrimStart('/')
    $entry = $Zip.CreateEntry($normalized, [System.IO.Compression.CompressionLevel]::Optimal)
    $stream = $entry.Open()
    try {
        $bytes = [System.IO.File]::ReadAllBytes($SourceFile)
        $stream.Write($bytes, 0, $bytes.Length)
    } finally {
        $stream.Close()
    }
}

function Add-DirectoryToZip {
    param(
        [System.IO.Compression.ZipArchive]$Zip,
        [string]$SourceDir,
        [string]$ZipPrefix
    )
    $prefix = ($ZipPrefix -replace '\\', '/').TrimEnd('/')
    Get-ChildItem -Path $SourceDir -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($SourceDir.Length).TrimStart('\', '/')
        $entryName = "$prefix/$relative"
        Add-FileToZip -Zip $Zip -SourceFile $_.FullName -EntryName $entryName
    }
}

if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
$uploadRoot = Join-Path $stage 'upload'
New-Item -ItemType Directory -Path $uploadRoot -Force | Out-Null

$folders = @('admin', 'catalog', 'system')
foreach ($folder in $folders) {
    $src = Join-Path $root $folder
    if (-not (Test-Path $src)) {
        throw "Missing folder: $src"
    }
    $dest = Join-Path $uploadRoot $folder
    Copy-Item -Path $src -Destination $dest -Recurse -Force
}

# OC3 fallback: duplicate en-gb language as english (older stores).
$enGbLang = Join-Path $uploadRoot 'admin\language\en-gb\extension\module\ibs_sync_connector.php'
$englishDir = Join-Path $uploadRoot 'admin\language\english\extension\module'
if (Test-Path $enGbLang) {
    New-Item -ItemType Directory -Path $englishDir -Force | Out-Null
    Copy-Item -Path $enGbLang -Destination (Join-Path $englishDir 'ibs_sync_connector.php') -Force
}

foreach ($rel in $requiredAdminFiles) {
    $full = Join-Path $uploadRoot ($rel -replace '/', '\')
    if (-not (Test-Path $full)) {
        throw "Required admin file missing in stage: $rel"
    }
}

Copy-Item (Join-Path $root 'install.xml') -Destination (Join-Path $stage 'install.xml') -Force
Copy-Item (Join-Path $root 'install.json') -Destination (Join-Path $stage 'install.json') -Force

if (-not (Test-Path $distDir)) { New-Item -ItemType Directory -Path $distDir -Force | Out-Null }
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    Add-FileToZip -Zip $zip -SourceFile (Join-Path $stage 'install.xml') -EntryName 'install.xml'
    Add-FileToZip -Zip $zip -SourceFile (Join-Path $stage 'install.json') -EntryName 'install.json'
    Add-DirectoryToZip -Zip $zip -SourceDir $uploadRoot -ZipPrefix 'upload'
} finally {
    $zip.Dispose()
}

Remove-Item $stage -Recurse -Force

Write-Output "Built: $zipPath"
Write-Output "Required module paths in zip (upload/ prefix):"
foreach ($rel in $requiredAdminFiles) {
    Write-Output "  upload/$rel"
}
Write-Output "Also: upload/admin/language/english/extension/module/ibs_sync_connector.php"

# Verify zip entry slashes
Add-Type -AssemblyName System.IO.Compression.FileSystem
$bad = @()
$archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    foreach ($entry in $archive.Entries) {
        if ($entry.FullName -match '\\') {
            $bad += $entry.FullName
        }
    }
} finally {
    $archive.Dispose()
}
if ($bad.Count -gt 0) {
    throw "Zip contains backslash paths: $($bad -join ', ')"
}
Write-Output "Zip path check: OK (forward slashes only)"
