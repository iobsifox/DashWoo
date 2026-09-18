<?php
/**
 * Schema-driven sanitisation.
 *
 * Every value that reaches the option must pass through here: the admin form,
 * the REST API and the import/export path all use the same rules, so a hostile
 * payload cannot smuggle anything into the database.
 *
 * @package DashWoo
 */

namespace DashWoo\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizer.
 */
final class Sanitizer {

	/**
	 * Sanitize one field value.
	 *
	 * @param array<string,mixed> $field Field definition.
	 * @param mixed               $value Raw value.
	 * @param mixed               $fallback Value to use when the input is invalid.
	 * @return mixed
	 */
	public static function value( array $field, $value, $fallback = null ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		switch ( $type ) {
			case 'toggle':
				return self::to_bool( $value );

			case 'number':
				return self::number( $field, $value, $fallback );

			case 'color':
				return self::color( $field, $value, $fallback );

			case 'select':
				return self::option( $field, $value, $fallback );

			case 'multi_select':
				return self::options( $field, $value, $fallback );

			case 'font':
				return self::font( $value, $fallback );

			case 'code':
				return is_string( $value ) ? $value : (string) $fallback;

			case 'textarea':
				return self::textarea( $value );

			case 'text':
			default:
				return self::text( $value, $fallback );
		}
	}

	/**
	 * Checkbox semantics: anything truthy-ish becomes true.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'yes', 'on' ), true );
		}

		return (bool) $value;
	}

	/**
	 * Numeric field: cast, then clamp into [min, max].
	 *
	 * @param array<string,mixed> $field Field.
	 * @param mixed               $value Raw value.
	 * @param mixed               $fallback Fallback.
	 * @return float|int
	 */
	public static function number( array $field, $value, $fallback = null ) {
		if ( ! is_numeric( $value ) ) {
			$value = $fallback;
		}
		if ( ! is_numeric( $value ) ) {
			$value = isset( $field['default'] ) ? $field['default'] : 0;
		}

		$number = (float) $value;

		if ( isset( $field['min'] ) && $number < (float) $field['min'] ) {
			$number = (float) $field['min'];
		}
		if ( isset( $field['max'] ) && $number > (float) $field['max'] ) {
			$number = (float) $field['max'];
		}

		$step = isset( $field['step'] ) ? (float) $field['step'] : 1.0;

		// Integers stay integers, decimals keep their precision.
		if ( $step >= 1 && floor( $step ) === $step && floor( $number ) === $number ) {
			return (int) $number;
		}

		return round( $number, 4 );
	}

	/**
	 * Hex / rgba / currentColor colour.
	 *
	 * @param array<string,mixed> $field Field.
	 * @param mixed               $value Raw value.
	 * @param mixed               $fallback Fallback.
	 * @return string
	 */
	public static function color( array $field, $value, $fallback = null ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( 'currentColor' === $value || 'transparent' === $value || 'inherit' === $value ) {
			return $value;
		}

		$hex = sanitize_hex_color( $value );
		if ( $hex ) {
			return $hex;
		}

		if ( preg_match( '/^rgba?\(\s*[0-9]{1,3}\s*,\s*[0-9]{1,3}\s*,\s*[0-9]{1,3}\s*(,\s*(0|1|0?\.[0-9]+)\s*)?\)$/i', $value ) ) {
			return preg_replace( '/\s+/', '', $value );
		}

		if ( is_string( $fallback ) && '' !== $fallback ) {
			return $fallback;
		}

		return isset( $field['default'] ) ? (string) $field['default'] : '';
	}

	/**
	 * Select: value must exist in the option map.
	 *
	 * @param array<string,mixed> $field Field.
	 * @param mixed               $value Raw value.
	 * @param mixed               $fallback Fallback.
	 * @return string
	 */
	public static function option( array $field, $value, $fallback = null ) {
		$value   = is_scalar( $value ) ? (string) $value : '';
		$options = isset( $field['options'] ) ? (array) $field['options'] : array();

		if ( array_key_exists( $value, $options ) ) {
			return $value;
		}

		if ( null !== $fallback && is_scalar( $fallback ) && array_key_exists( (string) $fallback, $options ) ) {
			return (string) $fallback;
		}

		return isset( $field['default'] ) ? (string) $field['default'] : '';
	}

	/**
	 * Multi-select: keep only allowed values, de-duplicated.
	 *
	 * @param array<string,mixed> $field Field.
	 * @param mixed               $value Raw value.
	 * @param mixed               $fallback Fallback.
	 * @return array<int,string>
	 */
	public static function options( array $field, $value, $fallback = null ) {
		if ( is_string( $value ) ) {
			$value = array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' );
		}
		if ( ! is_array( $value ) ) {
			$value = is_array( $fallback ) ? $fallback : array();
		}

		$options = isset( $field['options'] ) ? (array) $field['options'] : array();
		$out     = array();

		foreach ( $value as $item ) {
			$item = is_scalar( $item ) ? (string) $item : '';
			if ( '' !== $item && array_key_exists( $item, $options ) && ! in_array( $item, $out, true ) ) {
				$out[] = $item;
			}
		}

		return array_values( $out );
	}

	/**
	 * Font family name.
	 *
	 * @param mixed  $value Raw value.
	 * @param mixed  $fallback Fallback.
	 * @return string
	 */
	public static function font( $value, $fallback = null ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';

		// Allow-list: letters, digits, spaces, dash and underscore plus the Arabic
		// block (Persian family names). Anything else - quotes, braces, parens,
		// semicolons - means the value is hostile, so the whole value is rejected
		// instead of being "cleaned" into something unexpected.
		if ( '' !== $value && ! preg_match( '/^[A-Za-z0-9 _\-\x{0600}-\x{06FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+$/u', $value ) ) {
			$value = '';
		}

		if ( '' === $value ) {
			return is_scalar( $fallback ) ? (string) $fallback : '';
		}

		return preg_replace( '/\s{2,}/', ' ', $value );
	}

	/**
	 * Plain text (tags stripped).
	 *
	 * @param mixed $value Raw value.
	 * @param mixed $fallback Fallback.
	 * @return string
	 */
	public static function text( $value, $fallback = null ) {
		$value = is_scalar( $value ) ? (string) $value : '';

		if ( '' === trim( $value ) ) {
			return is_scalar( $fallback ) ? (string) $fallback : '';
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Rich text.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function textarea( $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';

		return wp_kses_post( $value );
	}
}
