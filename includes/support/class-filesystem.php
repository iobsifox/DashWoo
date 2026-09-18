<?php
/**
 * Thin, testable filesystem helper.
 *
 * @package DashWoo
 */

namespace DashWoo\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Filesystem helper used by the asset storage and the compiler.
 */
final class Filesystem {

	/**
	 * Create a directory recursively.
	 *
	 * @param string $dir Absolute path.
	 * @return bool
	 */
	public static function mkdir( $dir ) {
		if ( is_dir( $dir ) ) {
			return true;
		}
		if ( function_exists( 'wp_mkdir_p' ) ) {
			return (bool) wp_mkdir_p( $dir );
		}
		return @mkdir( $dir, 0755, true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

	/**
	 * Write a file, creating the parent directory when needed.
	 *
	 * @param string $file     Absolute path.
	 * @param string $contents File contents.
	 * @return bool
	 */
	public static function put( $file, $contents ) {
		self::mkdir( dirname( $file ) );

		return false !== @file_put_contents( $file, $contents ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
	}

	/**
	 * Read a file.
	 *
	 * @param string $file Absolute path.
	 * @return string|false
	 */
	public static function get( $file ) {
		if ( ! is_readable( $file ) ) {
			return false;
		}
		return (string) @file_get_contents( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
	}

	/**
	 * Delete a single file.
	 *
	 * @param string $file Absolute path.
	 * @return bool
	 */
	public static function delete( $file ) {
		if ( is_file( $file ) ) {
			return @unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
		}
		return true;
	}

	/**
	 * Delete a directory with everything inside.
	 *
	 * @param string $dir Absolute path.
	 * @return bool
	 */
	public static function delete_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return true;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $item ) {
			$item->isDir() ? @rmdir( $item->getPathname() ) : @unlink( $item->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		return @rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

	/**
	 * Recursive directory size in bytes.
	 *
	 * @param string $dir Absolute path.
	 * @return int
	 */
	public static function size( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return 0;
		}

		$total    = 0;
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $item ) {
			if ( $item->isFile() ) {
				$total += (int) $item->getSize();
			}
		}

		return $total;
	}

	/**
	 * List files of a directory (one level).
	 *
	 * @param string $dir   Absolute path.
	 * @param string $regex Optional file name filter.
	 * @return array<int,string> Absolute paths.
	 */
	public static function list_files( $dir, $regex = '' ) {
		if ( ! is_dir( $dir ) ) {
			return array();
		}

		$out = array();
		foreach ( (array) scandir( $dir ) as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$path = trailingslashit( $dir ) . $entry;
			if ( ! is_file( $path ) ) {
				continue;
			}
			if ( '' !== $regex && ! preg_match( $regex, $entry ) ) {
				continue;
			}
			$out[] = $path;
		}

		return $out;
	}

	/**
	 * Guard against path traversal: the resolved path must live inside $base.
	 *
	 * @param string $base Absolute base directory.
	 * @param string $path Candidate path.
	 * @return bool
	 */
	public static function is_inside( $base, $path ) {
		$base = self::normalize( $base );
		$path = self::normalize( $path );

		if ( false !== strpos( $path, '..' ) ) {
			return false;
		}

		return 0 === strpos( $path, $base );
	}

	/**
	 * Normalize slashes and trailing slash.
	 *
	 * @param string $path Raw path.
	 * @return string
	 */
	public static function normalize( $path ) {
		$path = str_replace( '\\', '/', (string) $path );

		return rtrim( $path, '/' ) . '/';
	}

	/**
	 * Human readable size.
	 *
	 * @param int $bytes Bytes.
	 * @return string
	 */
	public static function format_size( $bytes ) {
		$bytes = (int) $bytes;
		$units = array( 'B', 'KB', 'MB', 'GB' );
		$index = 0;

		while ( $bytes >= 1024 && $index < 3 ) {
			$bytes /= 1024;
			$index++;
		}

		return round( $bytes, 2 ) . ' ' . $units[ $index ];
	}
}
