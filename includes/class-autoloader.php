<?php
/**
 * PSR-4-ish autoloader for the DashWoo namespace.
 *
 * DashWoo\Assets\Fonts\Google_Fonts_Provider
 *   -> includes/assets/fonts/class-google-fonts-provider.php
 * DashWoo\Compatibility\Adapter_Interface
 *   -> includes/compatibility/interface-adapter.php
 * DashWoo\Compatibility\Abstract_Adapter
 *   -> includes/compatibility/abstract-adapter.php
 *
 * @package DashWoo
 */

namespace DashWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class map based autoloader.
 */
final class Autoloader {

	/**
	 * Root namespace.
	 */
	const PREFIX = 'DashWoo\\';

	/**
	 * Namespace segments that do not follow snake_case naming.
	 *
	 * @var array<string,string>
	 */
	private static $dir_aliases = array(
		'WooCommerce'  => 'woocommerce',
		'DesignSystem' => 'design-system',
		'Api'          => 'api',
		'Rest'         => 'rest',
	);

	/**
	 * Explicit overrides (namespace => relative path under includes/).
	 *
	 * @var array<string,string>
	 */
	private static $map = array(
		'DashWoo\\Plugin'      => 'class-plugin.php',
		'DashWoo\\Activator'   => 'class-activator.php',
		'DashWoo\\Deactivator' => 'class-deactivator.php',
	);

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * PSR-4-ish path guesser.
	 *
	 * @param string $class Fully qualified class name.
	 * @return string Relative path or empty string.
	 */
	public static function path_for( $class ) {
		if ( isset( self::$map[ $class ] ) ) {
			return self::$map[ $class ];
		}

		if ( 0 !== strpos( $class, self::PREFIX ) ) {
			return '';
		}

		$relative = substr( $class, strlen( self::PREFIX ) );
		$parts    = explode( '\\', $relative );
		$class_n  = array_pop( $parts );

		$file = self::file_name_for( $class_n );

		$dirs = array();
		foreach ( $parts as $part ) {
			$dirs[] = self::dir_slug( $part );
		}

		return $dirs
			? implode( '/', $dirs ) . '/' . $file
			: $file;
	}

	/**
	 * Class name => file name.
	 *
	 * @param string $class_name Last namespace segment.
	 * @return string
	 */
	public static function file_name_for( $class_name ) {
		if ( 0 === strpos( $class_name, 'Interface_' ) ) {
			return 'interface-' . self::slug( substr( $class_name, 10 ) ) . '.php';
		}
		if ( 0 === strpos( $class_name, 'Abstract_' ) ) {
			return 'abstract-' . self::slug( substr( $class_name, 9 ) ) . '.php';
		}
		if ( 0 === strpos( $class_name, 'Trait_' ) ) {
			return 'trait-' . self::slug( substr( $class_name, 6 ) ) . '.php';
		}
		return 'class-' . self::slug( $class_name ) . '.php';
	}

	/**
	 * Namespace segment => directory name.
	 *
	 * @param string $value Namespace segment.
	 * @return string
	 */
	public static function dir_slug( $value ) {
		if ( isset( self::$dir_aliases[ $value ] ) ) {
			return self::$dir_aliases[ $value ];
		}

		return strtolower( (string) $value );
	}

	/**
	 * CamelCase / snake_case => kebab-case.
	 *
	 * @param string $value Raw segment.
	 * @return string
	 */
	public static function slug( $value ) {
		$value = (string) $value;

		if ( false === strpos( $value, '_' ) ) {
			// Pure camelCase class name: split it (SvgSanitizer -> svg-sanitizer).
			$value = preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $value );
		} else {
			// WordPress naming convention: underscores are the separators, and the
			// inner capitals are part of the word (WordPress_Adapter -> wordpress-adapter).
			$value = str_replace( '_', '-', $value );
		}

		return strtolower( trim( $value, '-' ) );
	}

	/**
	 * Load a class file when it exists.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function load( $class ) {
		if ( 0 !== strpos( $class, self::PREFIX ) ) {
			return;
		}

		$relative = self::path_for( $class );
		if ( '' === $relative ) {
			return;
		}

		$file = DASHWOO_INCLUDES . $relative;
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
