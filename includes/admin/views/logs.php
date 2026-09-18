<?php
/**
 * Logs view.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';
?>
	<div class="dw-card">
		<h2>گزارش‌ها <small>(<?php echo count( (array) $context['logs'] ); ?> ردیف آخر)</small></h2>
		<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" class="dw-inline">
			<?php wp_nonce_field( 'dashwoo_action' ); ?>
			<input type="hidden" name="action" value="dashwoo_action" />
			<button class="button" type="submit" name="dw_action" value="clear_logs">پاک کردن گزارش‌ها</button>
		</form>

		<table class="widefat striped dw-table">
			<thead><tr><th style="width:180px">زمان</th><th style="width:100px">سطح</th><th>پیام</th><th>Context</th></tr></thead>
			<tbody>
			<?php foreach ( (array) $context['logs'] as $dw_row ) : ?>
				<tr>
					<td><?php echo esc_html( $dw_row['time'] ?? '' ); ?></td>
					<td><span class="dw-pill dw-pill--<?php echo esc_attr( (string) ( $dw_row['level'] ?? 'info' ) ); ?>"><?php echo esc_html( $dw_row['level'] ?? '' ); ?></span></td>
					<td><?php echo esc_html( $dw_row['message'] ?? '' ); ?></td>
					<td><code><?php echo esc_html( (string) wp_json_encode( $dw_row['context'] ?? array() ) ); ?></code></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
