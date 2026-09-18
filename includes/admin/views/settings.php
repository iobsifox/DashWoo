<?php
/**
 * Settings Center: schema-driven form.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';

$dw_key      = $context['section'];
$dw_section  = $context['sections'][ $dw_key ];
$dw_values   = isset( $context['settings'][ $dw_key ] ) ? $context['settings'][ $dw_key ] : array();
$dw_admin    = \DashWoo\Admin\Admin::instance();
?>
	<div class="dw-grid dw-grid--main">
		<div class="dw-card dw-card--form">
			<h2><?php echo esc_html( $dw_section['label'] ); ?></h2>
			<?php if ( ! empty( $dw_section['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $dw_section['description'] ); ?></p>
			<?php endif; ?>

			<?php if ( empty( $dw_section['fields'] ) ) : ?>
				<p>این بخش فیلد تنظیماتی ندارد؛ از طریق ابزارهای زیر مدیریت می‌شود.</p>
			<?php else : ?>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<input type="hidden" name="dw_action" value="save_settings" />
				<input type="hidden" name="section" value="<?php echo esc_attr( $dw_key ); ?>" />

				<table class="form-table dw-form">
					<tbody>
					<?php foreach ( (array) $dw_section['fields'] as $dw_field ) : ?>
						<?php
						$dw_name  = 'dw[' . $dw_field['key'] . ']';
						$dw_value = $dw_values[ $dw_field['key'] ] ?? $dw_field['default'];
						?>
						<tr>
							<th scope="row"><label for="dw-<?php echo esc_attr( $dw_field['key'] ); ?>"><?php echo esc_html( $dw_field['label'] ); ?></label></th>
							<td>
								<?php if ( 'toggle' === $dw_field['type'] ) : ?>
									<label class="dw-switch">
										<input type="checkbox" id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>"
											name="<?php echo esc_attr( $dw_name ); ?>" value="1" <?php checked( (bool) $dw_value ); ?> />
										<span>فعال</span>
									</label>

								<?php elseif ( 'select' === $dw_field['type'] ) : ?>
									<select id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>" name="<?php echo esc_attr( $dw_name ); ?>">
										<?php foreach ( (array) $dw_field['options'] as $dw_option => $dw_label ) : ?>
											<option value="<?php echo esc_attr( $dw_option ); ?>" <?php selected( (string) $dw_value, (string) $dw_option ); ?>>
												<?php echo esc_html( $dw_label ); ?>
											</option>
										<?php endforeach; ?>
									</select>

								<?php elseif ( 'multi_select' === $dw_field['type'] ) : ?>
									<select id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>" name="<?php echo esc_attr( $dw_name ); ?>[]" multiple size="6">
										<?php foreach ( (array) $dw_field['options'] as $dw_option => $dw_label ) : ?>
											<option value="<?php echo esc_attr( $dw_option ); ?>"
												<?php echo in_array( (string) $dw_option, array_map( 'strval', (array) $dw_value ), true ) ? 'selected' : ''; ?>>
												<?php echo esc_html( $dw_label ); ?>
											</option>
										<?php endforeach; ?>
									</select>

								<?php elseif ( 'color' === $dw_field['type'] ) : ?>
									<input type="text" class="dw-color" id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>"
										name="<?php echo esc_attr( $dw_name ); ?>" value="<?php echo esc_attr( (string) $dw_value ); ?>"
										placeholder="#000000" data-color-picker="1" />

								<?php elseif ( 'number' === $dw_field['type'] ) : ?>
									<input type="number" id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>"
										name="<?php echo esc_attr( $dw_name ); ?>" value="<?php echo esc_attr( (string) $dw_value ); ?>"
										min="<?php echo esc_attr( (string) ( $dw_field['min'] ?? '' ) ); ?>"
										max="<?php echo esc_attr( (string) ( $dw_field['max'] ?? '' ) ); ?>"
										step="<?php echo esc_attr( (string) ( $dw_field['step'] ?? 1 ) ); ?>" />

								<?php elseif ( 'textarea' === $dw_field['type'] ) : ?>
									<textarea id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>" name="<?php echo esc_attr( $dw_name ); ?>" rows="5" class="large-text"><?php echo esc_textarea( (string) $dw_value ); ?></textarea>

								<?php elseif ( 'code' === $dw_field['type'] ) : ?>
									<textarea id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>" name="<?php echo esc_attr( $dw_name ); ?>" rows="8" class="large-text code" dir="ltr"><?php echo esc_textarea( (string) $dw_value ); ?></textarea>

								<?php else : ?>
									<input type="text" class="regular-text" id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>"
										name="<?php echo esc_attr( $dw_name ); ?>" value="<?php echo esc_attr( (string) $dw_value ); ?>" />
								<?php endif; ?>

								<?php if ( ! empty( $dw_field['description'] ) ) : ?>
									<p class="description"><?php echo esc_html( $dw_field['description'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p class="dw-actions">
					<button class="button button-primary" type="submit">ذخیره تنظیمات</button>
					<button class="button" type="submit" name="dw_action" value="reset_section">بازنشانی به پیش‌فرض</button>
				</p>
			</form>
			<?php endif; ?>
		</div>

		<div class="dw-card dw-card--aside">
			<h3>بخش‌ها</h3>
			<ul class="dw-list">
				<?php foreach ( $context['groups'] as $dw_group => $dw_keys ) : ?>
					<li><strong><?php echo esc_html( $context['group_labels'][ $dw_group ]['label'] ?? $dw_group ); ?></strong>
						<ul>
						<?php foreach ( $dw_keys as $dw_item ) : ?>
							<li><a href="<?php echo esc_url( $dw_admin->url( $dw_item ) ); ?>"><?php echo esc_html( $context['sections'][ $dw_item ]['label'] ); ?></a></li>
						<?php endforeach; ?>
						</ul>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
