<?php
/**
 * Rasterize plugin brand assets for marketplace listings (e.g. Envato CodeCanyon).
 *
 * Envato cover: 3:2 aspect ratio — recommended 2340×1560px, minimum 1170×780px
 * (see Item Presentation Requirements in Envato Author Help Center).
 *
 * Run from repo root: php tools/generate-brand-pngs.php
 *
 * @package WP_Span_Checker
 */

if ( ! extension_loaded( 'gd' ) ) {
	fwrite( STDERR, "PHP GD extension required.\n" );
	exit( 1 );
}

$root = dirname( __DIR__ );
$out  = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'brand' . DIRECTORY_SEPARATOR . 'envato';

if ( ! is_dir( $out ) && ! mkdir( $out, 0755, true ) && ! is_dir( $out ) ) {
	fwrite( STDERR, "Cannot create {$out}\n" );
	exit( 1 );
}

/**
 * @param array{0:float,1:float,2:float} $a RGB 0-255
 * @param array{0:float,1:float,2:float} $b RGB 0-255
 * @return array{0:int,1:int,2:int}
 */
function wsc_lerp_rgb( array $a, array $b, float $t ): array {
	$t = max( 0.0, min( 1.0, $t ) );
	return array(
		(int) round( $a[0] + ( $b[0] - $a[0] ) * $t ),
		(int) round( $a[1] + ( $b[1] - $a[1] ) * $t ),
		(int) round( $a[2] + ( $b[2] - $a[2] ) * $t ),
	);
}

function wsc_inside_round_rect( float $x, float $y, int $w, int $h, float $r ): bool {
	if ( $x < 0 || $y < 0 || $x >= $w || $y >= $h ) {
		return false;
	}
	if ( $x < $r && $y < $r ) {
		return hypot( $r - $x, $r - $y ) <= $r;
	}
	if ( $x >= $w - $r && $y < $r ) {
		return hypot( $x - ( $w - $r ), $r - $y ) <= $r;
	}
	if ( $x < $r && $y >= $h - $r ) {
		return hypot( $r - $x, $y - ( $h - $r ) ) <= $r;
	}
	if ( $x >= $w - $r && $y >= $h - $r ) {
		return hypot( $x - ( $w - $r ), $y - ( $h - $r ) ) <= $r;
	}
	return true;
}

/**
 * @param resource|\GdImage $im
 */
function wsc_alloc( $im, int $r, int $g, int $b, int $a = 0 ) {
	if ( function_exists( 'imagecolorallocatealpha' ) ) {
		return imagecolorallocatealpha( $im, $r, $g, $b, $a );
	}
	return imagecolorallocate( $im, $r, $g, $b );
}

/**
 * @param resource|\GdImage $im
 */
function wsc_fill_round_gradient( $im, int $w, int $h, float $r, array $tl, array $br ): void {
	for ( $y = 0; $y < $h; $y++ ) {
		for ( $x = 0; $x < $w; $x++ ) {
			if ( ! wsc_inside_round_rect( $x, $y, $w, $h, $r ) ) {
				continue;
			}
			$t   = ( ( $x / max( 1, $w - 1 ) ) + ( $y / max( 1, $h - 1 ) ) ) / 2.0;
			$rgb = wsc_lerp_rgb( $tl, $br, $t );
			$c   = wsc_alloc( $im, $rgb[0], $rgb[1], $rgb[2] );
			imagesetpixel( $im, $x, $y, $c );
		}
	}
}

/**
 * @return list<array{0:int,1:int}>
 */
function wsc_shield_points_256(): array {
	return array(
		array( 128, 52 ),
		array( 188, 78 ),
		array( 206, 128 ),
		array( 188, 178 ),
		array( 128, 212 ),
		array( 68, 178 ),
		array( 50, 128 ),
		array( 68, 78 ),
	);
}

/**
 * @param list<array{0:int,1:int}> $pts
 * @return list<array{0:int,1:int}>
 */
function wsc_scale_poly( array $pts, float $scale ): array {
	$o = array();
	foreach ( $pts as $p ) {
		$o[] = array( (int) round( $p[0] * $scale ), (int) round( $p[1] * $scale ) );
	}
	return $o;
}

/**
 * @param resource|\GdImage $im
 * @param list<array{0:int,1:int}> $pts
 */
function wsc_imagefilledpolygon_compat( $im, array $pts, int $color ): void {
	$flat = array();
	foreach ( $pts as $p ) {
		$flat[] = $p[0];
		$flat[] = $p[1];
	}
	$n = count( $pts );
	if ( PHP_VERSION_ID >= 80100 ) {
		imagefilledpolygon( $im, $flat, $color );
	} else {
		imagefilledpolygon( $im, $flat, $n, $color );
	}
}

/**
 * @return resource|\GdImage
 */
