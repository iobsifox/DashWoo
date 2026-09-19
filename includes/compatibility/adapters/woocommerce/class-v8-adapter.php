<?php
/**
 * WooCommerce 8.x implementation of the commerce adapter.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility\Adapters\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Woo 8.x behaviour.
 *
 * Not final on purpose: V9_Adapter extends it and only overrides what changed.
 */
class V8_Adapter {

	/**
	 * Template hook set for the single product page.
	 *
	 * @return array<int,string>
	 */
	public function single_product_hooks() {
		return array(
			'woocommerce_before_single_product_summary',
			'woocommerce_single_product_summary',
			'woocommerce_after_single_product_summary',
		);
	}

	/**
	 * Cart page hook set.
	 *
	 * @return array<int,string>
	 */
	public function cart_hooks() {
		return array(
			'woocommerce_before_cart',
			'woocommerce_cart_collaterals',
			'woocommerce_after_cart',
		);
	}

	/**
	 * Checkout hook set (classic checkout in 8.x).
	 *
	 * @return array<int,string>
	 */
	public function checkout_hooks() {
		return array(
			'woocommerce_before_checkout_form',
			'woocommerce_checkout_before_customer_details',
			'woocommerce_after_checkout_form',
		);
	}

	/**
	 * Amount available as a float.
	 *
	 * @param string $amount Amount string.
	 * @return float
	 */
	public function to_amount( $amount ) {
		if ( function_exists( 'wc_format_decimal' ) ) {
			return (float) wc_format_decimal( $amount );
		}

		return (float) $amount;
	}

	/**
	 * Price HTML for a product id.
	 *
	 * @param int $product_id Product id.
	 * @return string
	 */
	public function price_html( $product_id ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );

		return $product ? $product->get_price_html() : '';
	}

	/**
	 * Cart item count.
	 *
	 * @return int
	 */
	public function cart_count() {
		if ( function_exists( 'WC' ) && WC()->cart ) {
			return (int) WC()->cart->get_cart_contents_count();
		}

		return 0;
	}
}
