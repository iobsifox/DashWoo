<?php
/**
 * Two-tier cache: transients (values) + files (generated CSS/JS).
 *
 * @package DashWoo
 */

namespace DashWoo\Cache;

use DashWoo\Assets\Storage;
use DashWoo\Support\Filesystem;

defined( 'ABSPATH' ) || exit;

/**
 * Cache manager.
 */
final class Cache {

	const PREFIX = 'dashwoo_';

	/**
	 * Singleton.
	 *
	 * @var Cache|null
	 */
	private static $instance = null;

	/**
	 * In-request memo.
	 *
	 * @var array<string,mixed>
	 */
	private $memo = array();

	/**
	 * Singleton accessor.
	 *
	 * @return Cache
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
		add_action( 'dashwoo_cache_flush', array( $this, 'flush_all' ) );
	}

	/**
	 * Get a cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed|null
	 */
	public function get( $key ) {
		$key = $this->key( $key );

		if ( array_key_exists( $key, $this->memo ) ) {
			return $this->memo[ $key ];
		}

		$value = get_transient( $key );
		if ( false !== $value ) {
			$this->memo[ $key ] = $value;
		}

		return false === $value ? null : $value;
	}

	/**
	 * Store a value.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value.
	 * @param int    $ttl   TTL in seconds.
	 * @return bool
	 */
	public function set( $key, $value, $ttl = 3600 ) {
		$this->memo[ $this->key( $key ) ] = $value;

		return set_transient( $this->key( $key ), $value, (int) $ttl );
	}

	/**
	 * Delete a value.
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function delete( $key ) {
		unset( $this->memo[ $this->key( $key ) ] );

		return delete_transient( $this->key( $key ) );
	}

	/**
	 * Get-or-compute.
	 *
	 * @param string   $key      Cache key.
	 * @param int      $ttl      TTL in seconds.
	 * @param callable $callback Producer.
	 * @return mixed
	 */
	public function remember( $key, $ttl, $callback ) {
		$cached = $this->get( $key );

		if ( null !== $cached ) {
			return $cached;
		}

		$value = call_user_func( $callback );
		$this->set( $key, $value, $ttl );

		return $value;
	}

	/**
	 * Write a generated file into uploads/dashwoo/cache/.
	 *
	 * @param string $name     File name.
	 * @param string $contents Contents.
	 * @return array{path:string,url:string}|null
	 */
	public function put_file( $name, $contents ) {
		$name = sanitize_file_name( $name );
		$path = Storage::instance()->path( 'cache' ) . $name;

		if ( ! Filesystem::put( $path, $contents ) ) {
			return null;
		}

		return array(
			'path' => $path,
			'url'  => Storage::instance()->url( 'cache' ) . $name,
		);
	}

	/**
	 * Public URL of a generated file.
	 *
	 * @param string $name File name.
	 * @return string
	 */
	public function file_url( $name ) {
		return Storage::instance()->url( 'cache' ) . sanitize_file_name( $name );
	}

	/**
	 * Absolute path of a generated file.
	 *
	 * @param string $name File name.
	 * @return string
	 */
	public function file_path( $name ) {
		return Storage::instance()->path( 'cache' ) . sanitize_file_name( $name );
	}

	/**
	 * Delete generated files, optionally only those matching a prefix.
	 *
	 * @param string $prefix File name prefix.
	 * @return int Deleted count.
	 */
	public function purge_files( $prefix = '' ) {
		$dir  = Storage::instance()->path( 'cache' );
		$gone = 0;

		foreach ( Filesystem::list_files( $dir ) as $file ) {
			if ( '' !== $prefix && 0 !== strpos( basename( $file ), $prefix ) ) {
				continue;
			}
			Filesystem::delete( $file );
			$gone++;
		}

		return $gone;
	}

	/**
	 * Flush transients + generated files.
	 *
	 * @return int
	 */
	public function flush_all() {
		$this->memo = array();

		return $this->purge_files();
	}

	/**
	 * Cache key with prefix + site salt.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	public function key( $key ) {
		$key = strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '_', (string) $key ) );

		return self::PREFIX . substr( $key, 0, 40 ) . '_' . substr( md5( (string) get_option( 'dashwoo_cache_salt', '' ) . $key ), 0, 8 );
	}

	/**
	 * Reset for tests.
	 *
	 * @return void
	 */
	public function reset() {
		$this->memo = array();
	}
}
