# ================================================================
# release.ps1 - Cukup jalankan: .\release
# Otomatis deteksi versi terakhir, naikan, dan push ke GitHub
# ================================================================

param([string]$Version = "")

$Cwd = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $Cwd

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "   CUTI TARUNA - AUTO RELEASE APK   " -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Ambil tag terakhir secara otomatis
$lastTag = git describe --tags --abbrev=0 2>$null
if (-not $lastTag) { $lastTag = "v1.1" }

# Auto-increment versi minor (v1.2 -> v1.3 -> v1.4 dst)
if ($Version -eq "") {
    $parts = $lastTag.TrimStart('v') -split '\.'
    $major = [int]$parts[0]
    $minor = if ($parts.Length -gt 1) { [int]$parts[1] + 1 } else { 1 }
    $Version = "v$major.$minor"
}

Write-Host "  Versi terakhir  : $lastTag" -ForegroundColor Gray
Write-Host "  Versi baru      : $Version" -ForegroundColor Green
Write-Host ""
Write-Host "  Membuat tag dan memicu build APK di GitHub..." -ForegroundColor Yellow

# Buat tag dan push
git tag -a $Version -m "Release $Version - Cuti Taruna Mobile App" 2>&1 | Out-Null
git push origin $Version 2>&1 | Out-Null

Write-Host ""
Write-Host "  [OK] Tag $Version berhasil dipush ke GitHub!" -ForegroundColor Green
Write-Host ""
Write-Host "  GitHub Actions sekarang sedang build APK Anda..." -ForegroundColor Cyan
Write-Host ""
Write-Host "  Pantau proses build:" -ForegroundColor White
Write-Host "  https://github.com/semutear/cuti-taruna/actions" -ForegroundColor Blue
Write-Host ""
Write-Host "  Download APK setelah selesai (~5 menit):" -ForegroundColor White
Write-Host "  https://github.com/semutear/cuti-taruna/releases/tag/$Version" -ForegroundColor Blue
Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
