param(
    [Parameter(Position = 0)]
    [string]$Message
)

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.UTF8Encoding]::new()
$OutputEncoding = [System.Text.UTF8Encoding]::new()
$root = Resolve-Path (Join-Path $PSScriptRoot "..")

function Show-FailSummary($reason) {
    Write-Host ""
    Write-Host "[FAIL] RED ISSUES SUMMARY"
    Write-Host "Reason: $reason"
    Write-Host "Commit: stopped"
    Write-Host "Push: stopped"
}

if ([string]::IsNullOrWhiteSpace($Message)) {
    Show-FailSummary "commit message argument required"
    return
}

Set-Location $root

Write-Host "[OK] Finish build requested"
Write-Host "Version: $Message"
Write-Host "Checkpoint: starting"

powershell -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "check-local.ps1")
if ($LASTEXITCODE -ne 0) {
    Show-FailSummary "checkpoint failed"
    return
}

Write-Host "[OK] Checkpoint passed"

git add README.md app config database public resources routes tools
if ($LASTEXITCODE -ne 0) {
    Show-FailSummary "git add failed"
    return
}

git commit -m $Message
if ($LASTEXITCODE -ne 0) {
    Show-FailSummary "git commit failed"
    return
}

git push origin main
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "[FAIL] RED ISSUES SUMMARY"
    Write-Host "Reason: git push failed"
    Write-Host "Commit: completed"
    Write-Host "Push: stopped"
    return
}

git status -sb
git log --oneline --decorate -3

Write-Host ""
Write-Host "[OK] ALL GREEN"
Write-Host "Version: $Message"
Write-Host "Checkpoint: passed"
Write-Host "Git: committed and pushed"
Write-Host "Red Issues: none"
