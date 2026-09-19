<?php
/**
 * WooCommerce 9.x implementation of the commerce adapter.
 *
 * Extends the 8.x behaviour: in 9.x the classic checkout still exists but
 * Blocks checkout is the default, so extra guards are needed.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility\Adapters\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Woo 9.x behaviour.
 */
final class V9_Adapter extends V8_Adapter {

	/**
	 * 9.x exposes both classic + block checkout hooks.
	 *
	 * @return array<int,string>
	 */
	public function checkout_hooks() {
		return array_merge(
			parent::checkout_hooks(),
			array(
				'woocommerce_blocks_checkout_before_order_review',
				'woocommerce_blocks_checkout_after_order_review',
			)
		);
	}

	/**
	 * Is the block checkout the active checkout?
	 *
	 * @return bool
	 */
	public function is_block_checkout() {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return false;
		}

		$page_id = wc_get_page_id( 'checkout' );

		return $page_id > 0 && has_block( 'woocommerce/checkout', $page_id );
	}

	/**
	 * HPOS-aware order status read.
	 *
	 * @param int $order_id Order id.
	 * @return string
	 */
	public function order_status( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return '';
		}

		$order = wc_get_order( $order_id );

		return $order ? $order->get_status() : '';
	}
}
