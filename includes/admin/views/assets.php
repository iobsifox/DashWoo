<?php
/**
 * Asset Manager screen.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';

$dw_admin   = \DashWoo\Admin\Admin::instance();
$dw_type    = $context['type'];
$dw_rows    = $context['rows'];
$dw_storage = $context['storage'];
$dw_tabs    = array(
	'font'   => 'فونت‌ها',
	'icon'   => 'آیکون‌ها',
	'image'  => 'تصاویر',
	'svg'    => 'SVG',
	'custom' => 'CSS/JS',
);
?>
	<nav class="dw-subnav">
		<?php foreach ( $dw_tabs as $dw_slug => $dw_label ) : ?>
			<a class="dw-nav__item <?php echo $dw_type === $dw_slug ? 'is-active' : ''; ?>"
				href="<?php echo esc_url( add_query_arg( array( 'page' => 'dashwoo-assets', 'type' => $dw_slug ), admin_url( 'admin.php' ) ) ); ?>">
				<?php echo esc_html( $dw_label ); ?>
				<span class="dw-count"><?php echo (int) ( $dw_storage['counts'][ $dw_slug ] ?? 0 ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'font' === $dw_type ) : ?>
		<div class="dw-grid dw-grid--2">
			<div class="dw-card">
				<h2>افزودن فونت از Google Fonts</h2>
				<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>">
					<?php wp_nonce_field( 'dashwoo_action' ); ?>
					<input type="hidden" name="action" value="dashwoo_action" />
					<input type="hidden" name="dw_action" value="install_font" />
					<p>
						<label for="dw-family">فونت</label>
						<select id="dw-family" name="family" class="dw-select-search" data-search="1">
							<?php foreach ( (array) $context['catalog'] as $dw_font ) : ?>
								<option value="<?php echo esc_attr( $dw_font['family'] ); ?>">
									<?php echo esc_html( $dw_font['family'] . ( $dw_font['persian'] ? ' — فارسی' : '' ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>
					<p>
						<label>وزن‌ها</label><br />
						<?php foreach ( array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ) as $dw_weight ) : ?>
							<label class="dw-inline"><input type="checkbox" name="weights[]" value="<?php echo esc_attr( $dw_weight ); ?>"
								<?php checked( in_array( $dw_weight, array( '400', '500', '700' ), true ) ); ?> /> <?php echo esc_html( $dw_weight ); ?></label>
						<?php endforeach; ?>
					</p>
					<button class="button button-primary" type="submit">دانلود و ذخیره محلی</button>
					<p class="description">پس از دانلود، فایل‌ها در <code>uploads/dashwoo/fonts/</code> قرار می‌گیرند و هیچ درخواستی به گوگل ارسال نمی‌شود.</p>
				</form>
			</div>

			<div class="dw-card">
				<h2>آپلود فونت اختصاصی</h2>
				<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'dashwoo_action' ); ?>
					<input type="hidden" name="action" value="dashwoo_action" />
					<input type="hidden" name="dw_action" value="upload_font" />
					<p><input type="text" name="label" placeholder="نام خانوادگی فونت" class="regular-text" required /></p>
					<p><input type="file" name="font_file" accept=".woff2,.woff,.ttf,.otf" required /></p>
					<button class="button" type="submit">آپلود</button>
				</form>
			</div>
		</div>
	<?php elseif ( 'icon' === $dw_type ) : ?>
		<div class="dw-card">
			<h2>Material Symbols و Material Icons</h2>
			<p class="description">
				Material Symbols یک <strong>فونت متغیر</strong> است: برای هر سبک فقط یک فایل WOFF2 دانلود می‌شود و
				Weight / Fill / Grade / Optical Size در زمان رندر با <code>font-variation-settings</code> تنظیم می‌شوند.
			</p>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" class="dw-inline">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<input type="hidden" name="dw_action" value="install_icons" />
				<select name="style">
					<?php foreach ( (array) $context['icon_styles'] as $dw_style => $dw_label ) : ?>
						<option value="<?php echo esc_attr( $dw_style ); ?>"><?php echo esc_html( $dw_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<label class="dw-inline"><input type="checkbox" name="force" value="1" /> دانلود مجدد</label>
				<button class="button button-primary" type="submit">دانلود</button>
			</form>
		</div>
	<?php endif; ?>

	<div class="dw-card">
		<h2>دارایی‌های ثبت‌شده (<?php echo esc_html( $dw_tabs[ $dw_type ] ?? $dw_type ); ?>)</h2>
		<table class="widefat striped dw-table">
			<thead>
				<tr>
					<th>نام</th><th>اسلاگ</th><th>ارائه‌دهنده</th><th>نسخه</th><th>وضعیت</th>
					<th>پیش‌فرض</th><th>حجم</th><th>آخرین تغییر</th><th>عملیات</th>
				</tr>
			</thead>
			<tbody>
			<?php if ( ! $dw_rows ) : ?>
				<tr><td colspan="9">هنوز دارایی‌ای ثبت نشده است.</td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $dw_rows as $dw_row ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $dw_row['label'] ); ?></strong></td>
					<td><code><?php echo esc_html( $dw_row['slug'] ); ?></code></td>
					<td><?php echo esc_html( $dw_row['provider'] ); ?></td>
					<td><code><?php echo esc_html( $dw_row['version'] ); ?></code></td>
					<td><?php echo 'active' === $dw_row['status'] ? '<span class="dw-pill dw-pill--ok">فعال</span>' : '<span class="dw-pill">غیرفعال</span>'; ?></td>
					<td><?php echo $dw_row['is_default'] ? '✓' : '—'; ?></td>
					<td><?php echo esc_html( size_format( (int) $dw_row['size'] ) ); ?></td>
					<td><?php echo esc_html( $dw_row['updated_at'] ); ?></td>
					<td class="dw-row-actions">
						<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" class="dw-inline">
							<?php wp_nonce_field( 'dashwoo_action' ); ?>
							<input type="hidden" name="action" value="dashwoo_action" />
							<input type="hidden" name="slug" value="<?php echo esc_attr( $dw_row['slug'] ); ?>" />
							<button class="button button-small" type="submit" name="dw_action" value="update_font">به‌روزرسانی</button>
							<button class="button button-small" type="submit" name="dw_action" value="delete_font">حذف</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
