# Build distributable zips + brand PNGs for Free and Pro.
# Usage: .\scripts\build-all.ps1

$ErrorActionPreference = 'Stop'
$here = $PSScriptRoot

& ( Join-Path $here 'render-brand-assets.ps1' ) -Variant Free
& ( Join-Path ( Split-Path -Parent $here ) 'vms-elements-form-guard-pro\scripts\render-brand-assets.ps1' ) -Variant Pro
& ( Join-Path $here 'build-plugin-zip.ps1' )
& ( Join-Path ( Split-Path -Parent $here ) 'vms-elements-form-guard-pro\scripts\build-plugin-zip.ps1' )

Write-Host ''
Write-Host 'Done. Zips:'
Write-Host "  $( Join-Path ( Split-Path -Parent $here ) 'vms-elements-form-guard\dist\vms-elements-form-guard.zip' )"
Write-Host "  $( Join-Path ( Split-Path -Parent $here ) 'vms-elements-form-guard-pro\dist\vms-elements-form-guard-pro.zip' )"