function wsc_render_icon( int $size ) {
	$im = imagecreatetruecolor( $size, $size );
	imagealphablending( $im, false );
	imagesavealpha( $im, true );
	$transparent = wsc_alloc( $im, 0, 0, 0, 127 );
	imagefill( $im, 0, 0, $transparent );
	imagealphablending( $im, true );

	$scale = $size / 256.0;
	$r     = 58.0 * $scale;

	$indigo = array( 79.0, 70.0, 229.0 );
	$violet = array( 99.0, 102.0, 241.0 );
	wsc_fill_round_gradient( $im, $size, $size, $r, $indigo, $violet );

	$white = wsc_alloc( $im, 252, 252, 254 );
	$pts   = wsc_scale_poly( wsc_shield_points_256(), $scale );
	wsc_imagefilledpolygon_compat( $im, $pts, $white );

	$cyan = wsc_alloc( $im, 6, 182, 212 );
	imagesetthickness( $im, (int) max( 3, round( 12 * $scale ) ) );

	$x1 = (int) round( 88 * $scale );
	$y1 = (int) round( 130 * $scale );
	$x2 = (int) round( 120 * $scale );
	$y2 = (int) round( 162 * $scale );
	$x3 = (int) round( 176 * $scale );
	$y3 = (int) round( 98 * $scale );
	imageline( $im, $x1, $y1, $x2, $y2, $cyan );
	imageline( $im, $x2, $y2, $x3, $y3, $cyan );
	imagesetthickness( $im, 1 );

	return $im;
}

/**
 * @return array{0:string,1:string}
 */
function wsc_pick_fonts(): array {
	$font_bold_candidates = array(
		'C:\\Windows\\Fonts\\segoeuib.ttf',
		'C:\\Windows\\Fonts\\arialbd.ttf',
		'/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
		'/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
	);
	$font_reg_candidates  = array(
		'C:\\Windows\\Fonts\\segoeui.ttf',
		'C:\\Windows\\Fonts\\arial.ttf',
		'/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
		'/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
	);
	$font_bold = '';
	$font_reg  = '';
	foreach ( $font_bold_candidates as $p ) {
		if ( is_readable( $p ) ) {
			$font_bold = $p;
			break;
		}
	}
	foreach ( $font_reg_candidates as $p ) {
		if ( is_readable( $p ) ) {
			$font_reg = $p;
			break;
		}
	}
	return array( $font_bold, $font_reg );
}

/**
 * Full-bleed indigo gradient used on marketplace raster assets.
 *
 * @param resource|\GdImage $im
 */
function wsc_fill_marketplace_gradient( $im, int $bw, int $bh ): void {
	$deep   = array( 49.0, 46.0, 129.0 );
	$mid    = array( 79.0, 70.0, 229.0 );
	$bright = array( 99.0, 102.0, 241.0 );

	for ( $y = 0; $y < $bh; $y++ ) {
		for ( $x = 0; $x < $bw; $x++ ) {
			$tx = $x / max( 1, $bw - 1 );
			$ty = $y / max( 1, $bh - 1 );
			$t  = ( $tx * 0.52 + $ty * 0.48 );
			$rgb = $t < 0.42
				? wsc_lerp_rgb( $deep, $mid, $t / 0.42 )
				: wsc_lerp_rgb( $mid, $bright, ( $t - 0.42 ) / 0.58 );
			$c = wsc_alloc( $im, $rgb[0], $rgb[1], $rgb[2] );
			imagesetpixel( $im, $x, $y, $c );
		}
	}
}

/**
 * Soft cyan highlight blob.
 *
 * @param resource|\GdImage $im
 */
function wsc_overlay_cyan_blob( $im, int $bw, int $bh, float $cx_ratio, float $cy_ratio, float $rad_ratio ): void {
	$cx  = (int) ( $bw * $cx_ratio );
	$cy  = (int) ( $bh * $cy_ratio );
	$rad = (int) ( min( $bw, $bh ) * $rad_ratio );
	for ( $yy = max( 0, $cy - $rad ); $yy < min( $bh, $cy + $rad ); $yy++ ) {
		for ( $xx = max( 0, $cx - $rad ); $xx < min( $bw, $cx + $rad ); $xx++ ) {
			$d = hypot( $xx - $cx, $yy - $cy ) / $rad;
			if ( $d > 1 ) {
				continue;
			}
			$a = ( 1 - $d ) * 0.12;
			if ( $a <= 0 ) {
				continue;
			}
			$idx = imagecolorat( $im, $xx, $yy );
			$r0  = ( $idx >> 16 ) & 0xFF;
			$g0  = ( $idx >> 8 ) & 0xFF;
			$b0  = $idx & 0xFF;
			$r1  = (int) round( $r0 + ( 6 - $r0 ) * $a );
			$g1  = (int) round( $g0 + ( 182 - $g0 ) * $a );
			$b1  = (int) round( $b0 + ( 212 - $b0 ) * $a );
			$c   = wsc_alloc( $im, $r1, $g1, $b1 );
			imagesetpixel( $im, $xx, $yy, $c );
		}
	}
}

/**
 * Envato-style 3:2 cover (full bleed, mark + short copy).
 *
 * @return resource|\GdImage
 */
