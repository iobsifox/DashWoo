<?php
/**
 * Asset-layer adapter: what the design system can rely on at runtime.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility\Adapters;

use DashWoo\Compatibility\Abstract_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * Asset adapter.
 */
class Asset_Adapter extends Abstract_Adapter {  // Not final: third parties may extend a check set.

	/**
	 * Adapter id.
	 *
	 * @return string
	 */
	public function id() {
		return 'assets';
	}

	/**
	 * Adapter label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Assets';
	}

	/**
	 * Always active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return true;
	}

	/**
	 * No version.
	 *
	 * @return string
	 */
	public function version() {
		return DASHWOO_VERSION;
	}

	/**
	 * Tested up to.
	 *
	 * @return string
	 */
	public function tested_up_to() {
		return DASHWOO_VERSION;
	}

	/**
	 * Collect checks.
	 *
	 * @return void
	 */
	protected function inspect() {
		$upload = wp_upload_dir();
		$base   = trailingslashit( $upload['basedir'] ) . 'dashwoo';

		$this->check(
			'storage_dir',
			(self::STATUS_OK),
			'Asset storage: ' . $base,
			array( 'path' => $base )
		);
		$this->capability( 'asset_storage', true );

		$zip = class_exists( '\ZipArchive' );
		$this->check(
			'zip',
			$zip ? self::STATUS_OK : self::STATUS_WARNING,
			$zip ? 'ZipArchive available (import/export)' : 'ZipArchive missing: import/export uses the WP filesystem'
		);
		$this->capability( 'zip', $zip );

		$gd = function_exists( 'imagecreatetruecolor' );
		$this->check(
			'gd',
			$gd ? self::STATUS_OK : self::STATUS_WARNING,
			$gd ? 'GD image library available' : 'GD missing: thumbnails are not generated'
		);
		$this->capability( 'image_processing', $gd );

		$svg_ok = function_exists( 'wp_kses' );
		$this->check(
			'svg_sanitizer',
			$svg_ok ? self::STATUS_OK : self::STATUS_WARNING,
			$svg_ok ? 'SVG sanitizer ready (wp_kses allow-list)' : 'SVG sanitizer unavailable - SVG uploads blocked'
		);
		$this->capability( 'svg_sanitizer', $svg_ok );

		$variation = function_exists( 'wp_add_inline_style' );
		$this->check(
			'css_variation',
			$variation ? self::STATUS_OK : self::STATUS_WARNING,
			$variation ? 'Variable fonts + font-variation-settings supported' : 'Inline CSS API unavailable'
		);
		$this->capability( 'variable_fonts', $variation );

		$this->check(
			'cdn_free',
			self::STATUS_OK,
			'CDN-free mode: all fonts/icons are served from the local uploads directory'
		);
		$this->capability( 'cdn_free', true );
	}
}
