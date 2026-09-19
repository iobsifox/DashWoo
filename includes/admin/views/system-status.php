<?php
/**
 * System Status + compatibility report.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';

$dw_report  = $context['compatibility'];
$dw_checks  = $dw_report['checks'] ?? array();
$dw_summary = $dw_report['summary'] ?? array();
$dw_degraded = $dw_report['degraded'] ?? array();
?>
	<div class="dw-grid dw-grid--2">
		<div class="dw-card">
			<h2>خلاصه بررسی</h2>
			<p class="dw-kpi">
				<span class="dw-pill dw-pill--ok">✓ <?php echo (int) ( $dw_summary['ok'] ?? 0 ); ?> فعال</span>
				<span class="dw-pill dw-pill--info">ℹ <?php echo (int) ( $dw_summary['notice'] ?? 0 ); ?> اطلاع</span>
				<span class="dw-pill dw-pill--warn">⚠ <?php echo (int) ( $dw_summary['warning'] ?? 0 ); ?> هشدار</span>
				<span class="dw-pill dw-pill--err">✕ <?php echo (int) ( $dw_summary['error'] ?? 0 ); ?> خطا</span>
				<span class="dw-pill">– <?php echo (int) ( $dw_summary['na'] ?? 0 ); ?> نامرتبط</span>
			</p>
			<p class="description">
				علامت <strong>ℹ</strong> یعنی «این قابلیت روی این هاست نیست، ولی DashWoo به آن نیازی ندارد» —
				مانند <code>zip</code> یا <code>gd</code>. این موارد نه خطا هستند و نه روی حالت سازگاری اثر دارند.
			</p>
			<p class="description">حالت جاری: <strong><?php echo 'compatibility' === $context['mode'] ? 'Compatibility Mode' : 'Full Override'; ?></strong>
				— آخرین بررسی: <?php echo esc_html( isset( $dw_report['generated_at'] ) ? gmdate( 'Y-m-d H:i', (int) $dw_report['generated_at'] ) : '—' ); ?></p>

			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<input type="hidden" name="dw_action" value="recheck" />
				<button class="button button-primary" type="submit">بررسی مجدد محیط</button>
			</form>
		</div>

		<div class="dw-card">
			<h2>قابلیت‌های غیرفعال</h2>
			<?php if ( ! $dw_degraded ) : ?>
				<p><span class="dw-pill dw-pill--ok">همه قابلیت‌ها فعال هستند</span></p>
			<?php else : ?>
				<ul class="dw-list">
					<?php foreach ( (array) $dw_degraded as $dw_feature ) : ?>
						<li><code><?php echo esc_html( $dw_feature ); ?></code></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="dw-card">
		<h2>جزئیات بررسی</h2>
		<table class="widefat striped">
			<thead>
				<tr><th style="width:40px">وضعیت</th><th>آداپتر</th><th>بررسی</th><th>جزئیات</th></tr>
			</thead>
			<tbody>
			<?php foreach ( (array) $dw_checks as $dw_check ) : ?>
				<?php
				$dw_class = 'dw-pill';
				if ( 'ok' === $dw_check['status'] ) {
					$dw_class .= ' dw-pill--ok';
				} elseif ( 'notice' === $dw_check['status'] ) {
					$dw_class .= ' dw-pill--info';
				} elseif ( 'warning' === $dw_check['status'] ) {
					$dw_class .= ' dw-pill--warn';
				} elseif ( 'error' === $dw_check['status'] ) {
					$dw_class .= ' dw-pill--err';
				}
				?>
				<tr>
					<td><span class="<?php echo esc_attr( $dw_class ); ?>"><?php echo esc_html( $dw_check['symbol'] ); ?></span></td>
					<td><code><?php echo esc_html( $dw_check['adapter'] ); ?></code></td>
					<td><code><?php echo esc_html( $dw_check['id'] ); ?></code></td>
					<td>
						<?php echo esc_html( $dw_check['message'] ); ?>
						<?php if ( ! empty( $dw_check['hint'] ) ) : ?>
							<p class="dw-hint"><?php echo esc_html( $dw_check['hint'] ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php
	$dw_diagnostics = $context['diagnostics'] ?? array();
	$dw_plain       = '';

	if ( $dw_diagnostics ) {
		// Plain-text block: easy to copy into a support ticket.
		foreach ( (array) $dw_diagnostics as $dw_group => $dw_facts ) {
			$dw_plain .= '[' . $dw_group . ']' . "\n";

			foreach ( (array) $dw_facts as $dw_key => $dw_value ) {
				$dw_plain .= '  ' . str_pad( (string) $dw_key, 20 ) . ' : ' . ( is_scalar( $dw_value ) ? (string) $dw_value : wp_json_encode( $dw_value ) ) . "\n";
			}

			$dw_plain .= "\n";
		}
	}
	?>
	<div class="dw-card">
		<h2>تشخیص محیط (برای پشتیبانی هاست)</h2>
		<p class="description">
			این متن، واقعیت‌های خام سرور است: نسخهٔ PHP، افزونه‌های نصب‌شده، توابع بسته‌شده در
			<code>disable_functions</code>، محدودیت‌های فضای دانلود و قابل‌نوشتن بودن پوشهٔ دارایی‌ها.
			در صورت نیاز آن را کپی کنید و به پشتیبانی هاست بدهید.
		</p>
		<textarea class="dw-diagnostics" readonly rows="12" onclick="this.select()"><?php echo esc_textarea( $dw_plain ); ?></textarea>
		<p class="description">نمایش ماشین‌خوان: <code>GET /wp-json/dashwoo/v1/system/diagnostics</code></p>
	</div>

	<div class="dw-grid dw-grid--2">
		<div class="dw-card">
			<h2>همگام‌سازی کیت المنتور</h2>
			<p class="description">سطح ۳ یکپارچه‌سازی. به‌صورت پیش‌فرض خاموش است و قبل از هر تغییر، نسخه پشتیبان گرفته می‌شود.</p>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" class="dw-inline">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<button class="button" type="submit" name="dw_action" value="kit_sync">همگام‌سازی</button>
				<button class="button" type="submit" name="dw_action" value="kit_revert">بازگردانی از پشتیبان</button>
			</form>
		</div>
		<div class="dw-card">
			<h2>کش</h2>
			<p class="description">فایل‌های تولیدشده در <code>uploads/dashwoo/cache/</code>.</p>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" class="dw-inline">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<button class="button" type="submit" name="dw_action" value="flush_cache">پاک‌سازی کش</button>
				<button class="button" type="submit" name="dw_action" value="compile">ساخت مجدد توکن‌ها</button>
			</form>
		</div>
	</div>
</div>
