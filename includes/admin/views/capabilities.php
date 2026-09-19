<?php
/**
 * Capabilities screen: what the host provides and which DashWoo features that gates.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';

$dw_status = isset( $context['capabilities'] ) ? $context['capabilities'] : array();
$dw_checks = isset( $dw_status['checks'] ) ? $dw_status['checks'] : array();
$dw_feat   = isset( $dw_status['features'] ) ? $dw_status['features'] : array();
$dw_admin  = \DashWoo\Admin\Admin::instance();

$dw_labels = array(
	'on'      => array( 'روشن', 'dw-pill--ok' ),
	'off'     => array( 'خاموش با انتخاب شما', 'dw-pill--warning' ),
	'blocked' => array( 'خودکار خاموش (قابلیت هاست نیست)', 'dw-pill--info' ),
);

$dw_on      = 0;
$dw_blocked = 0;

foreach ( $dw_feat as $dw_feature ) {
	if ( 'on' === $dw_feature['state'] ) {
		$dw_on++;
	}
	if ( 'blocked' === $dw_feature['state'] ) {
		$dw_blocked++;
	}
}
?>
	<div class="dw-grid dw-grid--4">
		<div class="dw-card">
			<p class="description">قابلیت‌های موجود هاست</p>
			<p class="dw-kpi"><?php echo (int) count( array_filter( $dw_checks, static function ( $c ) { return ! empty( $c['available'] ); } ) ); ?> / <?php echo (int) count( $dw_checks ); ?></p>
		</div>
		<div class="dw-card">
			<p class="description">ویژگی‌های فعال</p>
			<p class="dw-kpi"><?php echo (int) $dw_on; ?> / <?php echo (int) count( $dw_feat ); ?></p>
		</div>
		<div class="dw-card">
			<p class="description">خودکار خاموش‌شده</p>
			<p class="dw-kpi"><?php echo (int) $dw_blocked; ?></p>
		</div>
		<div class="dw-card">
			<p class="description">آخرین بررسی خودکار</p>
			<p class="dw-kpi"><?php echo esc_html( isset( $dw_status['checked_human'] ) ? $dw_status['checked_human'] : '—' ); ?></p>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<input type="hidden" name="dw_action" value="capabilities_recheck" />
				<button class="button button-primary" type="submit">بررسی مجدد قابلیت‌ها</button>
			</form>
		</div>
	</div>

	<div class="dw-card">
		<h2>ویژگی‌های DashWoo و وضعیت خودکار آن‌ها</h2>
		<p class="description">
			DashWoo هیچ‌گاه به قابلیت‌های اختیاری هاست وابسته نیست: اگر هاست چیزی را نداشته باشد،
			ویژگی مربوطه خودش خاموش می‌ماند و به‌محض فراهم شدن، <strong>خودکار روشن می‌شود</strong>.
		</p>

		<table class="widefat striped dw-table">
			<thead>
				<tr>
					<th>ویژگی</th>
					<th>وضعیت</th>
					<th>نیازمند قابلیت</th>
					<th>اگر خاموش باشد چه می‌شود</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $dw_feat as $dw_id => $dw_feature ) : ?>
					<?php $dw_state = isset( $dw_labels[ $dw_feature['state'] ] ) ? $dw_labels[ $dw_feature['state'] ] : array( $dw_feature['state'], 'dw-pill' ); ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $dw_feature['label'] ); ?></strong>
							<div class="dw-hint"><?php echo esc_html( $dw_feature['effect'] ); ?></div>
						</td>
						<td><span class="dw-pill <?php echo esc_attr( $dw_state[1] ); ?>"><?php echo esc_html( $dw_state[0] ); ?></span></td>
						<td>
							<?php if ( empty( $dw_feature['requires'] ) ) : ?>
								<span class="dw-pill">همیشه فعال</span>
							<?php else : ?>
								<?php foreach ( $dw_feature['requires'] as $dw_req ) : ?>
									<span class="dw-pill <?php echo ! empty( $dw_checks[ $dw_req ]['available'] ) ? 'dw-pill--ok' : 'dw-pill--info'; ?>">
										<?php echo esc_html( $dw_checks[ $dw_req ]['label'] ?? $dw_req ); ?>
									</span>
								<?php endforeach; ?>
							<?php endif; ?>
						</td>
						<td>
							<?php echo esc_html( $dw_feature['when_off'] ); ?>
							<?php if ( 'blocked' === $dw_feature['state'] ) : ?>
								<div class="dw-hint"><?php echo esc_html( $dw_feature['hint'] ); ?></div>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="dw-card">
		<h2>قابلیت‌های هاست</h2>
		<p class="description">این جدول هر ۶ ساعت خودکار و با دکمهٔ بالا دستی بررسی می‌شود.</p>

		<table class="widefat striped dw-table">
			<thead>
				<tr>
					<th>قابلیت</th>
					<th>وضعیت</th>
					<th>جزئیات</th>
					<th>نقش در DashWoo</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $dw_checks as $dw_id => $dw_check ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $dw_check['label'] ); ?></strong>
							<?php if ( ! empty( $dw_check['required'] ) ) : ?>
								<span class="dw-pill dw-pill--err">الزامی</span>
							<?php else : ?>
								<span class="dw-pill dw-pill--info">اختیاری</span>
							<?php endif; ?>
						</td>
						<td>
							<span class="dw-pill <?php echo ! empty( $dw_check['available'] ) ? 'dw-pill--ok' : 'dw-pill--info'; ?>">
								<?php echo ! empty( $dw_check['available'] ) ? '✓' : 'ℹ'; ?>
							</span>
						</td>
						<td><?php echo esc_html( $dw_check['detail'] ); ?></td>
						<td><div class="dw-hint"><?php echo esc_html( $dw_check['hint'] ); ?></div></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h3>متن آمادهٔ ارسال به هاست</h3>
		<p class="description">اگر ویژگی‌ای خودکار خاموش مانده، این متن را کپی کنید و به پشتیبانی هاست بدهید.</p>
		<textarea class="dw-diagnostics" rows="10" readonly><?php
		foreach ( $dw_checks as $dw_id => $dw_check ) {
			printf(
				"%-18s %-4s %s\n",
				$dw_id,
				! empty( $dw_check['available'] ) ? 'on' : 'off',
				$dw_check['detail']
			);
		}
		?></textarea>
	</div>

	<div class="dw-card">
		<h2>اعلان سازگاری با ویژگی‌های ووکامرس</h2>
		<?php $dw_wc = \DashWoo\Compatibility\WooCommerce_Features::instance()->status(); ?>

		<?php if ( empty( $dw_wc['aware'] ) ) : ?>
			<p class="description">ووکامرس فعال نیست؛ اعلانی لازم نیست. به‌محض فعال شدن، DashWoo سازگاری خود را روی قلاب
				<code>before_woocommerce_init</code> اعلام می‌کند.</p>
		<?php else : ?>
			<p class="description">
				ووکامرس فقط افزونه‌هایی را بررسی می‌کند که هدر <code>WC tested up to</code> دارند و افزونهٔ اعلام‌نکرده را
				برای ویژگی‌هایی مثل HPOS «ناسازگار» می‌شمارد. DashWoo برای همهٔ ویژگی‌ها اعلام سازگاری می‌کند.
			</p>
			<ul class="dw-list">
				<li>اعلام‌شده در این درخواست: <strong><?php echo (int) count( (array) $dw_wc['declared'] ); ?></strong></li>
				<li>ووکامرس DashWoo را سازگار می‌داند: <strong><?php echo (int) count( (array) $dw_wc['compatible'] ); ?></strong></li>
				<li>ناسازگار: <strong><?php echo (int) count( (array) $dw_wc['incompatible'] ); ?></strong>
					<?php if ( ! empty( $dw_wc['incompatible'] ) ) : ?>
						— <?php echo esc_html( implode( ', ', $dw_wc['incompatible'] ) ); ?>
					<?php endif; ?></li>
				<li>نامعلوم نزد ووکامرس: <strong><?php echo (int) count( (array) $dw_wc['uncertain'] ); ?></strong></li>
				<li>خارج از فهرست ممیزی DashWoo: <strong><?php echo (int) count( (array) $dw_wc['unknown'] ); ?></strong>
					<?php if ( ! empty( $dw_wc['unknown'] ) ) : ?>
						— <?php echo esc_html( implode( ', ', $dw_wc['unknown'] ) ); ?>
					<?php endif; ?></li>
			</ul>
		<?php endif; ?>

		<p>
			<a class="button" href="<?php echo esc_url( $dw_admin->url( 'features' ) ); ?>">تنظیم دستی ویژگی‌ها</a>
			<a class="button" href="<?php echo esc_url( $dw_admin->url( 'system' ) ); ?>">مشاهدهٔ گزارش کامل سازگاری</a>
		</p>
	</div>
</div>
