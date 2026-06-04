param(
    [int]$Port = 8020
)

$ErrorActionPreference = "Stop"
$root = Resolve-Path (Join-Path $PSScriptRoot "..")
$serverProcess = $null
$serverStarted = $false

function Fail($message) {
    Write-Host "[FAIL] $message"
    exit 1
}

function Ok($message) {
    Write-Host "[OK] $message"
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

    Fail "PHP executable not found. Expected D:\xampp\php\php.exe, E:\xampp\php\php.exe, C:\xampp\php\php.exe, or php in PATH."
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

    Fail "Temporary PHP server did not start on $baseUrl."
}

Set-Location $root
$php = Find-Php

try {
    $phpFiles = Get-ChildItem -Path @("app", "config", "public", "resources", "routes") -Filter "*.php" -Recurse -File
    foreach ($file in $phpFiles) {
        & $php -l $file.FullName | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Fail "PHP lint failed: $($file.FullName)"
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

    $routes = @("/login", "/dashboard", "/activity-log", "/roles-permissions", "/database-safety", "/health", "/version", "/users", "/suppliers", "/business-sources", "/product-control", "/order-workflow", "/dispatch-reports")
    foreach ($route in $routes) {
        $status = Invoke-HttpStatus "$baseUrl$route"
        if ($status -notin @(200, 301, 302, 303)) {
            Fail "Route smoke failed for $route with HTTP $status."
        }
    }
    Ok "Route smoke"

    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $loginResponse = Invoke-WebRequest -Uri "$baseUrl/login" -Method "POST" -Body @{ username = "admin"; password = "admin" } -WebSession $session -MaximumRedirection 5 -UseBasicParsing -TimeoutSec 10
    $versionResponse = Invoke-WebRequest -Uri "$baseUrl/version" -Method "GET" -WebSession $session -UseBasicParsing -TimeoutSec 10
    if ($versionResponse.Content -notmatch "v0\.1\.11") {
        Fail "Version check failed: /version does not contain v0.1.11."
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
            Fail "Forbidden text found in $($file.FullName): PHP 7.4+"
        }
        if ($content -match "Lokkisona IBS ERP") {
            Fail "Forbidden branding found in $($file.FullName): Lokkisona IBS ERP"
        }
    }
    Ok "Forbidden text"

    $runtimeFiles = Get-ChildItem -Path @("app", "public", "resources", "routes") -Filter "*.php" -Recurse -File
    foreach ($file in $runtimeFiles) {
        $lines = Get-Content -Path $file.FullName
        foreach ($line in $lines) {
            if ($line -match "(?i)CREATE\s+TABLE|ALTER\s+TABLE") {
                if ($line -notmatch "(?i)No .*CREATE\s+TABLE|No .*ALTER\s+TABLE|CREATE\s+TABLE.*during page load|ALTER\s+TABLE.*during page load") {
                    Fail "Runtime schema statement found in $($file.FullName)."
                }
            }
            if ($line -match "(?i)schema\s+repair|repair\s+schema") {
                if ($line -notmatch "(?i)No .*schema\s+repair|No .*repair\s+schema") {
                    Fail "Suspicious page-load schema repair wording found in $($file.FullName)."
                }
            }
        }
    }
    Ok "Database safety"

    git status --short
    Ok "Git summary"
} finally {
    if ($serverStarted -and $serverProcess -and -not $serverProcess.HasExited) {
        Stop-Process -Id $serverProcess.Id -Force
    }
}
