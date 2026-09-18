<?php
/**
 * Deactivation: never destroys user assets.
 *
 * @package DashWoo
 */

namespace DashWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Deactivator.
 */
final class Deactivator {

	/**
	 * Run on deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		delete_transient( 'dashwoo_activation_report' );
		flush_rewrite_rules();
	}
}
