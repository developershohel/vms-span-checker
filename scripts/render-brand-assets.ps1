# Renders WordPress.org + marketplace PNGs from the VMS Elements Form Guard brand.
# Usage:
#   .\scripts\render-brand-assets.ps1
#   .\scripts\render-brand-assets.ps1 -Variant Pro

param(
	[ValidateSet( 'Free', 'Pro' )]
	[string]$Variant = 'Free'
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$pluginRoot = Split-Path -Parent $PSScriptRoot
$isPro      = 'Pro' -eq $Variant
$title      = if ( $isPro ) { 'VMS Elements Form Guard Pro' } else { 'VMS Elements Form Guard' }
$tagline    = if ( $isPro ) {
	'Pro extension — advanced form mapping, guards, AI summaries & licensing'
} else {
	'Email domain validation & spam protection for WordPress forms'
}
$subtitle   = if ( $isPro ) {
	'Form Guard · Contact & subscribe guards · WooCommerce reviews · AI summaries'
} else {
	'Disposable lists · HTTPS checks · Optional VirusTotal & Google Web Risk'
}

$outDir = if ( $isPro ) {
	Join-Path ( Split-Path -Parent $pluginRoot ) 'vms-elements-form-guard-pro\assets\brand'
} else {
	Join-Path $pluginRoot 'assets\wordpress-org'
}

function New-VefgColor {
	param( [int]$R, [int]$G, [int]$B, [int]$A = 255 )
	return [System.Drawing.Color]::FromArgb( $A, $R, $G, $B )
}

function New-VefgRoundedRectPath {
	param(
		[float]$X,
		[float]$Y,
		[float]$Width,
		[float]$Height,
		[float]$Radius
	)

	$path = New-Object System.Drawing.Drawing2D.GraphicsPath
	$d    = [Math]::Min( $Radius, [Math]::Min( $Width, $Height ) / 2 )
	$path.AddArc( $X, $Y, $d * 2, $d * 2, 180, 90 )
	$path.AddArc( $X + $Width - $d * 2, $Y, $d * 2, $d * 2, 270, 90 )
	$path.AddArc( $X + $Width - $d * 2, $Y + $Height - $d * 2, $d * 2, $d * 2, 0, 90 )
	$path.AddArc( $X, $Y + $Height - $d * 2, $d * 2, $d * 2, 90, 90 )
	$path.CloseFigure()
	return $path
}

function Draw-VefgLogoMark {
	param(
		[System.Drawing.Graphics]$G,
		[System.Drawing.RectangleF]$Bounds,
		[float]$CornerRatio = 0.227
	)

	$g = $G
	$g.SmoothingMode     = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
	$g.PixelOffsetMode   = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
	$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic

	$radius = $Bounds.Width * $CornerRatio
	$rect   = $Bounds

	$bgBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
		$rect,
		( New-VefgColor -R 79 -G 70 -B 229 ),
		( New-VefgColor -R 99 -G 102 -B 241 ),
		45
	)
	$rounded = New-VefgRoundedRectPath $rect.X $rect.Y $rect.Width $rect.Height $radius
	$g.FillPath( $bgBrush, $rounded )

	$shineRect = [System.Drawing.RectangleF]::new( $rect.X, $rect.Y, $rect.Width, $rect.Height * 0.55 )
	$shineBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
		$shineRect,
		( New-VefgColor -R 255 -G 255 -B 255 -A 56 ),
		( New-VefgColor -R 255 -G 255 -B 255 -A 0 ),
		90
	)
	$g.FillPath( $shineBrush, $rounded )

	$cx = $rect.X + $rect.Width / 2
	$cy = $rect.Y + $rect.Height / 2
	$sw = $rect.Width * 0.58
	$sh = $rect.Height * 0.62

	$shield = New-Object System.Drawing.Drawing2D.GraphicsPath
	$shield.AddBezier(
		$cx, $rect.Y + $rect.Height * 0.2,
		$rect.X + $rect.Width * 0.88, $rect.Y + $rect.Height * 0.32,
		$rect.X + $rect.Width * 0.88, $rect.Y + $rect.Height * 0.58,
		$cx, $rect.Y + $rect.Height * 0.84
	)
	$shield.AddBezier(
		$cx, $rect.Y + $rect.Height * 0.84,
		$rect.X + $rect.Width * 0.12, $rect.Y + $rect.Height * 0.58,
		$rect.X + $rect.Width * 0.12, $rect.Y + $rect.Height * 0.32,
		$cx, $rect.Y + $rect.Height * 0.2
	)
	$shield.CloseFigure()

	$g.FillPath( ( New-Object System.Drawing.SolidBrush ( New-VefgColor -R 255 -G 255 -B 255 -A 245 ) ), $shield )

	$pen = New-Object System.Drawing.Pen ( ( New-VefgColor -R 6 -G 182 -B 212 ) ), ( $rect.Width * 0.05 )
	$pen.StartCap  = [System.Drawing.Drawing2D.LineCap]::Round
	$pen.EndCap    = [System.Drawing.Drawing2D.LineCap]::Round
	$pen.LineJoin  = [System.Drawing.Drawing2D.LineJoin]::Round

	$check = New-Object System.Drawing.Drawing2D.GraphicsPath
	$check.AddLines(
		@(
			[System.Drawing.PointF]::new( $cx - $sw * 0.22, $cy + $sh * 0.02 ),
			[System.Drawing.PointF]::new( $cx - $sw * 0.02, $cy + $sh * 0.2 ),
			[System.Drawing.PointF]::new( $cx + $sw * 0.28, $cy - $sh * 0.18 )
		)
	)
	$g.DrawPath( $pen, $check )

	if ( $isPro ) {
		$badgeRect = [System.Drawing.RectangleF]::new(
			$rect.Right - $rect.Width * 0.34,
			$rect.Bottom - $rect.Height * 0.22,
			$rect.Width * 0.3,
			$rect.Height * 0.16
		)
		$badgePath = New-VefgRoundedRectPath $badgeRect.X $badgeRect.Y $badgeRect.Width $badgeRect.Height ( $badgeRect.Height * 0.35 )
		$g.FillPath( ( New-Object System.Drawing.SolidBrush ( New-VefgColor -R 245 -G 158 -B 11 ) ), $badgePath )
		$badgeFont = [System.Drawing.Font]::new( 'Segoe UI', [float]( $rect.Width * 0.09 ), [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel )
		$sf        = New-Object System.Drawing.StringFormat
		$sf.Alignment     = [System.Drawing.StringAlignment]::Center
		$sf.LineAlignment = [System.Drawing.StringAlignment]::Center
		$g.DrawString( 'PRO', $badgeFont, [System.Drawing.Brushes]::White, $badgeRect, $sf )
	}

	$bgBrush.Dispose()
	$shineBrush.Dispose()
	$rounded.Dispose()
	$shield.Dispose()
	$pen.Dispose()
	$check.Dispose()
}

