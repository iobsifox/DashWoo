<?php
/**
 * Activation: table, defaults, folders, compatibility report.
 *
 * @package DashWoo
 */

namespace DashWoo;

use DashWoo\Assets\Storage;
use DashWoo\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Activator.
 */
final class Activator {

	/**
	 * Run on activation.
	 *
	 * @return void
	 */
	public static function activate() {
		self::install_table();
		self::install_settings();
		self::install_storage();

		update_option( 'dashwoo_version', DASHWOO_VERSION, true );

		// Build the environment report right away: the activation screen shows it.
		$report = \DashWoo\Compatibility\Compatibility::instance()->refresh();

		set_transient( 'dashwoo_activation_report', $report->to_array(), 300 );

		if ( $report->requires_compatibility_mode() ) {
			// Goes through the Settings API on purpose: a direct update_option()
			// would leave the in-request cache (and the change events) stale.
			Settings::instance()->set_value( 'general', 'mode', 'compatibility' );
		}

		flush_rewrite_rules();
	}

	/**
	 * Create the asset registry table.
	 *
	 * @return void
	 */
	public static function install_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = $wpdb->prefix . 'dashwoo_assets';
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			type VARCHAR(20) NOT NULL DEFAULT 'font',
			group_key VARCHAR(190) NOT NULL DEFAULT '',
			slug VARCHAR(190) NOT NULL DEFAULT '',
			label VARCHAR(190) NOT NULL DEFAULT '',
			provider VARCHAR(60) NOT NULL DEFAULT 'local',
			version VARCHAR(32) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			is_default TINYINT(1) NOT NULL DEFAULT 0,
			role VARCHAR(40) NOT NULL DEFAULT '',
			path VARCHAR(255) NOT NULL DEFAULT '',
			url VARCHAR(255) NOT NULL DEFAULT '',
			size BIGINT UNSIGNED NOT NULL DEFAULT 0,
			meta LONGTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY type_status (type, status),
			KEY group_key (group_key),
			UNIQUE KEY slug_type (slug, type)
		) {$collate};";

		dbDelta( $sql );

		update_option( 'dashwoo_db_version', DASHWOO_DB_VERSION, true );
	}

	/**
	 * Seed default settings.
	 *
	 * @return void
	 */
	public static function install_settings() {
		$current = get_option( Settings::OPTION, array() );

		if ( ! is_array( $current ) || ! $current ) {
			update_option( Settings::OPTION, Settings::defaults(), true );
			return;
		}

		update_option( Settings::OPTION, Settings::merge( Settings::defaults(), $current ), true );
	}

	/**
	 * Create the uploads tree + the .htaccess guard.
	 *
	 * @return void
	 */
	public static function install_storage() {
		Storage::instance()->ensure_tree();
	}
}
