<?php
/**
 * Asset storage: wp-content/uploads/dashwoo/{fonts,icons,images,svg,custom,cache}
 *
 * Architectural rule: nothing DashWoo generates lives inside the plugin folder,
 * because a plugin update wipes it. Uploads are the only update-safe location.
 *
 * @package DashWoo
 */

namespace DashWoo\Assets;

use DashWoo\Support\Filesystem;

defined( 'ABSPATH' ) || exit;

/**
 * Storage layout manager.
 */
final class Storage {

	const DIR = 'dashwoo';

	/**
	 * Allowed sub directories.
	 *
	 * @var array<int,string>
	 */
	private $types = array( 'fonts', 'icons', 'images', 'svg', 'custom', 'cache' );

	/**
	 * Singleton.
	 *
	 * @var Storage|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Storage
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Uploads root info (memoised).
	 *
	 * @return array<string,string>
	 */
	private function uploads() {
		static $cache = null;

		if ( null === $cache ) {
			$cache = wp_upload_dir();
		}

		return $cache;
	}

	/**
	 * Root directory of the DashWoo storage.
	 *
	 * @return string
	 */
	public function basedir() {
		$uploads = $this->uploads();
		$base    = trailingslashit( $uploads['basedir'] ) . self::DIR;

		/**
		 * Filter the DashWoo storage root.
		 *
		 * @param string $base Absolute path.
		 */
		return untrailingslashit( apply_filters( 'dashwoo_storage_dir', $base ) );
	}

	/**
	 * Root URL of the DashWoo storage.
	 *
	 * @return string
	 */
	public function baseurl() {
		$uploads = $this->uploads();
		$url     = trailingslashit( $uploads['baseurl'] ) . self::DIR;

		return untrailingslashit( apply_filters( 'dashwoo_storage_url', $url, $this->basedir() ) );
	}

	/**
	 * Known asset types.
	 *
	 * @return array<int,string>
	 */
	public function types() {
		return $this->types;
	}

	/**
	 * Absolute path of a sub directory (always with a trailing slash).
	 *
	 * @param string $type Sub directory.
	 * @return string
	 */
	public function path( $type = '' ) {
		$type = $this->clean_type( $type );
		$base = $this->basedir() . '/';

		return '' === $type ? $base : $base . $type . '/';
	}

	/**
	 * Public URL of a sub directory (always with a trailing slash).
	 *
	 * @param string $type Sub directory.
	 * @return string
	 */
	public function url( $type = '' ) {
		$type = $this->clean_type( $type );
		$base = $this->baseurl() . '/';

		return '' === $type ? $base : $base . $type . '/';
	}

	/**
	 * Reject traversal in the type segment.
	 *
	 * @param string $type Raw type.
	 * @return string
	 */
	public function clean_type( $type ) {
		$type = strtolower( trim( (string) $type ) );
		$type = preg_replace( '/[^a-z0-9_\-]/', '', $type );

		return in_array( $type, $this->types, true ) ? $type : '';
	}

	/**
	 * Create the whole tree + the security guards.
	 *
	 * @return array<string,bool> Map of type => created.
	 */
	public function ensure_tree() {
		$result = array();

		Filesystem::mkdir( $this->basedir() );

		foreach ( $this->types as $type ) {
			$result[ $type ] = Filesystem::mkdir( $this->path( $type ) );
		}

		Filesystem::put( $this->basedir() . '/.htaccess', $this->htaccess_rules() );
		Filesystem::put( $this->basedir() . '/index.php', "<?php\n// Silence is golden.\n" );
		Filesystem::put( $this->basedir() . '/web.config', $this->web_config() );
		Filesystem::put( $this->path( 'cache' ) . 'index.php', "<?php\n// Silence is golden.\n" );

		return $result;
	}

	/**
	 * Apache guard: no PHP execution, no directory listing, no server-side includes.
	 *
	 * @return string
	 */
	public function htaccess_rules() {
		return implode(
			"\n",
			array(
				'# DashWoo managed directory. Do not edit by hand.',
				'<IfModule mod_php.c>',
				'php_flag engine off',
				'</IfModule>',
				'<IfModule mod_php7.c>',
				'php_flag engine off',
				'</IfModule>',
				'<IfModule mod_php8.c>',
				'php_flag engine off',
				'</IfModule>',
				'AddType text/plain .php .phtml .php3 .php4 .php5 .php7 .php8 .phar .shtml .cgi .pl .py .sh',
				'Options -Indexes -ExecCGI',
				'<FilesMatch "\.(php|phtml|php3|php4|php5|php7|php8|phar|cgi|pl|py|sh|shtml)$">',
				'  <IfModule mod_authz_core.c>',
				'    Require all denied',
				'  </IfModule>',
				'  <IfModule !mod_authz_core.c>',
				'    Order allow,deny',
				'    Deny from all',
				'  </IfModule>',
				'</FilesMatch>',
				'<IfModule mod_headers.c>',
				'  Header set X-Content-Type-Options "nosniff"',
				'</IfModule>',
				'',
			)
		);
	}

	/**
	 * IIS guard.
	 *
	 * @return string
	 */
	public function web_config() {
		return '<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <handlers>
      <clear />
      <add name="StaticFile" path="*" verb="*" modules="StaticFileModule" resourceType="Either" requireAccess="Read" />
    </handlers>
    <directoryBrowse enabled="false" />
  </system.webServer>
</configuration>
';
	}

	/**
	 * Storage statistics.
	 *
	 * @return array<string,mixed>
	 */
	public function stats() {
		$types = array();

		foreach ( $this->types as $type ) {
			$types[ $type ] = array(
				'path'  => $this->path( $type ),
				'bytes' => Filesystem::size( $this->path( $type ) ),
				'files' => count( Filesystem::list_files( $this->path( $type ) ) ),
			);
		}

		return array(
			'basedir'  => $this->basedir(),
			'baseurl'  => $this->baseurl(),
			'writable' => is_writable( $this->basedir() ),
			'types'    => $types,
			'total'    => array_sum( array_column( $types, 'bytes' ) ),
		);
	}

	/**
	 * Absolute path => storage relative path (what goes into the DB).
	 *
	 * @param string $absolute Absolute path.
	 * @return string
	 */
	public function relative( $absolute ) {
		$base = Filesystem::normalize( $this->basedir() );
		$path = Filesystem::normalize( $absolute );

		if ( 0 !== strpos( $path, $base ) ) {
			return '';
		}

		return trim( substr( $path, strlen( $base ) ), '/' );
	}

	/**
	 * Storage relative path => absolute path (traversal safe).
	 *
	 * @param string $relative Relative path.
	 * @return string
	 */
	public function absolute( $relative ) {
		$relative = ltrim( str_replace( '\\', '/', (string) $relative ), '/' );

		if ( false !== strpos( $relative, '..' ) ) {
			return '';
		}

		$path = $this->basedir() . '/' . $relative;

		if ( ! Filesystem::is_inside( $this->basedir(), $path ) ) {
			return '';
		}

		return rtrim( $path, '/' );
	}

	/**
	 * Reset for tests.
	 *
	 * @return void
	 */
	public function reset() {
		// Nothing memoised beyond the static uploads cache, which WordPress invalidates itself.
	}
}
