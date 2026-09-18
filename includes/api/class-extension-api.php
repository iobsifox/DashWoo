<?php
/**
 * Public extension API: the documented ways a third party extends DashWoo.
 *
 * @package DashWoo
 */

namespace DashWoo\Api;

use DashWoo\Assets\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Developer facing helpers + documented filters/actions.
 */
final class Extension_API {

	/**
	 * Register a token (shows up in the Elementor picker).
	 *
	 * @param string $name     Token name (short, e.g. "brand-tint").
	 * @param string $value    CSS value.
	 * @param string $category Category key.
	 * @return void
	 */
	public static function register_token( $name, $value, $category = 'color' ) {
		add_filter(
			'dashwoo_custom_tokens',
			static function ( $tokens ) use ( $name, $value ) {
				$tokens[ $name ] = $value;
				return $tokens;
			}
		);

		add_filter(
			'dashwoo_token_categories',
			static function ( $categories ) use ( $category ) {
				if ( ! isset( $categories[ $category ] ) ) {
					$categories[ $category ] = array( 'label' => $category );
				}
				return $categories;
			}
		);
	}

	/**
	 * Register an asset provider (extends the Asset Manager beyond Google/Material).
	 *
	 * @param string   $id       Provider id.
	 * @param callable $resolver Resolver: function ( string $query ) : array.
	 * @return void
	 */
	public static function register_asset_provider( $id, $resolver ) {
		add_filter(
			'dashwoo_asset_providers',
			static function ( $providers ) use ( $id, $resolver ) {
				$providers[ sanitize_key( $id ) ] = $resolver;
				return $providers;
			}
		);
	}

	/**
	 * Register an Elementor widget class.
	 *
	 * @param string $class Widget class (must extend Elementor\Widget_Base).
	 * @return void
	 */
	public static function register_widget( $class ) {
		add_filter(
			'dashwoo_elementor_widgets',
			static function ( $classes ) use ( $class ) {
				$classes[] = $class;
				return $classes;
			}
		);
	}

	/**
	 * Register a compatibility adapter.
	 *
	 * @param object $adapter Object implementing Interface_Adapter.
	 * @return void
	 */
	public static function register_adapter( $adapter ) {
		add_filter(
			'dashwoo_adapters',
			static function ( $adapters ) use ( $adapter ) {
				$adapters[] = $adapter;
				return $adapters;
			}
		);
	}

	/**
	 * Store an asset row from an extension.
	 *
	 * @param array<string,mixed> $data Row.
	 * @return int Row id.
	 */
	public static function register_asset( array $data ) {
		return Registry::instance()->upsert( $data );
	}

	/**
	 * The full hook reference (used by the docs screen + tests).
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function hooks() {
		return array(
			'filters' => array(
				'dashwoo_tokens'                  => 'Final token map before it becomes CSS variables.',
				'dashwoo_custom_tokens'           => 'Add your own tokens.',
				'dashwoo_tokens_css'              => 'The compiled tokens CSS string.',
				'dashwoo_font_catalog'            => 'The bundled Google Fonts catalogue.',
				'dashwoo_storage_dir'             => 'Override uploads/dashwoo.',
				'dashwoo_asset_providers'         => 'Register asset providers.',
				'dashwoo_adapters'                => 'Register compatibility adapters.',
				'dashwoo_elementor_widgets'       => 'Register Elementor widgets.',
				'dashwoo_elementor_font_families' => 'Families added to the Elementor font list.',
				'dashwoo_icon_html'               => 'Icon markup.',
				'dashwoo_http_allowed_hosts'      => 'Hosts DashWoo may contact during an import.',
			),
			'actions' => array(
				'dashwoo_booted'            => 'Plugin fully booted.',
				'dashwoo_settings_saved'    => 'A settings section was saved.',
				'dashwoo_settings_imported' => 'Settings were imported.',
				'dashwoo_settings_reset'    => 'A section or everything was reset.',
				'dashwoo_font_installed'    => 'A font finished downloading.',
				'dashwoo_font_changed'      => 'Fonts changed: regenerate the stylesheet.',
				'dashwoo_font_deleted'      => 'A font was deleted.',
				'dashwoo_icon_installed'    => 'An icon font was installed.',
				'dashwoo_asset_imported'    => 'An asset was imported.',
				'dashwoo_rest_registered'   => 'REST routes registered.',
				'dashwoo_cache_flush'       => 'Flush generated files.',
			),
		);
	}
}
