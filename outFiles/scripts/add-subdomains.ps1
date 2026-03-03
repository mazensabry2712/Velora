# Add tenant subdomains to Windows hosts file
# Run this script as Administrator

# Check if running as administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator', then run this script again." -ForegroundColor Yellow
    pause
    exit 1
}

$hostsFile = "C:\Windows\System32\drivers\etc\hosts"

Write-Host "Adding tenant subdomains to hosts file..." -ForegroundColor Cyan
Write-Host ""

# Read current content
$currentContent = Get-Content $hostsFile -Raw

# Fix any malformed entries first
if ($currentContent -match '## Local - End ##127\.0\.0\.1') {
    Write-Host "[FIX] Fixing malformed hosts file entries..." -ForegroundColor Yellow
    $currentContent = $currentContent -replace '(## Local - End ##)(127\.0\.0\.1)', "`$1`n`$2"
    Set-Content -Path $hostsFile -Value $currentContent -NoNewline
    $currentContent = Get-Content $hostsFile -Raw
}

# Add subdomain entries
$subdomains = @(
    "demo.booking-saas.test",
    "testcompany.booking-saas.test"
)

foreach($subdomain in $subdomains) {
    if($currentContent -match [regex]::Escape($subdomain)) {
        Write-Host "[EXISTS] $subdomain" -ForegroundColor Yellow
    } else {
        Add-Content -Path $hostsFile -Value "`n127.0.0.1 $subdomain"
        Write-Host "[ADDED] $subdomain" -ForegroundColor Green
    }
}

# Flush DNS
Write-Host ""
Write-Host "Flushing DNS cache..." -ForegroundColor Cyan
ipconfig /flushdns | Out-Null

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "SUCCESS! Configuration complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Close ALL browser windows" -ForegroundColor White
Write-Host "2. Open a new browser and go to:" -ForegroundColor White
Write-Host "   http://demo.booking-saas.test/login" -ForegroundColor Cyan
Write-Host ""
Write-Host "Login with:" -ForegroundColor Yellow
Write-Host "Email:    demo@admin.com" -ForegroundColor White
Write-Host "Password: password" -ForegroundColor White
Write-Host ""
pause
