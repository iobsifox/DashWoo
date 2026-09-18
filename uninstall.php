<?php
/**
 * Uninstall routine. Runs only when the plugin is deleted from the plugins screen.
 *
 * @package DashWoo
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$dashwoo_settings = get_option( 'dashwoo_settings', array() );
$dashwoo_remove   = isset( $dashwoo_settings['general']['remove_data_on_uninstall'] )
	? (bool) $dashwoo_settings['general']['remove_data_on_uninstall']
	: false;

delete_option( 'dashwoo_compatibility' );
delete_option( 'dashwoo_log' );
delete_transient( 'dashwoo_activation_report' );

if ( ! $dashwoo_remove ) {
	return;
}

global $wpdb;

delete_option( 'dashwoo_settings' );
delete_option( 'dashwoo_version' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}dashwoo_assets" );

$dashwoo_upload = wp_upload_dir();
$dashwoo_dir    = trailingslashit( $dashwoo_upload['basedir'] ) . 'dashwoo';
if ( is_dir( $dashwoo_dir ) ) {
	$dashwoo_it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dashwoo_dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $dashwoo_it as $dashwoo_item ) {
		$dashwoo_item->isDir() ? rmdir( $dashwoo_item->getPathname() ) : unlink( $dashwoo_item->getPathname() );
	}
	rmdir( $dashwoo_dir );
}