function wsc_render_envato_cover( int $bw, int $bh ) {
	$im = imagecreatetruecolor( $bw, $bh );
	imagealphablending( $im, true );

	wsc_fill_marketplace_gradient( $im, $bw, $bh );
	wsc_overlay_cyan_blob( $im, $bw, $bh, 0.84, 0.2, 0.35 );

	$mark_size = (int) round( min( $bh * 0.5, $bw * 0.22 ) );
	$icon      = wsc_render_icon( $mark_size );
	$dx        = (int) round( $bw * 0.045 );
	$dy        = (int) round( ( $bh - $mark_size ) / 2 );
	imagecopy( $im, $icon, $dx, $dy, 0, 0, $mark_size, $mark_size );
	imagedestroy( $icon );

	list( $font_bold, $font_reg ) = wsc_pick_fonts();

	$tx      = (int) round( $dx + $mark_size + $bw * 0.032 );
	$scale_f = $bw / 2340.0;

	if ( is_readable( $font_bold ) ) {
		$title = 'WP Span Checker';
		$sz    = (int) max( 28, round( 92 * $scale_f ) );
		$color = wsc_alloc( $im, 255, 255, 255 );
		imagettftext( $im, $sz, 0, $tx, (int) round( $bh * 0.46 ), $color, $font_bold, $title );
	}

	if ( is_readable( $font_reg ) ) {
		$sub  = 'WordPress plugin — email domain validation & spam protection';
		$sz2  = (int) max( 14, round( 40 * $scale_f ) );
		$col2 = wsc_alloc( $im, 199, 210, 254 );
		imagettftext( $im, $sz2, 0, $tx, (int) round( $bh * 0.54 ), $col2, $font_reg, $sub );

		$sub2 = 'Disposable lists · HTTPS checks · Optional VirusTotal & Google Web Risk';
		$sz3  = (int) max( 12, round( 30 * $scale_f ) );
		$col3 = wsc_alloc( $im, 165, 180, 252 );
		imagettftext( $im, $sz3, 0, $tx, (int) round( $bh * 0.60 ), $col3, $font_reg, $sub2 );
	}

	return $im;
}

/**
 * Square image (e.g. 1080×1080) — centered mark and title.
 *
 * @return resource|\GdImage
 */
function wsc_render_social_square( int $s ) {
	$im = imagecreatetruecolor( $s, $s );
	imagealphablending( $im, true );
	wsc_fill_marketplace_gradient( $im, $s, $s );
	wsc_overlay_cyan_blob( $im, $s, $s, 0.88, 0.12, 0.42 );

	$mark = (int) round( $s * 0.38 );
	$icon = wsc_render_icon( $mark );
	$dx   = (int) round( ( $s - $mark ) / 2 );
	$dy   = (int) round( $s * 0.16 );
	imagecopy( $im, $icon, $dx, $dy, 0, 0, $mark, $mark );
	imagedestroy( $icon );

	list( $font_bold, $font_reg ) = wsc_pick_fonts();

	if ( is_readable( $font_bold ) ) {
		$title = 'WP Span Checker';
		$sz    = (int) max( 18, round( $s * 0.062 ) );
		$box   = imagettfbbox( $sz, 0, $font_bold, $title );
		$tw    = (int) ( $box[2] - $box[0] );
		$tx    = (int) round( ( $s - $tw ) / 2 );
		$ty    = (int) round( $dy + $mark + $s * 0.11 );
		imagettftext( $im, $sz, 0, $tx, $ty, wsc_alloc( $im, 255, 255, 255 ), $font_bold, $title );
	}

	if ( is_readable( $font_reg ) ) {
		$sub = 'Email domain validation for WordPress';
		$sz2 = (int) max( 12, round( $s * 0.028 ) );
		$box = imagettfbbox( $sz2, 0, $font_reg, $sub );
		$tw  = (int) ( $box[2] - $box[0] );
		$tx  = (int) round( ( $s - $tw ) / 2 );
		$ty  = (int) round( $dy + $mark + $s * 0.19 );
		imagettftext( $im, $sz2, 0, $tx, $ty, wsc_alloc( $im, 199, 210, 254 ), $font_reg, $sub );
	}

	return $im;
}

$icon512 = wsc_render_icon( 512 );
imagepng( $icon512, $out . DIRECTORY_SEPARATOR . 'icon-512x512.png' );
imagedestroy( $icon512 );

$cover_full = wsc_render_envato_cover( 2340, 1560 );
imagepng( $cover_full, $out . DIRECTORY_SEPARATOR . 'cover-2340x1560.png' );
imagedestroy( $cover_full );

$cover_min = wsc_render_envato_cover( 1170, 780 );
imagepng( $cover_min, $out . DIRECTORY_SEPARATOR . 'cover-1170x780.png' );
imagedestroy( $cover_min );

$social = wsc_render_social_square( 1080 );
imagepng( $social, $out . DIRECTORY_SEPARATOR . 'social-1080x1080.png' );
imagedestroy( $social );

list( $font_bold, $font_reg ) = wsc_pick_fonts();

echo "Wrote Envato / marketplace PNGs to {$out}\n";
if ( '' === $font_bold || '' === $font_reg ) {
	echo "(Warning: TrueType fonts not found; cover text may be missing. Install DejaVu or Liberation fonts on Linux.)\n";
}
