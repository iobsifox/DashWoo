<?php
/**
 * Dashboard view.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';

$dw_summary  = $context['compatibility']['summary'] ?? array();
$dw_storage  = $context['storage'];
$dw_fonts    = count( $context['fonts'] );
$dw_icons    = count( $context['icons'] );
$dw_compiled = $context['compiled'];
?>
	<div class="dw-grid dw-grid--4">
		<div class="dw-card">
			<h2>سازگاری محیط</h2>
			<p class="dw-kpi"><?php echo (int) ( $dw_summary['ok'] ?? 0 ); ?><small> ✓</small>
				<?php echo (int) ( $dw_summary['warning'] ?? 0 ); ?><small> ⚠</small>
				<?php echo (int) ( $dw_summary['error'] ?? 0 ); ?><small> ✕</small></p>
			<a class="button" href="<?php echo esc_url( \DashWoo\Admin\Admin::instance()->url( 'system' ) ); ?>">گزارش کامل</a>
		</div>
		<div class="dw-card">
			<h2>فونت‌های محلی</h2>
			<p class="dw-kpi"><?php echo (int) $dw_fonts; ?></p>
			<p class="description">سبک‌های آیکون: <?php echo (int) $dw_icons; ?></p>
		</div>
		<div class="dw-card">
			<h2>CSS توکن‌ها</h2>
			<p class="description"><?php echo esc_html( $dw_compiled['file'] ?? '—' ); ?></p>
			<p class="description"><?php echo (int) ( $dw_compiled['variables'] ?? 0 ); ?> متغیر • <?php echo esc_html( size_format( (int) ( $dw_compiled['bytes'] ?? 0 ) ) ); ?></p>
		</div>
		<div class="dw-card">
			<h2>فضای ذخیره‌سازی</h2>
			<p class="dw-kpi"><?php echo esc_html( size_format( (int) $dw_storage['total'] ) ); ?></p>
			<p class="description"><code><?php echo esc_html( $dw_storage['basedir'] ); ?></code></p>
		</div>
	</div>

	<div class="dw-grid dw-grid--2">
		<div class="dw-card">
			<h2>دارایی‌های ثبت‌شده</h2>
			<table class="widefat striped">
				<thead><tr><th>نوع</th><th>تعداد</th></tr></thead>
				<tbody>
				<?php foreach ( (array) ( $dw_storage['counts'] ?? array() ) as $dw_type => $dw_count ) : ?>
					<tr><td><?php echo esc_html( $dw_type ); ?></td><td><?php echo (int) $dw_count; ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="dw-card">
			<h2>وضعیت CDN</h2>
			<p><span class="dw-pill dw-pill--ok">✓ بدون وابستگی به CDN</span></p>
			<p class="description">همه فونت‌ها و آیکون‌ها از <code>uploads/dashwoo/</code> بارگذاری می‌شوند. پس از دانلود، هیچ درخواستی به سرویس‌های بیرونی زده نمی‌شود.</p>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<input type="hidden" name="dw_action" value="compile" />
				<button class="button button-primary" type="submit">ساخت مجدد CSS توکن‌ها</button>
			</form>
		</div>
	</div>
</div>
