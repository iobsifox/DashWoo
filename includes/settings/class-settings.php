<?php
/**
 * Settings manager: schema-backed, one autoloaded option.
 *
 * @package DashWoo
 */

namespace DashWoo\Settings;

use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Settings store.
 */
final class Settings {

	const OPTION = 'dashwoo_settings';

	/**
	 * Singleton.
	 *
	 * @var Settings|null
	 */
	private static $instance = null;

	/**
	 * Per-request cache.
	 *
	 * @var array<string,mixed>|null
	 */
	private $cache = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Settings
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'update_option_' . self::OPTION, array( $this, 'on_update' ), 10, 0 );
	}

	/**
	 * Invalidate caches when the option changes.
	 *
	 * @return void
	 */
	public function on_update() {
		$this->cache = null;
		do_action( 'dashwoo_settings_changed' );
	}

	/**
	 * Schema defaults.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function defaults() {
		return Sections::defaults();
	}

	/**
	 * Deep merge of stored values over the defaults.
	 *
	 * @param array<string,mixed> $defaults Defaults.
	 * @param array<string,mixed> $stored   Stored values.
	 * @return array<string,mixed>
	 */
	public static function merge( array $defaults, array $stored ) {
		$out = $defaults;

		foreach ( $stored as $key => $value ) {
			if ( is_array( $value ) && isset( $out[ $key ] ) && is_array( $out[ $key ] ) ) {
				$out[ $key ] = self::merge( $out[ $key ], $value );
				continue;
			}
			$out[ $key ] = $value;
		}

		return $out;
	}

	/**
	 * All settings (defaults merged with stored, sanitized shape).
	 *
	 * @return array<string,mixed>
	 */
	public function all() {
		if ( null === $this->cache ) {
			$stored      = get_option( self::OPTION, array() );
			$this->cache = self::merge( self::defaults(), is_array( $stored ) ? $stored : array() );
		}

		return $this->cache;
	}

	/**
	 * Dot-path get: get( 'colors.primary' ).
	 *
	 * @param string $path    Dot path.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public function get( $path, $default = null ) {
		$value = $this->all();

		if ( '' === $path || null === $path ) {
			return $value;
		}

		foreach ( explode( '.', (string) $path ) as $segment ) {
			if ( is_array( $value ) && array_key_exists( $segment, $value ) ) {
				$value = $value[ $segment ];
				continue;
			}
			if ( is_object( $value ) && isset( $value->$segment ) ) {
				$value = $value->$segment;
				continue;
			}

			return $default;
		}

		return $value;
	}

	/**
	 * Boolean helper.
	 *
	 * @param string $path Dot path.
	 * @return bool
	 */
	public function is_on( $path ) {
		return Sanitizer::to_bool( $this->get( $path, false ) );
	}

	/**
	 * Save one section from raw input.
	 *
	 * @param string              $section Section key.
	 * @param array<string,mixed> $input   Raw input ($_POST or REST payload).
	 * @return array<string,mixed> The saved section values.
	 */
	public function save_section( $section, array $input, $partial = false ) {
		$sections = Sections::all();

		if ( ! isset( $sections[ $section ] ) ) {
			return array();
		}

		$current = $this->get( $section, array() );
		$saved   = array();

		foreach ( (array) ( $sections[ $section ]['fields'] ?? array() ) as $field ) {
			$key      = $field['key'];
			$fallback = isset( $current[ $key ] ) ? $current[ $key ] : ( $field['default'] ?? null );

			if ( ! array_key_exists( $key, $input ) ) {
				// A section form posts every field, so an absent checkbox is a
				// deliberate "off". A partial write (single field, REST) must
				// leave the fields it does not mention alone.
				$saved[ $key ] = ( ! $partial && 'toggle' === $field['type'] )
					? Sanitizer::value( $field, false, $fallback )
					: $fallback;
				continue;
			}

			$saved[ $key ] = Sanitizer::value( $field, $input[ $key ], $fallback );
		}

		$all                   = $this->all();
		$all[ $section ]       = $saved;
		$all['_meta']['revision']  = (int) $this->get( '_meta.revision', 0 ) + 1;
		$all['_meta']['updated_at'] = gmdate( 'c' );

		update_option( self::OPTION, $all, true );
		$this->cache = null;

		/**
		 * Fires after a section was persisted.
		 *
		 * @param string              $section Section key.
		 * @param array<string,mixed> $saved   Saved values.
		 */
		do_action( 'dashwoo_settings_saved', $section, $saved );

		Logger::instance()->info( 'Settings section saved', array( 'section' => $section ) );

		return $saved;
	}

	/**
	 * Replace everything with the schema defaults.
	 *
	 * @return array<string,mixed>
	 */
	/**
	 * Write a single field through the sanitizer.
	 *
	 * Used by the plugin itself (activation fallback, asset sync) so every write
	 * keeps the cache, the revision counter and the change events consistent -
	 * never call update_option( Settings::OPTION ) directly.
	 *
	 * @param string $section Section key.
	 * @param string $key     Field key.
	 * @param mixed  $value   New value.
	 * @return mixed Saved value (empty array when the section is unknown).
	 */
	public function set_value( $section, $key, $value ) {
		$sections = Sections::all();

		if ( ! isset( $sections[ $section ] ) ) {
			return array();
		}

		$saved = $this->save_section( $section, array( $key => $value ), true );

		return $saved[ $key ] ?? null;
	}

	public function reset_all() {
		$defaults = self::defaults();

		$defaults['_meta']['revision']   = (int) $this->get( '_meta.revision', 0 ) + 1;
		$defaults['_meta']['updated_at'] = gmdate( 'c' );

		update_option( self::OPTION, $defaults, true );
		$this->cache = null;

		do_action( 'dashwoo_settings_reset', 'all' );

		return $defaults;
	}

	/**
	 * Reset a single section.
	 *
	 * @param string $section Section key.
	 * @return array<string,mixed>
	 */
	public function reset_section( $section ) {
		$defaults = self::defaults();

		if ( ! isset( $defaults[ $section ] ) ) {
			return array();
		}

		$all                        = $this->all();
		$all[ $section ]            = $defaults[ $section ];
		$all['_meta']['revision']   = (int) $this->get( '_meta.revision', 0 ) + 1;
		$all['_meta']['updated_at'] = gmdate( 'c' );

		update_option( self::OPTION, $all, true );
		$this->cache = null;

		do_action( 'dashwoo_settings_reset', $section );

		return $defaults[ $section ];
	}

	/**
	 * Export payload.
	 *
	 * @return array<string,mixed>
	 */
	public function export() {
		return array(
			'plugin'     => 'dashwoo',
			'version'    => DASHWOO_VERSION,
			'schema'     => array(
				'sections' => count( Sections::all() ),
				'fields'   => Sections::count_fields(),
			),
			'exported'   => gmdate( 'c' ),
			'settings'   => $this->all(),
		);
	}

	/**
	 * Export as JSON.
	 *
	 * @return string
	 */
	public function to_json() {
		return (string) wp_json_encode( $this->export() );
	}

	/**
	 * Import a JSON payload (full replace, schema-sanitized).
	 *
	 * @param string $json JSON string.
	 * @return array{ok:bool,error?:string,sections:int}
	 */
	public function import_json( $json ) {
		$decoded = json_decode( (string) $json, true );

		if ( ! is_array( $decoded ) ) {
			return array(
				'ok'       => false,
				'imported' => false,
				'reason'   => 'invalid_json',
			);
		}

		$payload = isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ? $decoded['settings'] : $decoded;

		// Only keys that exist in the schema may be imported; a payload without a
		// single known section is rejected instead of silently "succeeding".
		$known = array_intersect_key( $payload, Sections::all() );

		if ( ! $known ) {
			return array(
				'ok'       => false,
				'imported' => false,
				'reason'   => 'no_sections',
			);
		}

		$all  = $this->all();
		$done = 0;

		foreach ( Sections::all() as $section => $definition ) {
			if ( ! isset( $payload[ $section ] ) || ! is_array( $payload[ $section ] ) ) {
				continue;
			}

			$raw = $payload[ $section ];
			foreach ( (array) ( $definition['fields'] ?? array() ) as $field ) {
				$key = $field['key'];

				if ( 'toggle' === $field['type'] ) {
					$all[ $section ][ $key ] = Sanitizer::value( $field, $raw[ $key ] ?? false, $field['default'] ?? false );
					continue;
				}

				if ( ! array_key_exists( $key, $raw ) ) {
					continue;
				}

				$all[ $section ][ $key ] = Sanitizer::value( $field, $raw[ $key ], $field['default'] ?? null );
			}
			$done++;
		}

		$all['_meta']['revision']   = (int) $this->get( '_meta.revision', 0 ) + 1;
		$all['_meta']['updated_at'] = gmdate( 'c' );

		update_option( self::OPTION, $all, true );
		$this->cache = null;

		do_action( 'dashwoo_settings_imported', $done );

		return array(
			'ok'       => true,
			'imported' => true,
			'sections' => $done,
			'revision' => (int) $all['_meta']['revision'],
		);
	}

	/**
	 * Revision counter (used as a cache-buster for generated CSS).
	 *
	 * @return int
	 */
	public function revision() {
		return (int) $this->get( '_meta.revision', 0 );
	}

	/**
	 * Reset for tests.
	 *
	 * @return void
	 */
	public function reset() {
		$this->cache = null;
	}
}
