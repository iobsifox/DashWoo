<?php
/**
 * Icon manager: install, deliver and inspect local icon fonts.
 *
 * @package DashWoo
 */

namespace DashWoo\Assets\Icons;

use DashWoo\Assets\Registry;
use DashWoo\Assets\Storage;
use DashWoo\Support\Filesystem;

defined( 'ABSPATH' ) || exit;

/**
 * Icon manager.
 */
final class Icon_Manager {

	const HANDLE = 'dashwoo-icons';

	/**
	 * Singleton.
	 *
	 * @var Icon_Manager|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Icon_Manager
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 20 );
	}

	/**
	 * Installed icon sets.
	 *
	 * @param array<string,mixed> $args Query args.
	 * @return array<int,array<string,mixed>>
	 */
	public function all( array $args = array() ) {
		return Registry::instance()->query( array_merge( array( 'type' => 'icon', 'per_page' => 100 ), $args ) );
	}

	/**
	 * Install a style (thin wrapper).
	 *
	 * @param string $style Style key.
	 * @param bool   $force Force re-download.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function install( $style, $force = false ) {
		return Material_Provider::instance()->install( $style, $force );
	}

	/**
	 * Delete an installed style + its files.
	 *
	 * @param string $slug Slug.
	 * @return bool
	 */
	public function delete( $slug ) {
		$row = Registry::instance()->find_by_slug( 'icon', $slug );

		if ( ! $row ) {
			return false;
		}

		return Registry::instance()->delete( $row['id'] );
	}

	/**
	 * Toggle a style on/off.
	 *
	 * @param string $slug Slug.
	 * @param bool   $active Active.
	 * @return bool
	 */
	public function set_status( $slug, $active ) {
		$row = Registry::instance()->find_by_slug( 'icon', $slug );

		if ( ! $row ) {
			return false;
		}

		return Registry::instance()->update( $row['id'], array( 'status' => $active ? 'active' : 'disabled' ) );
	}

	/**
	 * CSS for the active icon fonts (local @font-face only).
	 *
	 * @return string
	 */
	public function css() {
		$css = "/* DashWoo icon fonts - local files only. */\n";

		foreach ( $this->all( array( 'status' => 'active' ) ) as $row ) {
			$family = 'material-symbols-' . $row['meta']['style'] . ( 'classic' === ( $row['meta']['style'] ?? '' ) ? '' : '' );
			$family = 'classic' === ( $row['meta']['style'] ?? '' ) ? 'Material Icons' : 'Material Symbols ' . ucfirst( (string) $row['meta']['style'] );

			$css .= sprintf(
				"@font-face {\n  font-family: \"%s\";\n  font-style: normal;\n  font-weight: 100 700;\n  font-display: block;\n  src: url(\"%s\") format(\"woff2\");\n}\n",
				$family,
				isset( $row['meta']['url'] ) ? $row['meta']['url'] : ''
			);
		}

		$css .= ".dw-icon { display: inline-block; direction: ltr; line-height: 1; letter-spacing: normal; text-transform: none; white-space: nowrap; word-wrap: normal; -webkit-font-smoothing: antialiased; }\n";
		$css .= ".dw-icon--material-symbols, .dw-icon--material-symbols-outlined, .dw-icon--material-symbols-rounded, .dw-icon--material-symbols-sharp { font-family: \"Material Symbols Outlined\"; }\n";
		$css .= ".dw-icon--outlined { font-family: \"Material Symbols Outlined\"; }\n";
		$css .= ".dw-icon--rounded { font-family: \"Material Symbols Rounded\"; }\n";
		$css .= ".dw-icon--sharp { font-family: \"Material Symbols Sharp\"; }\n";
		$css .= ".dw-icon--classic, .dw-icon--material-icons { font-family: \"Material Icons\"; }\n";

		return $css;
	}

	/**
	 * Write the icon stylesheet into the cache directory.
	 *
	 * @return array<string,mixed>
	 */
	public function compile() {
		$css     = $this->css();
		$storage = Storage::instance();
		$path    = $storage->path( 'cache' ) . 'dw-icons.css';

		Filesystem::put( $path, $css );

		return array(
			'path'  => $path,
			'url'   => $storage->url( 'cache' ) . 'dw-icons.css',
			'hash'  => substr( md5( $css ), 0, 12 ),
			'bytes' => strlen( $css ),
		);
	}

	/**
	 * Enqueue the icon stylesheet when at least one set is installed.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! dashwoo_is_on( 'general.enabled' ) || ! $this->all( array( 'status' => 'active' ) ) ) {
			return;
		}

		$compiled = $this->compile();

		wp_enqueue_style( self::HANDLE, $compiled['url'], array(), $compiled['hash'] );
	}

	/**
	 * Icon search: returns a small local catalogue for the admin picker.
	 *
	 * @param string $query Query.
	 * @param int    $limit Max results.
	 * @return array<int,string>
	 */
	public function search( $query = '', $limit = 60 ) {
		$icons = array(
			'home', 'search', 'shopping_cart', 'shopping_bag', 'favorite', 'star', 'person', 'account_circle',
			'settings', 'menu', 'close', 'add', 'remove', 'check', 'delete', 'edit', 'visibility',
			'arrow_back', 'arrow_forward', 'arrow_upward', 'arrow_downward', 'chevron_left', 'chevron_right',
			'expand_more', 'expand_less', 'filter_list', 'sort', 'refresh', 'download', 'upload', 'share',
			'notifications', 'mail', 'call', 'location_on', 'local_shipping', 'payments', 'credit_card',
			'receipt_long', 'inventory_2', 'storefront', 'loyalty', 'redeem', 'local_offer', 'verified',
			'info', 'warning', 'error', 'help', 'support_agent', 'chat', 'language', 'translate',
			'light_mode', 'dark_mode', 'palette', 'text_fields', 'image', 'folder', 'description',
		);

		$query = strtolower( trim( (string) $query ) );
		$out   = array();

		foreach ( $icons as $icon ) {
			if ( '' !== $query && false === strpos( $icon, $query ) ) {
				continue;
			}
			$out[] = $icon;
			if ( count( $out ) >= (int) $limit ) {
				break;
			}
		}

		return $out;
	}
}
