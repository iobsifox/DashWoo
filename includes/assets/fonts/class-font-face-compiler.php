<?php
/**
 * Builds the local @font-face stylesheet.
 *
 * @package DashWoo
 */

namespace DashWoo\Assets\Fonts;

use DashWoo\Assets\Storage;
use DashWoo\Support\Filesystem;

defined( 'ABSPATH' ) || exit;

/**
 * @font-face compiler.
 */
final class Font_Face_Compiler {

	/**
	 * Quote a family name for CSS.
	 *
	 * @param string $family Family.
	 * @return string
	 */
	public static function quote( $family ) {
		$family = trim( (string) $family );

		if ( '' === $family ) {
			return '""';
		}
		if ( '"' === $family[0] ) {
			return $family;
		}

		return '"' . str_replace( '"', '', $family ) . '"';
	}

	/**
	 * One @font-face block.
	 *
	 * @param array<string,mixed> $args family, url, weight, style, unicode_range, display.
	 * @return string
	 */
	public static function face( array $args ) {
		$family  = isset( $args['family'] ) ? $args['family'] : '';
		$url     = isset( $args['url'] ) ? $args['url'] : '';
		$weight  = isset( $args['weight'] ) ? (string) $args['weight'] : '400';
		$style   = isset( $args['style'] ) ? (string) $args['style'] : 'normal';
		$display = isset( $args['display'] ) ? (string) $args['display'] : 'swap';
		$range   = isset( $args['unicode_range'] ) ? trim( (string) $args['unicode_range'] ) : '';

		if ( '' === $family || '' === $url ) {
			return '';
		}

		$lines = array(
			'@font-face {',
			'  font-family: ' . self::quote( $family ) . ';',
			'  font-style: ' . $style . ';',
			'  font-weight: ' . $weight . ';',
			'  font-display: ' . $display . ';',
			'  src: url("' . $url . '") format("woff2");',
		);

		if ( '' !== $range ) {
			$lines[] = '  unicode-range: ' . $range . ';';
		}

		$lines[] = '}';

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Full stylesheet for a set of registry rows.
	 *
	 * @param array<int,array<string,mixed>> $rows  Registry rows (type=font).
	 * @param array<string,mixed>            $args  display, unicode_range.
	 * @return string
	 */
	public static function stylesheet( array $rows, array $args = array() ) {
		$display       = isset( $args['display'] ) ? (string) $args['display'] : 'swap';
		$keep_range    = ! empty( $args['unicode_range'] );
		$chunks        = array();
		$header        = "/* DashWoo local fonts - generated, do not edit. No external requests. */\n";

		foreach ( $rows as $row ) {
			if ( isset( $row['status'] ) && 'active' !== $row['status'] ) {
				continue;
			}

			$family = isset( $row['meta']['family'] ) && '' !== $row['meta']['family']
				? $row['meta']['family']
				: ( $row['label'] ?? $row['slug'] ?? '' );

			foreach ( (array) ( $row['meta']['faces'] ?? array() ) as $face ) {
				$chunks[] = self::face(
					array(
						'family'        => $family,
						'url'           => isset( $face['url'] ) ? $face['url'] : '',
						'weight'        => isset( $face['weight'] ) ? $face['weight'] : '400',
						'style'         => isset( $face['style'] ) ? $face['style'] : 'normal',
						'unicode_range' => $keep_range ? ( $face['unicode_range'] ?? '' ) : '',
						'display'       => $display,
					)
				);
			}
		}

		return $header . implode( "\n", array_filter( $chunks ) );
	}

	/**
	 * Compile + write to uploads/dashwoo/fonts/dw-fonts.css.
	 *
	 * @param array<int,array<string,mixed>> $rows Registry rows.
	 * @return array{path:string,url:string,hash:string,bytes:int}
	 */
	public static function compile( array $rows ) {
		$css = self::stylesheet(
			$rows,
			array(
				'display'       => dashwoo_get_setting( 'typography.font_display', 'swap' ),
				'unicode_range' => dashwoo_is_on( 'fonts_google.unicode_range' ),
			)
		);

		$storage = Storage::instance();
		$path    = $storage->path( 'fonts' ) . 'dw-fonts.css';

		Filesystem::put( $path, $css );

		return array(
			'path'  => $path,
			'url'   => $storage->url( 'fonts' ) . 'dw-fonts.css',
			'hash'  => substr( md5( $css ), 0, 12 ),
			'bytes' => strlen( $css ),
		);
	}
}
