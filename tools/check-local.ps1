param(
    [int]$Port = 8020
)

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.UTF8Encoding]::new()
$OutputEncoding = [System.Text.UTF8Encoding]::new()
$root = Resolve-Path (Join-Path $PSScriptRoot "..")
$serverProcess = $null
$serverStarted = $false
$redIssues = @()
$checkpointFailed = $false
$appVersionLabel = "v0.4.2.2 Dev Database Activation Helper and Table Verification Foundation"
$writePathWhitelistDirs = @("app/Services/Write", "app/Repositories/Write")
$routeSmokeCount = 0

function Add-RedIssue($issue, $area, $filePage, $whatToFix) {
    $script:redIssues += [PSCustomObject]@{
        Issue = $issue
        Area = $area
        FilePage = $filePage
        WhatToFix = $whatToFix
    }
}

function Fail($message, $area = "Checkpoint", $filePage = "n/a", $whatToFix = $message) {
    Write-Host "[FAIL] $message"
    Add-RedIssue $message $area $filePage $whatToFix
    throw $message
}

function Ok($message) {
    Write-Host "[OK] $message"
}

function Show-Footer {
    Write-Host ""
    Write-Host "========================================"
    if ($script:redIssues.Count -eq 0) {
        Write-Host "[OK] ALL GREEN"
        Write-Host "Version: $script:appVersionLabel"
        Write-Host "Checkpoint: passed"
        Write-Host "Browser/Routes: passed ($script:routeSmokeCount routes)"
        Write-Host "Git: summary printed above"
        Write-Host "Red Issues: none"
    } else {
        Write-Host "[FAIL] RED ISSUES SUMMARY"
        $index = 1
        foreach ($redIssue in $script:redIssues) {
            Write-Host "$index. Issue: $($redIssue.Issue)"
            Write-Host "   Area: $($redIssue.Area)"
            Write-Host "   File/Page: $($redIssue.FilePage)"
            Write-Host "   What to fix: $($redIssue.WhatToFix)"
            $index++
        }
    }
    Write-Host "========================================"
}

function Find-Php {
    $candidates = @(
        "D:\xampp\php\php.exe",
        "E:\xampp\php\php.exe",
        "C:\xampp\php\php.exe"
    )

    foreach ($candidate in $candidates) {
        if (Test-Path $candidate) {
            return $candidate
        }
    }

    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) {
        return $cmd.Source
    }

    Fail "PHP executable not found. Expected D:\xampp\php\php.exe, E:\xampp\php\php.exe, C:\xampp\php\php.exe, or php in PATH." "PHP auto-detect" "tools/check-local.ps1" "Install PHP in one of the configured XAMPP paths or add php to PATH."
}

function Invoke-HttpStatus($url, $session = $null) {
    try {
        $uri = [Uri]$url
        $request = [System.Net.HttpWebRequest]::Create($uri)
        $request.Method = "GET"
        $request.AllowAutoRedirect = $false
        $request.Timeout = 10000

        if ($session) {
            foreach ($cookie in $session.Cookies.GetCookies($uri)) {
                if (-not $request.CookieContainer) {
                    $request.CookieContainer = New-Object System.Net.CookieContainer
                }
                $request.CookieContainer.Add($cookie)
            }
        }

        $response = $request.GetResponse()
        $status = [int]$response.StatusCode
        $response.Close()
        return $status
    } catch {
        if ($_.Exception.Response) {
            $status = [int]$_.Exception.Response.StatusCode
            $_.Exception.Response.Close()
            return $status
        }
        throw
    }
}

function Wait-ForServer($baseUrl) {
    for ($i = 0; $i -lt 20; $i++) {
        try {
            $status = Invoke-HttpStatus "$baseUrl/login"
            if ($status -ge 200 -and $status -lt 500) {
                return
            }
        } catch {
            Start-Sleep -Milliseconds 250
        }
    }

    Fail "Temporary PHP server did not start on $baseUrl." "Browser/Routes" $baseUrl "Check that PHP can start the built-in server and that port is available."
}

