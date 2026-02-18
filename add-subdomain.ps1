# Add subdomain to hosts file
# Run as Administrator: Right-click PowerShell -> Run as Administrator
# Usage: .\add-subdomain.ps1 test

param(
    [Parameter(Mandatory=$true)]
    [string]$subdomain
)

$hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
$domain = "$subdomain.booking-saas.test"
$entry = "127.0.0.1 $domain"

# Check if entry already exists
$content = Get-Content $hostsPath
if ($content -match [regex]::Escape($domain)) {
    Write-Host "✅ Domain $domain already exists in hosts file" -ForegroundColor Green
    exit 0
}

# Add new entry
Add-Content -Path $hostsPath -Value $entry
Write-Host "✅ Added $domain to hosts file" -ForegroundColor Green
Write-Host "🔄 Please flush DNS cache: ipconfig /flushdns" -ForegroundColor Yellow
