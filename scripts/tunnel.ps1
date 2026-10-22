# =====================================================================
# Smart Lost & Found - Mobile & Public Testing Tunneling Assistant
# =====================================================================

Write-Host "=========================================================" -ForegroundColor Cyan
Write-Host "  Smart Lost & Found - Mobile & Public Access Assistant" -ForegroundColor Cyan
Write-Host "=========================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Detect Local Wi-Fi / LAN IP
$localIps = (Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | Where-Object { 
    $_.IPAddress -notlike "127.*" -and $_.IPAddress -notlike "169.254.*" 
} | Select-Object -ExpandProperty IPAddress)

Write-Host "[OPTION 1: LOCAL WI-FI / LAN ACCESS (Zero Setup)]" -ForegroundColor Green
Write-Host "If your mobile phone is connected to the same Wi-Fi network as this PC,"
Write-Host "you can open this URL directly in your phone's browser right now:"
Write-Host ""
foreach ($ip in $localIps) {
    Write-Host "  👉 http://$ip/lost_and_found/" -ForegroundColor Yellow
}
Write-Host ""
Write-Host "---------------------------------------------------------" -ForegroundColor DarkGray

# 2. Public Tunnel Options (Over 4G/5G Cellular or External Reviewers)
Write-Host "[OPTION 2: PUBLIC INTERNET TUNNEL (4G/5G / Remote Reviewers)]" -ForegroundColor Green

$hasNgrok = (Get-Command ngrok -ErrorAction SilentlyContinue) -ne $null
$hasNpx = (Get-Command npx.cmd, npx -ErrorAction SilentlyContinue) -ne $null

Write-Host "Available public tunneling tools on this machine:"
if ($hasNgrok) {
    Write-Host "  [OK] Ngrok is installed." -ForegroundColor Green
} else {
    Write-Host "  [--] Ngrok not found." -ForegroundColor DarkGray
}

if ($hasNpx) {
    Write-Host "  [OK] NPX (Localtunnel) is available." -ForegroundColor Green
} else {
    Write-Host "  [--] NPX not found." -ForegroundColor DarkGray
}
Write-Host ""

Write-Host "Choose an option to launch:" -ForegroundColor White
Write-Host "  1. Start Ngrok Tunnel (Port 80) -> Public HTTPS URL"
Write-Host "  2. Start Localtunnel via NPX (Port 80) -> Free instant tunnel"
Write-Host "  3. Exit"
Write-Host ""

$choice = Read-Host "Enter your choice (1-3) [Default: 1]"
if ([string]::IsNullOrWhiteSpace($choice)) { $choice = "1" }

switch ($choice) {
    "1" {
        if ($hasNgrok) {
            Write-Host "Starting Ngrok on Port 80..." -ForegroundColor Cyan
            Write-Host "Copy the 'Forwarding' HTTPS URL shown below and open it on your phone!" -ForegroundColor Yellow
            ngrok http 80
        } else {
            Write-Host "Ngrok is not installed. You can install it via 'winget install ngrok' or download from ngrok.com" -ForegroundColor Red
        }
    }
    "2" {
        if ($hasNpx) {
            Write-Host "Starting Localtunnel on Port 80..." -ForegroundColor Cyan
            npx localtunnel --port 80
        } else {
            Write-Host "Node.js / NPX not detected." -ForegroundColor Red
        }
    }
    default {
        Write-Host "Exited. You can run this script anytime using: powershell -ExecutionPolicy Bypass -File scripts/tunnel.ps1" -ForegroundColor Gray
    }
}
