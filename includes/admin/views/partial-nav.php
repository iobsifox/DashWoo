<?php
/**
 * Shared navigation header for the DashWoo screens.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

$dw_section   = isset( $context['section'] ) ? $context['section'] : 'dashboard';
$dw_groups    = isset( $context['groups'] ) ? $context['groups'] : array();
$dw_sections  = isset( $context['sections'] ) ? $context['sections'] : array();
$dw_glabels   = isset( $context['group_labels'] ) ? $context['group_labels'] : array();
$dw_admin     = \DashWoo\Admin\Admin::instance();
$dw_done      = isset( $_GET['dw_done'] ) ? sanitize_key( wp_unslash( $_GET['dw_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$dw_msg       = isset( $_GET['dw_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['dw_msg'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<div class="wrap dashwoo-wrap">
	<h1 class="dw-title">
		<span class="dw-logo" aria-hidden="true"></span>
		DashWoo
		<span class="dw-badge">v<?php echo esc_html( DASHWOO_VERSION ); ?></span>
		<span class="dw-badge dw-badge--<?php echo esc_attr( $context['mode'] ); ?>">
			<?php echo 'compatibility' === $context['mode'] ? 'Compatibility Mode' : 'Full Override'; ?>
		</span>
	</h1>

	<?php if ( '' !== $dw_done ) : ?>
		<div class="notice <?php echo '1' === $dw_done ? 'notice-success' : 'notice-error'; ?> is-dismissible">
			<p><?php echo esc_html( $dw_msg ); ?></p>
		</div>
	<?php endif; ?>

	<nav class="dw-nav">
		<a class="dw-nav__item <?php echo 'dashboard' === $dw_section ? 'is-active' : ''; ?>"
			href="<?php echo esc_url( $dw_admin->url( 'dashboard' ) ); ?>">داشبورد</a>

		<?php foreach ( $dw_groups as $dw_group => $dw_keys ) : ?>
			<?php if ( ! $dw_keys ) { continue; } ?>
			<span class="dw-nav__group">
				<span class="dw-nav__group-title"><?php echo esc_html( $dw_glabels[ $dw_group ]['label'] ?? $dw_group ); ?></span>
				<?php foreach ( $dw_keys as $dw_key ) : ?>
					<a class="dw-nav__item <?php echo $dw_section === $dw_key ? 'is-active' : ''; ?>"
						href="<?php echo esc_url( $dw_admin->url( $dw_key ) ); ?>">
						<?php echo esc_html( $dw_sections[ $dw_key ]['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</span>
		<?php endforeach; ?>

		<a class="dw-nav__item <?php echo 'assets' === $dw_section ? 'is-active' : ''; ?>"
			href="<?php echo esc_url( admin_url( 'admin.php?page=dashwoo-assets' ) ); ?>">مدیریت دارایی‌ها</a>
		<a class="dw-nav__item <?php echo 'system' === $dw_section ? 'is-active' : ''; ?>"
			href="<?php echo esc_url( $dw_admin->url( 'system' ) ); ?>">وضعیت سیستم</a>
	</nav>
