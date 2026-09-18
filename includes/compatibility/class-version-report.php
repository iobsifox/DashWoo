<?php
/**
 * Aggregated compatibility report.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Value object built from every adapter report.
 */
final class Version_Report {

	/**
	 * Adapter reports keyed by adapter id.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private $adapters;

	/**
	 * Timestamp.
	 *
	 * @var int
	 */
	private $generated_at;

	/**
	 * Constructor.
	 *
	 * @param array<string,array<string,mixed>> $adapters Adapter reports.
	 * @param int                               $generated_at Timestamp.
	 */
	public function __construct( array $adapters, $generated_at = 0 ) {
		$this->adapters     = $adapters;
		$this->generated_at = $generated_at ? (int) $generated_at : time();
	}

	/**
	 * Adapter reports.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function adapters() {
		return $this->adapters;
	}

	/**
	 * Timestamp.
	 *
	 * @return int
	 */
	public function generated_at() {
		return $this->generated_at;
	}

	/**
	 * Worst status across all adapters.
	 *
	 * @return string
	 */
	public function status() {
		$worst = Abstract_Adapter::STATUS_OK;
		$order = array(
			Abstract_Adapter::STATUS_OK,
			Abstract_Adapter::STATUS_WARNING,
			Abstract_Adapter::STATUS_ERROR,
		);

		foreach ( $this->adapters as $report ) {
			$status = isset( $report['status'] ) ? $report['status'] : Abstract_Adapter::STATUS_NA;

			// "Not applicable" is neutral: it must not escalate the status.
			if ( Abstract_Adapter::STATUS_NA === $status ) {
				continue;
			}

			if ( array_search( $status, $order, true ) > array_search( $worst, $order, true ) ) {
				$worst = $status;
			}
		}

		return $worst;
	}

	/**
	 * Compatibility Mode must be forced?
	 *
	 * Rules:
	 *  - any error => force
	 *  - more than one warning from *required* adapters => force
	 *  - warnings from optional components (no WooCommerce / no Elementor) are
	 *    surfaced in the admin notice but must not downgrade the whole site
	 *
	 * @return bool
	 */
	public function requires_compatibility_mode() {
		$errors   = 0;
		$warnings = 0;

		foreach ( $this->adapters as $report ) {
			$status = isset( $report['status'] ) ? $report['status'] : Abstract_Adapter::STATUS_NA;

			if ( Abstract_Adapter::STATUS_ERROR === $status ) {
				$errors++;
				continue;
			}

			// Warnings only count when the component is required.
			if ( Abstract_Adapter::STATUS_WARNING === $status && ( ! array_key_exists( 'required', $report ) || $report['required'] ) ) {
				$warnings++;
			}
		}

		return $errors > 0 || $warnings > 1;
	}

	/**
	 * Flat list of checks: [{adapter, id, label, status, symbol, message}, ...]
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function checks() {
		$out = array();

		foreach ( $this->adapters as $adapter_id => $report ) {
			foreach ( (array) ( $report['checks'] ?? array() ) as $check_id => $check ) {
				$out[] = array(
					'adapter' => $adapter_id,
					'id'      => $check_id,
					'label'   => isset( $check['label'] ) ? $check['label'] : $check_id,
					'status'  => isset( $check['status'] ) ? $check['status'] : Abstract_Adapter::STATUS_NA,
					'symbol'  => isset( $check['symbol'] ) ? $check['symbol'] : Abstract_Adapter::STATUS_NA,
					'message' => isset( $check['message'] ) ? $check['message'] : '',
				);
			}
		}

		return $out;
	}

	/**
	 * Counts by status.
	 *
	 * @return array<string,int>
	 */
	public function summary() {
		$counts = array(
			'ok'      => 0,
			'warning' => 0,
			'error'   => 0,
			'na'      => 0,
		);

		foreach ( $this->checks() as $check ) {
			$status = $check['status'];
			if ( isset( $counts[ $status ] ) ) {
				$counts[ $status ]++;
			}
		}

		$counts['total'] = count( $this->checks() );

		return $counts;
	}

	/**
	 * Degraded feature keys across all adapters.
	 *
	 * @return array<int,string>
	 */
	public function degraded() {
		$out = array();

		foreach ( $this->adapters as $report ) {
			foreach ( (array) ( $report['degraded'] ?? array() ) as $feature ) {
				$out[] = $feature;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * Array shape (option payload / REST response).
	 *
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return array(
			'status'         => $this->status(),
			'generated_at'   => $this->generated_at,
			'adapters'       => $this->adapters,
			'checks'         => $this->checks(),
			'summary'        => $this->summary(),
			'degraded'       => $this->degraded(),
			'compatibility'  => $this->requires_compatibility_mode() ? 'compatibility' : 'full',
		);
	}
}
