# Builds a WordPress.org-ready plugin zip (no vendor/composer dev files).
# Uses forward-slash paths and file-only entries (WordPress-safe on Windows).
# Usage: .\scripts\build-plugin-zip.ps1

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$pluginRoot = Split-Path -Parent $PSScriptRoot
$pluginSlug = Split-Path -Leaf $pluginRoot
$distDir    = Join-Path $pluginRoot 'dist'
$zipPath    = Join-Path $distDir "$pluginSlug.zip"
$ignoreFile = Join-Path $pluginRoot '.distignore'

function Test-DistIgnore {
	param(
		[string]$RelativePath,
		[string[]]$Patterns
	)

	$normalized = $RelativePath -replace '\\', '/'

	foreach ($raw in $Patterns) {
		$pattern = $raw.Trim()
		if ('' -eq $pattern -or $pattern.StartsWith('#')) {
			continue
		}

		$pattern = $pattern -replace '^/+', ''

		if ($pattern.EndsWith('/')) {
			$prefix = $pattern.TrimEnd('/')
			if ($normalized -eq $prefix -or $normalized.StartsWith("$prefix/")) {
				return $true
			}
			continue
		}

		if ($pattern.StartsWith('*.')) {
			$ext = $pattern.Substring(1)
			if ($normalized.EndsWith($ext)) {
				return $true
			}
			continue
		}

		if ($normalized -eq $pattern) {
			return $true
		}
	}

	return $false
}

if (-not (Test-Path $ignoreFile)) {
	throw ".distignore not found at $ignoreFile"
}

$ignorePatterns = Get-Content -Path $ignoreFile -ErrorAction Stop

New-Item -ItemType Directory -Path $distDir -Force | Out-Null

if (Test-Path $zipPath) {
	Remove-Item -Path $zipPath -Force
}

$fileCount = 0
$archive   = [System.IO.Compression.ZipFile]::Open( $zipPath, [System.IO.Compression.ZipArchiveMode]::Create )

try {
	$files = Get-ChildItem -Path $pluginRoot -Recurse -File -Force
	foreach ($file in $files) {
		$relative = $file.FullName.Substring( $pluginRoot.Length + 1 )
		if ( Test-DistIgnore -RelativePath $relative -Patterns $ignorePatterns ) {
			continue
		}

		$entryName = ( $pluginSlug + '/' + ( $relative -replace '\\', '/' ) )
		[void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
			$archive,
			$file.FullName,
			$entryName,
			[System.IO.Compression.CompressionLevel]::Optimal
		)
		$fileCount++
	}
}
finally {
	$archive.Dispose()
}

if ( $fileCount -eq 0 ) {
	throw 'No files were added to the zip. Check .distignore patterns.'
}

$sizeMb = [math]::Round( ( Get-Item $zipPath ).Length / 1MB, 2 )
Write-Host "Created $zipPath ($sizeMb MB, $fileCount files)"