Set-Location $root

try {
    $php = Find-Php
    $phpFiles = Get-ChildItem -Path @("app", "config", "public", "resources", "routes") -Filter "*.php" -Recurse -File
    foreach ($file in $phpFiles) {
        & $php -l $file.FullName | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Fail "PHP lint failed: $($file.FullName)" "PHP lint" $file.FullName "Fix the PHP syntax error reported above."
        }
    }
    Ok "PHP lint"

    $baseUrl = "http://127.0.0.1:$Port"
    $serverAvailable = $false
    try {
        $status = Invoke-HttpStatus "$baseUrl/login"
        $serverAvailable = ($status -ge 200 -and $status -lt 500)
    } catch {
        $serverAvailable = $false
    }

    if (-not $serverAvailable) {
        $serverProcess = Start-Process -FilePath $php -ArgumentList @("-S", "127.0.0.1:$Port", "-t", "public", "public/router.php") -WorkingDirectory $root -PassThru -WindowStyle Hidden
        $serverStarted = $true
        Wait-ForServer $baseUrl
    }

    $routes = @("/login", "/dashboard", "/activity-log", "/roles-permissions", "/database-safety", "/dev-db-activation", "/migration-runner", "/migration-files", "/migration-dry-run", "/migration-approval", "/migration-execution-lock", "/supplier-opening-balances", "/build-queue", "/health", "/version", "/users", "/suppliers", "/business-sources", "/product-control", "/order-workflow", "/dispatch-reports", "/supplier-payables", "/return-receive", "/status-mapping", "/sync-preview", "/invoice-printing", "/supplier-tools", "/manual-orders")
    $script:routeSmokeCount = $routes.Count
    foreach ($route in $routes) {
        $status = Invoke-HttpStatus "$baseUrl$route"
        if ($status -notin @(200, 301, 302, 303)) {
            Fail "Route smoke failed for $route with HTTP $status." "Browser/Routes" $route "Fix the controller, route, auth redirect, or view causing this route to return HTTP $status."
        }
    }
    Ok "Route smoke"

    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $loginResponse = Invoke-WebRequest -Uri "$baseUrl/login" -Method "POST" -Body @{ username = "admin"; password = "admin" } -WebSession $session -MaximumRedirection 5 -UseBasicParsing -TimeoutSec 10
    $versionResponse = Invoke-WebRequest -Uri "$baseUrl/version" -Method "GET" -WebSession $session -UseBasicParsing -TimeoutSec 10
    if ($versionResponse.Content -notmatch "v0\.4\.2\.2") {
        Fail "Version check failed: /version does not contain v0.4.2.2." "Version" "/version" "Update config/app.php and VersionController so /version displays v0.4.2.2."
    }
    Ok "Version"

    $scanFiles = Get-ChildItem -Path @("app", "config", "public", "resources", "routes", "README.md", "database") -Recurse -File -ErrorAction SilentlyContinue |
        Where-Object {
            $_.FullName -notmatch "\\\.git\\" -and
            $_.FullName -notmatch "\\storage\\" -and
            $_.FullName -notmatch "\\cache\\" -and
            $_.Extension -in @(".php", ".md", ".css", ".js", ".sql")
        }

    foreach ($file in $scanFiles) {
        $content = Get-Content -Raw -Path $file.FullName
        if ($content -match "PHP 7\.4\+") {
            Fail "Forbidden text found in $($file.FullName): PHP 7.4+" "Forbidden text" $file.FullName "Replace legacy PHP 7.4+ wording with current PHP 8.2+ wording."
        }
        if ($content -match "Lokkisona IBS ERP") {
            Fail "Forbidden branding found in $($file.FullName): Lokkisona IBS ERP" "Forbidden text" $file.FullName "Use IBS-LK Business Manager branding."
        }
    }
    Ok "Forbidden text"

    $runtimeFiles = Get-ChildItem -Path @("app", "public", "resources", "routes") -Filter "*.php" -Recurse -File
    foreach ($file in $runtimeFiles) {
        $relativePath = $file.FullName.Substring($root.Path.Length + 1) -replace "\\", "/"
        $isWriteWhitelisted = $false
        foreach ($writePathWhitelistDir in $writePathWhitelistDirs) {
            if ($relativePath -eq $writePathWhitelistDir -or $relativePath.StartsWith("$writePathWhitelistDir/")) {
                $isWriteWhitelisted = $true
                break
            }
        }
        if ($isWriteWhitelisted) {
            continue
        }
        $lines = Get-Content -Path $file.FullName
        foreach ($line in $lines) {
            if ($line -match "(?i)CREATE\s+TABLE|ALTER\s+TABLE|DROP\s+TABLE") {
                if ($line -notmatch "(?i)No .*CREATE\s+TABLE|No .*ALTER\s+TABLE|No .*DROP\s+TABLE|CREATE\s+TABLE.*during page load|ALTER\s+TABLE.*during page load|DROP\s+TABLE.*during page load") {
                    Fail "Runtime schema statement found in $($file.FullName)." "Database safety" $file.FullName "Remove runtime CREATE TABLE / ALTER TABLE / DROP TABLE. Schema changes must be manual migrations only."
                }
            }
            if ($line -match "(?i)schema\s+repair|repair\s+schema") {
                if ($line -notmatch "(?i)No .*schema\s+repair|No .*repair\s+schema") {
                    Fail "Suspicious page-load schema repair wording found in $($file.FullName)." "Database safety" $file.FullName "Remove schema repair behavior/wording from runtime page-load code."
                }
            }
            if ($line -match "(?i)\b(INSERT|UPDATE|DELETE|TRUNCATE|REPLACE)\b") {
                if ($line -notmatch "(?i)No INSERT|No UPDATE|No DELETE|no INSERT|no UPDATE|no DELETE|does not INSERT|does not UPDATE|does not DELETE|no database writes|No database writes|not INSERT|not UPDATE|not DELETE") {
                    Fail "Runtime mutation SQL found in $($file.FullName)." "Database safety" $file.FullName "Remove runtime INSERT / UPDATE / DELETE / TRUNCATE / REPLACE. Writes belong to a future owner-approved service layer."
                }
            }
        }
    }

    $migrationSqlFiles = Get-ChildItem -Path "database/migrations" -Filter "*.sql" -File -ErrorAction SilentlyContinue
    foreach ($file in $migrationSqlFiles) {
        $content = Get-Content -Raw -Path $file.FullName
        foreach ($requiredWarning in @("DRAFT ONLY", "DO NOT AUTO RUN", "APPLY MANUALLY ONLY AFTER OWNER APPROVAL", "BACKUP DATABASE FIRST", "NOT EXECUTED BY APPLICATION PAGE LOAD")) {
            if ($content -notmatch [regex]::Escape($requiredWarning)) {
                Fail "Migration draft warning missing in $($file.FullName): $requiredWarning" "Database safety" $file.FullName "Add the required manual-only warning header to the migration draft."
            }
        }
    }
    Ok "Database safety"

    git status --short
    Ok "Git summary"
} catch {
    $checkpointFailed = $true
    if ($redIssues.Count -eq 0) {
        Add-RedIssue $_.Exception.Message "Checkpoint" "tools/check-local.ps1" "Review the detailed error above and fix the failing checkpoint step."
    }
} finally {
    if ($serverStarted -and $serverProcess -and -not $serverProcess.HasExited) {
        Stop-Process -Id $serverProcess.Id -Force
    }
    Show-Footer
    if ($checkpointFailed) {
        exit 1
    }
}