function Save-VefgBitmap {
	param(
		[System.Drawing.Bitmap]$Bitmap,
		[string]$Path
	)

	$dir = Split-Path -Parent $Path
	if ( -not ( Test-Path $dir ) ) {
		New-Item -ItemType Directory -Path $dir -Force | Out-Null
	}

	$Bitmap.Save( $Path, [System.Drawing.Imaging.ImageFormat]::Png )
}

function New-VefgIconBitmap {
	param( [int]$Size )

	$bmp = New-Object System.Drawing.Bitmap $Size, $Size
	$g   = [System.Drawing.Graphics]::FromImage( $bmp )
	$g.Clear( [System.Drawing.Color]::Transparent )
	Draw-VefgLogoMark $g ( [System.Drawing.RectangleF]::new( 0, 0, $Size, $Size ) )
	$g.Dispose()
	return $bmp
}

function New-VefgBannerBitmap {
	param( [int]$Width, [int]$Height )

	$bmp = New-Object System.Drawing.Bitmap $Width, $Height
	$g   = [System.Drawing.Graphics]::FromImage( $bmp )
	$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias

	$rect = [System.Drawing.Rectangle]::new( 0, 0, $Width, $Height )
	$bg   = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
		$rect,
		( New-VefgColor -R 49 -G 46 -B 129 ),
		( New-VefgColor -R 99 -G 102 -B 241 ),
		35
	)
	$g.FillRectangle( $bg, $rect )

	$blobBrush = New-Object System.Drawing.SolidBrush ( New-VefgColor -R 6 -G 182 -B 212 -A 40 )
	$g.FillEllipse( $blobBrush, ( $Width * 0.72 ), ( -$Height * 0.15 ), ( $Width * 0.35 ), ( $Height * 0.7 ) )
	$g.FillEllipse( $blobBrush, ( -$Width * 0.08 ), ( $Height * 0.55 ), ( $Width * 0.22 ), ( $Height * 0.55 ) )

	$logoSize = [int]( [Math]::Min( $Height * 0.72, $Width * 0.18 ) )
	$logoX    = [int]( $Width * 0.05 )
	$logoY    = [int]( ( $Height - $logoSize ) / 2 )
	Draw-VefgLogoMark $g ( [System.Drawing.RectangleF]::new( $logoX, $logoY, $logoSize, $logoSize ) )

	$textX = $logoX + $logoSize + [int]( $Width * 0.03 )
	$titleSize = [int]( $Height * 0.17 )
	if ( $titleSize -lt 18 ) { $titleSize = 18 }
	$titleFont = [System.Drawing.Font]::new( 'Segoe UI', [float]$titleSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel )
	$subFont   = [System.Drawing.Font]::new( 'Segoe UI', [float]( $titleSize * 0.42 ), [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel )
	$metaFont  = [System.Drawing.Font]::new( 'Segoe UI', [float]( $titleSize * 0.32 ), [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel )

	$brushTitle = New-Object System.Drawing.SolidBrush ( New-VefgColor -R 255 -G 255 -B 255 )
	$brushSub   = New-Object System.Drawing.SolidBrush ( New-VefgColor -R 199 -G 210 -B 254 )
	$brushMeta  = New-Object System.Drawing.SolidBrush ( New-VefgColor -R 165 -G 180 -B 252 )

	$g.DrawString( $title, $titleFont, $brushTitle, $textX, ( $Height * 0.28 ) )
	$g.DrawString( $tagline, $subFont, $brushSub, $textX, ( $Height * 0.52 ) )
	$g.DrawString( $subtitle, $metaFont, $brushMeta, $textX, ( $Height * 0.7 ) )

	$bg.Dispose()
	$blobBrush.Dispose()
	$titleFont.Dispose()
	$subFont.Dispose()
	$metaFont.Dispose()
	$brushTitle.Dispose()
	$brushSub.Dispose()
	$brushMeta.Dispose()
	$g.Dispose()
	return $bmp
}

function New-VefgCoverBitmap {
	$width  = 2340
	$height = 1560
	return New-VefgBannerBitmap -Width $width -Height $height
}

New-Item -ItemType Directory -Path $outDir -Force | Out-Null

$icon256 = New-VefgIconBitmap 256
$icon128 = New-VefgIconBitmap 128
Save-VefgBitmap $icon256 ( Join-Path $outDir 'icon-256x256.png' )
Save-VefgBitmap $icon128 ( Join-Path $outDir 'icon-128x128.png' )

$banner1544 = New-VefgBannerBitmap 1544 500
$banner772  = New-VefgBannerBitmap 772 250
Save-VefgBitmap $banner1544 ( Join-Path $outDir 'banner-1544x500.png' )
Save-VefgBitmap $banner772 ( Join-Path $outDir 'banner-772x250.png' )

$cover = New-VefgCoverBitmap
Save-VefgBitmap $cover ( Join-Path $outDir 'marketplace-cover.png' )

# WordPress plugin directory "thumbnail" (icon at 128px is the listing tile).
Copy-Item -Path ( Join-Path $outDir 'icon-256x256.png' ) -Destination ( Join-Path $outDir 'plugin-icon.png' ) -Force
Copy-Item -Path ( Join-Path $outDir 'banner-772x250.png' ) -Destination ( Join-Path $outDir 'plugin-banner.png' ) -Force

$icon256.Dispose()
$icon128.Dispose()
$banner1544.Dispose()
$banner772.Dispose()
$cover.Dispose()

Write-Host "Rendered $Variant brand assets -> $outDir"
