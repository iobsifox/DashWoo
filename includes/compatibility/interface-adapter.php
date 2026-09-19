<?php
/**
 * Contract every compatibility adapter must satisfy.
 *
 * Widgets and templates never touch WordPress / WooCommerce / Elementor internals
 * directly - they only ask the adapter. That is what makes DashWoo survive plugin updates.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Adapter contract.
 */
interface Interface_Adapter {

	/**
	 * Machine name, e.g. "woocommerce".
	 *
	 * @return string
	 */
	public function id();

	/**
	 * Human readable name.
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Is the target component present and usable?
	 *
	 * @return bool
	 */
	public function is_active();

	/**
	 * Detected version (empty string when not detected).
	 *
	 * @return string
	 */
	public function version();

	/**
	 * Is the component required for the plugin to run in full mode?
	 *
	 * WooCommerce and Elementor are optional: when they are missing DashWoo
	 * reports a warning but keeps working, so their warnings must not force
	 * Compatibility Mode all on their own.
	 *
	 * @return bool
	 */
	public function is_required();

	/**
	 * Version DashWoo was tested against.
	 *
	 * @return string
	 */
	public function tested_up_to();

	/**
	 * Version report: status + checks + list of unavailable features.
	 *
	 * @return array{status:string,checks:array<string,array<string,mixed>>,degraded:array<int,string>}
	 */
	public function report();
}
