<?php
/**
 * Admin UI: Settings Center, Asset Manager, System Status, Logs, Export.
 *
 * @package DashWoo
 */

namespace DashWoo\Admin;

use DashWoo\Assets\Asset_Manager;
use DashWoo\Assets\Fonts\Font_Manager;
use DashWoo\Assets\Fonts\Google_Fonts_Provider;
use DashWoo\Assets\Icons\Icon_Manager;
use DashWoo\Assets\Icons\Material_Provider;
use DashWoo\Cache\Cache;
use DashWoo\Compatibility\Compatibility;
use DashWoo\DesignSystem\Compiler;
use DashWoo\DesignSystem\Elementor\Tokens_Integration;
use DashWoo\Settings\Sections;
use DashWoo\Settings\Settings;
use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Admin controller.
 */
final class Admin {

	const SLUG = 'dashwoo';

	/**
	 * Singleton.
	 *
	 * @var Admin|null
	 */
	private static $instance = null;

	/**
	 * Notice to render on the next page load.
	 *
	 * @var array<string,string>|null
	 */
	private $notice = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Admin
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
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_dashwoo_action', array( $this, 'handle_action' ) );
		add_action( 'admin_notices', array( $this, 'activation_notice' ) );
	}

	/**
	 * Admin menu.
	 *
	 * @return void
	 */
	public function menu() {
		add_menu_page(
			'DashWoo',
			'DashWoo',
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-art',
			58
		);

		add_submenu_page( self::SLUG, 'داشبورد', 'داشبورد', 'manage_options', self::SLUG, array( $this, 'render' ) );
		add_submenu_page( self::SLUG, 'مرکز تنظیمات', 'مرکز تنظیمات', 'manage_options', self::SLUG . '&section=general', array( $this, 'render' ) );
		add_submenu_page( self::SLUG, 'مدیریت دارایی‌ها', 'دارایی‌ها', 'manage_options', self::SLUG . '-assets', array( $this, 'render_assets' ) );
		add_submenu_page( self::SLUG, 'وضعیت سیستم', 'وضعیت سیستم', 'manage_options', self::SLUG . '&section=system', array( $this, 'render' ) );
		add_submenu_page( self::SLUG, 'گزارش‌ها', 'گزارش‌ها', 'manage_options', self::SLUG . '&section=logs', array( $this, 'render' ) );
	}

	/**
	 * Requested section (default dashboard).
	 *
	 * @return string
	 */
	public function current_section() {
		$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification
		$all     = Sections::all();

		if ( 'system' === $section || 'assets' === $section ) {
			return $section;
		}

		return isset( $all[ $section ] ) ? $section : 'dashboard';
	}

	/**
	 * Main page router.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied.' );
		}

		$section = $this->current_section();
		$context = $this->context( $section );

		switch ( $section ) {
			case 'system':
				$this->view( 'system-status', $context );
				break;

			case 'dashboard':
				$this->view( 'dashboard', $context );
				break;

			case 'logs':
				$this->view( 'logs', $context );
				break;

			default:
				$this->view( 'settings', $context );
		}
	}

	/**
	 * Asset manager screen.
	 *
	 * @return void
	 */
	public function render_assets() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied.' );
		}

		$type    = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'font'; // phpcs:ignore WordPress.Security.NonceVerification
		$context = $this->context( 'assets' );
		$context['type']  = in_array( $type, array( 'font', 'icon', 'image', 'svg', 'custom' ), true ) ? $type : 'font';
		$context['rows']  = Asset_Manager::instance()->all( array( 'type' => $context['type'], 'per_page' => 100 ) );
		$context['stats'] = Asset_Manager::instance()->stats();

		$this->view( 'assets', $context );
	}

	/**
	 * Shared view context.
	 *
	 * @param string $section Section.
	 * @return array<string,mixed>
	 */
	public function context( $section ) {
		return array(
			'section'       => $section,
			'settings'      => Settings::instance()->all(),
			'sections'      => Sections::all(),
			'groups'        => Sections::by_group(),
			'group_labels'  => Sections::groups(),
			'compatibility' => Compatibility::instance()->stored(),
			'mode'          => Compatibility::instance()->mode(),
			'storage'       => Asset_Manager::instance()->stats(),
			'fonts'         => Font_Manager::instance()->all(),
			'icons'         => Icon_Manager::instance()->all(),
			'logs'          => Logger::instance()->all( 60 ),
			'compiled'      => Compiler::instance()->compiled(),
			'notice'        => $this->notice,
			'catalog'       => Google_Fonts_Provider::instance()->catalog(),
			'icon_styles'   => Material_Provider::instance()->styles(),
			'action_url'    => admin_url( 'admin-post.php' ),
		);
	}

	/**
	 * Load a view file.
	 *
	 * @param string              $name    View name (without .php).
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	public function view( $name, array $context = array() ) {
		$file = DASHWOO_INCLUDES . 'admin/views/' . sanitize_file_name( $name ) . '.php';

		if ( ! is_readable( $file ) ) {
			printf( '<div class="wrap"><p>%s</p></div>', esc_html( 'View not found: ' . $name ) );
			return;
		}

		require $file;
	}

	/**
	 * Handle every admin-post action.
	 *
	 * @return void
	 */
	/**
	 * Every action key `dispatch()` understands.
	 *
	 * @return string[]
	 */
	public function actions() {
		return array(
			'save_settings',
			'reset_section',
			'install_font',
			'update_font',
			'delete_font',
			'default_font',
			'install_icons',
			'delete_icon',
			'recheck',
			'compile',
			'flush_cache',
			'kit_sync',
			'kit_revert',
			'clear_logs',
			'upload_font',
			'upload_asset',
		);
	}

	public function handle_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied.' );
		}

		check_admin_referer( 'dashwoo_action' );

		$action  = isset( $_POST['dw_action'] ) ? sanitize_key( wp_unslash( $_POST['dw_action'] ) ) : '';
		$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : 'dashboard';
		$redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=' . self::SLUG );

		$result = $this->dispatch( $action );

		$redirect = add_query_arg(
			array(
				'dw_done' => $result['ok'] ? '1' : '0',
				'dw_msg'  => rawurlencode( $result['message'] ),
			),
			$redirect
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Execute one admin action.
	 *
	 * @param string $action Action key.
	 * @return array{ok:bool,message:string,data?:mixed}
	 */
	public function dispatch( $action ) {
		$post = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification

		switch ( $action ) {
			case 'save_settings':
				$section = isset( $post['section'] ) ? sanitize_key( $post['section'] ) : '';
				$fields  = isset( $post['dw'] ) && is_array( $post['dw'] ) ? $post['dw'] : array();
				$saved   = Settings::instance()->save_section( $section, $fields );

				return array(
					'ok'      => (bool) $saved,
					'message' => $saved ? 'تنظیمات ذخیره شد.' : 'بخش نامعتبر است.',
				);

			case 'reset_section':
				Settings::instance()->reset_section( isset( $post['section'] ) ? sanitize_key( $post['section'] ) : '' );

				return array(
					'ok'      => true,
					'message' => 'بخش بازنشانی شد.',
				);

			case 'install_font':
				$family  = isset( $post['family'] ) ? sanitize_text_field( $post['family'] ) : '';
				$weights = isset( $post['weights'] ) ? array_map( 'sanitize_text_field', (array) $post['weights'] ) : array( '400' );
				$result  = Font_Manager::instance()->install_google( $family, $weights );

				return is_wp_error( $result )
					? array(
						'ok'      => false,
						'message' => $result->get_error_message(),
					)
					: array(
						'ok'      => true,
						'message' => sprintf( 'فونت %s با موفقیت دانلود و به‌صورت محلی ذخیره شد.', $result['label'] ),
					);

			case 'update_font':
				$result = Font_Manager::instance()->update( isset( $post['slug'] ) ? sanitize_text_field( $post['slug'] ) : '' );

				return is_wp_error( $result )
					? array(
						'ok'      => false,
						'message' => $result->get_error_message(),
					)
					: array(
						'ok'      => true,
						'message' => ! empty( $result['updated'] ) ? 'فونت به‌روزرسانی شد.' : 'فونت از قبل به‌روز است.',
					);

			case 'delete_font':
				$ok = Font_Manager::instance()->delete( isset( $post['slug'] ) ? sanitize_text_field( $post['slug'] ) : '' );

				return array(
					'ok'      => $ok,
					'message' => $ok ? 'فونت حذف شد.' : 'فونت یافت نشد.',
				);

			case 'upload_font':
				$result = $this->handle_upload( 'font_file', 'font', isset( $post['label'] ) ? $post['label'] : 'Font' );

				return $result;

			case 'upload_asset':
				$result = $this->handle_upload(
					'asset_file',
					isset( $post['type'] ) ? sanitize_key( $post['type'] ) : 'image',
					isset( $post['label'] ) ? $post['label'] : ''
				);

				return $result;

			case 'default_font':
				$result = Font_Manager::instance();

				return array(
					'ok'      => (bool) $result->set_status( (string) ( $post['slug'] ?? '' ), true ),
					'message' => 'فونت پیش‌فرض تنظیم شد.',
				);

			case 'install_icons':
				$result = Icon_Manager::instance()->install( isset( $post['style'] ) ? sanitize_key( $post['style'] ) : 'outlined', ! empty( $post['force'] ) );

				return is_wp_error( $result )
					? array(
						'ok'      => false,
						'message' => $result->get_error_message(),
					)
					: array(
						'ok'      => true,
						'message' => ! empty( $result['installed'] ) ? 'فونت آیکون دانلود شد (یک فایل متغیر).' : 'این سبک از قبل نصب است.',
					);

			case 'delete_icon':
				$ok = Icon_Manager::instance()->delete( isset( $post['slug'] ) ? sanitize_text_field( $post['slug'] ) : '' );

				return array(
					'ok'      => $ok,
					'message' => $ok ? 'سبک آیکون حذف شد.' : 'سبک یافت نشد.',
				);

			case 'recheck':
				$report = Compatibility::instance()->refresh();

				return array(
					'ok'      => true,
					'message' => sprintf(
						'بررسی انجام شد: %d موفق، %d هشدار، %d خطا.',
						(int) $report->summary()['ok'],
						(int) $report->summary()['warning'],
						(int) $report->summary()['error']
					),
				);

			case 'compile':
				$result = Compiler::instance()->recompile();

				return array(
					'ok'      => true,
					'message' => sprintf( 'CSS توکن‌ها ساخته شد (%d متغیر، %s).', (int) $result['variables'], $result['file'] ),
				);

			case 'flush_cache':
				$gone = Cache::instance()->flush_all();

				return array(
					'ok'      => true,
					'message' => sprintf( '%d فایل کش حذف شد.', (int) $gone ),
				);

			case 'kit_sync':
				$result = Tokens_Integration::instance()->sync_kit();

				return is_wp_error( $result )
					? array(
						'ok'      => false,
						'message' => $result->get_error_message(),
					)
					: array(
						'ok'      => true,
						'message' => sprintf( 'کیت المنتور همگام شد (%d رنگ).', (int) $result['colors'] ),
					);

			case 'kit_revert':
				$result = Tokens_Integration::instance()->revert_kit();

				return is_wp_error( $result )
					? array(
						'ok'      => false,
						'message' => $result->get_error_message(),
					)
					: array(
						'ok'      => true,
						'message' => 'کیت المنتور به نسخه پشتیبان بازگشت.',
					);

			case 'clear_logs':
				Logger::instance()->clear();

				return array(
					'ok'      => true,
					'message' => 'گزارش‌ها پاک شد.',
				);

			default:
				return array(
					'ok'      => false,
					'message' => 'عملیات ناشناخته.',
				);
		}
	}

	/**
	 * Activation compatibility screen (once).
	 *
	 * @return void
	 */
	public function activation_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$report = get_transient( 'dashwoo_activation_report' );

		if ( ! $report ) {
			return;
		}

		delete_transient( 'dashwoo_activation_report' );

		$summary = isset( $report['summary'] ) ? $report['summary'] : array();
		$class   = empty( $summary['error'] ) ? 'notice-success' : 'notice-error';

		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p><strong>DashWoo</strong> — ';
		printf(
			/* translators: 1: ok, 2: warnings, 3: errors */
			esc_html__( 'بررسی سازگاری: %1$d موفق، %2$d هشدار، %3$d خطا.', 'dashwoo' ),
			(int) ( $summary['ok'] ?? 0 ),
			(int) ( $summary['warning'] ?? 0 ),
			(int) ( $summary['error'] ?? 0 )
		);
		printf(
			' <a href="%s">%s</a></p></div>',
			esc_url( admin_url( 'admin.php?page=dashwoo&section=system' ) ),
			esc_html__( 'مشاهده گزارش کامل', 'dashwoo' )
		);
	}

	/**
	 * Shared upload handler for fonts and assets.
	 *
	 * @param string $field File field name.
	 * @param string $type  Asset type.
	 * @param string $label Label.
	 * @return array{ok:bool,message:string}
	 */
	public function handle_upload( $field, $type, $label = '' ) {
		if ( empty( $_FILES[ $field ]['tmp_name'] ) ) {
			return array(
				'ok'      => false,
				'message' => 'فایلی انتخاب نشده است.',
			);
		}

		$file  = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$label = sanitize_text_field( (string) $label );

		if ( function_exists( 'wp_check_filetype_and_ext' ) ) {
			$check = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( $file['name'] ) );

			if ( empty( $check['ext'] ) ) {
				return array(
					'ok'      => false,
					'message' => 'پسوند فایل مجاز نیست.',
				);
			}
		}

		$result = Asset_Manager::instance()->import_file(
			(string) $file['tmp_name'],
			array(
				'type'  => $type,
				'label' => '' !== $label ? $label : sanitize_file_name( (string) $file['name'] ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return array(
				'ok'      => false,
				'message' => $result->get_error_message(),
			);
		}

		if ( 'font' === $type ) {
			Font_Manager::instance()->install_local(
				array(
					'family'   => '' !== $label ? $label : $result['slug'],
					'slug'     => $result['slug'],
					'provider' => 'local',
				),
				array(
					array(
						'path'   => (string) $result['meta']['path'],
						'weight' => '400',
						'style'  => 'normal',
					),
				)
			);
		}

		return array(
			'ok'      => true,
			'message' => sprintf( 'فایل «%s» با موفقیت ذخیره شد.', $label ),
		);
	}

	/**
	 * URL helper for the tabs.
	 *
	 * @param string              $section Section key.
	 * @param array<string,mixed> $args    Extra query args.
	 * @return string
	 */
	public function url( $section, array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG, 'section' => $section ), $args ), admin_url( 'admin.php' ) );
	}
}
