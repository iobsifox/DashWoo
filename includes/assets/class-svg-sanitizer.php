<?php
/**
 * SVG sanitizer: an SVG is XML that can carry scripts, so it is never trusted.
 *
 * @package DashWoo
 */

namespace DashWoo\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Allow-list based SVG cleaner.
 */
final class Svg_Sanitizer {

	/**
	 * Elements that may survive.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_tags() {
		return array(
			'svg', 'g', 'title', 'desc', 'defs', 'symbol', 'use', 'switch',
			'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon',
			'text', 'tspan', 'textPath', 'linearGradient', 'radialGradient', 'stop',
			'clipPath', 'mask', 'pattern', 'filter', 'feGaussianBlur', 'feOffset',
			'feBlend', 'feColorMatrix', 'feComposite', 'feFlood', 'feMerge',
			'feMergeNode', 'feMorphology', 'feTurbulence', 'feDisplacementMap',
		);
	}

	/**
	 * Attributes that may survive.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_attributes() {
		return array(
			'id', 'class', 'style', 'fill', 'fill-opacity', 'fill-rule', 'stroke',
			'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray',
			'stroke-dashoffset', 'stroke-opacity', 'stroke-miterlimit', 'opacity',
			'transform', 'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'x2',
			'y1', 'y2', 'points', 'width', 'height', 'viewBox', 'preserveAspectRatio',
			'xmlns', 'xmlns:xlink', 'version', 'offset', 'stop-color', 'stop-opacity',
			'gradientUnits', 'gradientTransform', 'patternUnits', 'clipPathUnits',
			'maskUnits', 'filterUnits', 'result', 'in', 'in2', 'stdDeviation',
			'scale', 'dx', 'dy', 'values', 'baseFrequency', 'numOctaves', 'type',
			'mode', 'href', 'xlink:href', 'aria-hidden', 'role', 'focusable',
		);
	}

	/**
	 * Clean an SVG string.
	 *
	 * @param string $svg      Raw SVG.
	 * @param bool   $strip_ids Remove id/class attributes.
	 * @return string
	 */
	public static function sanitize( $svg, $strip_ids = true ) {
		$svg = (string) $svg;

		// 1. Drop anything that is not SVG content at all.
		if ( false === stripos( $svg, '<svg' ) ) {
			return '';
		}

		// 2. Remove comments, CDATA, processing instructions and doctypes.
		$svg = preg_replace( '/<!--.*?-->/s', '', $svg );
		$svg = preg_replace( '/<!\[CDATA\[.*?\]\]>/s', '', (string) $svg );
		$svg = preg_replace( '/<\?.*?\?>/s', '', (string) $svg );
		$svg = preg_replace( '/<!DOCTYPE[^>]*>/i', '', (string) $svg );

		// 3. Nuke dangerous elements together with their content.
		$svg = preg_replace( '#<(script|style|foreignObject|iframe|object|embed|audio|video|animate|set|handler)\b.*?</\1>#is', '', (string) $svg );
		$svg = preg_replace( '#<(script|iframe|object|embed|use)\b[^>]*/?>#is', '', (string) $svg );

		// 4. Drop every on* event handler and javascript:/data: payloads.
		$svg = preg_replace( '/\son[a-z]+\s*=\s*"[^"]*"/i', '', (string) $svg );
		$svg = preg_replace( "/\son[a-z]+\s*=\s*'[^']*'/i", '', (string) $svg );
		$svg = preg_replace( '/\son[a-z]+\s*=\s*[^\s>]+/i', '', (string) $svg );
		$svg = preg_replace( '/(href|xlink:href)\s*=\s*("|\')\s*(javascript|data|vbscript)\s*:[^"\']*("|\')/i', '', (string) $svg );

		// 5. Run the WordPress allow-list over the remaining markup.
		if ( function_exists( 'wp_kses' ) ) {
			$svg = wp_kses( $svg, self::kses_map() );
		}

		if ( $strip_ids ) {
			$svg = preg_replace( '/\s(id|class)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', (string) $svg );
		}

		$svg = trim( (string) $svg );

		// 6. Guarantee a valid root + viewBox.
		if ( 0 !== strpos( $svg, '<svg' ) ) {
			return '';
		}

		return $svg;
	}

	/**
	 * wp_kses map built from the allow-lists.
	 *
	 * @return array<string,array<string,bool>>
	 */
	public static function kses_map() {
		$map = array();

		foreach ( self::allowed_tags() as $tag ) {
			$attrs = array();
			foreach ( self::allowed_attributes() as $attr ) {
				$attrs[ $attr ] = true;
			}
			$map[ strtolower( $tag ) ] = $attrs;
		}

		return $map;
	}

	/**
	 * Quick safety verdict, used by the UI and the tests.
	 *
	 * @param string $svg SVG markup.
	 * @return array{safe:bool,issues:array<int,string>}
	 */
	public static function audit( $svg ) {
		$svg    = (string) $svg;
		$issues = array();

		$patterns = array(
			'script_tag'   => '#<script#i',
			'event_attr'   => '/\son[a-z]+\s*=/i',
			'js_protocol'  => '/javascript\s*:/i',
			'foreign_obj'  => '#<foreignObject#i',
			'iframe'       => '#<iframe#i',
			'entity'       => '/<!ENTITY/i',
			'doctype'      => '/<!DOCTYPE/i',
		);

		foreach ( $patterns as $key => $pattern ) {
			if ( preg_match( $pattern, $svg ) ) {
				$issues[] = $key;
			}
		}

		return array(
			'safe'   => ! $issues,
			'issues' => $issues,
		);
	}

	/**
	 * Wrap a sanitized SVG so it can be rendered inline with a controllable colour.
	 *
	 * @param string $svg   Sanitized SVG.
	 * @param string $color CSS colour (currentColor by default).
	 * @param int    $size  Size in px.
	 * @return string
	 */
	public static function inline( $svg, $color = 'currentColor', $size = 24 ) {
		$svg = self::sanitize( $svg );

		if ( '' === $svg ) {
			return '';
		}

		$size  = max( 8, min( 512, (int) $size ) );
		$attrs = sprintf(
			' class="dw-svg-icon" aria-hidden="true" focusable="false" width="%1$d" height="%1$d" fill="%2$s"',
			$size,
			esc_attr( $color )
		);

		$svg = preg_replace( '/<svg\b/i', '<svg' . $attrs, $svg, 1 );

		if ( false === strpos( $svg, 'viewBox' ) && false === strpos( $svg, 'width=' ) ) {
			$svg = preg_replace( '/<svg\b/i', '<svg width="' . $size . '" height="' . $size . '"', $svg, 1 );
		}

		return (string) $svg;
	}
}
