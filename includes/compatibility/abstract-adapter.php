<?php
/**
 * Shared behaviour for every adapter.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Base adapter: version comparison, check collection, capability degradation.
 */
abstract class Abstract_Adapter implements Interface_Adapter {

	const STATUS_OK      = 'ok';
	const STATUS_NOTICE  = 'notice';
	const STATUS_WARNING = 'warning';
	const STATUS_ERROR   = 'error';
	const STATUS_NA      = 'na';

	/**
	 * Collected checks.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected $checks = array();

	/**
	 * Runtime capability map (feature => bool).
	 *
	 * @var array<string,bool>
	 */
	protected $capabilities = array();

	/**
	 * Version report.
	 *
	 * @return array{status:string,checks:array<string,array<string,mixed>>,degraded:array<int,string>}
	 */
	public function report() {
		$this->checks       = array();
		$this->capabilities = array();

		$this->inspect();

		return array(
			'status'   => $this->worst_status(),
			'checks'   => $this->checks,
			'degraded' => $this->degraded_features(),
		);
	}

	/**
	 * Subclasses collect their checks + capabilities here.
	 *
	 * @return void
	 */
	abstract protected function inspect();

	/**
	 * Required by default; optional adapters override this.
	 *
	 * @return bool
	 */
	public function is_required() {
		return true;
	}

	/**
	 * Register an *informational* row.
	 *
	 * Notices never escalate a status and never flip the compatibility mode: they
	 * exist so the System Status screen can describe the environment completely
	 * (for example: "this host has no ZipArchive") without pretending that
	 * something is broken. DashWoo keeps working without these capabilities.
	 *
	 * @param string              $id      Check id.
	 * @param string              $message Human readable message.
	 * @param array<string,mixed> $extra   Extra payload (hint, available, …).
	 * @return void
	 */
	protected function notice( $id, $message, $extra = array() ) {
		$this->check( $id, self::STATUS_NOTICE, $message, $extra );
	}

	/**
	 * Register a check row.
	 *
	 * @param string              $id      Check id.
	 * @param string              $status  One of the STATUS_* constants.
	 * @param string              $message Human readable message.
	 * @param array<string,mixed> $extra   Extra payload.
	 * @return void
	 */
	protected function check( $id, $status, $message, $extra = array() ) {
		$this->checks[ $id ] = array(
			'label'   => $id,
			'status'  => $status,
			'message' => $message,
			'symbol'  => $this->symbol( $status ),
			'hint'    => isset( $extra['hint'] ) ? (string) $extra['hint'] : '',
			'extra'   => $extra,
		);
	}

	/**
	 * The ✓ / ⚠ / ✕ / – glyph for a status.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public function symbol( $status ) {
		$map = array(
			self::STATUS_OK      => "\xe2\x9c\x93", // check mark.
			self::STATUS_WARNING => "\xe2\x9a\xa0", // warning sign.
			self::STATUS_ERROR   => "\xe2\x9c\x95", // cross mark.
			self::STATUS_NA      => "\xe2\x80\x93", // en dash.
			self::STATUS_NOTICE  => "\xe2\x84\xb9", // information source (U+2139).
		);

		return isset( $map[ $status ] ) ? $map[ $status ] : $map[ self::STATUS_NA ];
	}

	/**
	 * Mark a feature as available or degraded.
	 *
	 * @param string $feature Feature key (e.g. "variable_fonts").
	 * @param bool   $enabled Available?
	 * @return void
	 */
	protected function capability( $feature, $enabled ) {
		$this->capabilities[ $feature ] = (bool) $enabled;
	}

	/**
	 * Is a feature available?
	 *
	 * @param string $feature Feature key.
	 * @return bool
	 */
	public function supports( $feature ) {
		if ( ! $this->capabilities ) {
			$this->report();
		}

		return ! empty( $this->capabilities[ $feature ] );
	}

	/**
	 * Full capability map.
	 *
	 * @return array<string,bool>
	 */
	public function capabilities() {
		if ( ! $this->capabilities ) {
			$this->report();
		}

		return $this->capabilities;
	}

	/**
	 * Feature keys that are NOT available.
	 *
	 * @return array<int,string>
	 */
	public function degraded_features() {
		$out = array();

		foreach ( $this->capabilities as $feature => $enabled ) {
			if ( ! $enabled ) {
				$out[] = $feature;
			}
		}

		return $out;
	}

	/**
	 * Worst status among the collected checks.
	 *
	 * @return string
	 */
	protected function worst_status() {
		$order = array( self::STATUS_OK, self::STATUS_WARNING, self::STATUS_ERROR );
		$worst = self::STATUS_OK;

		foreach ( $this->checks as $check ) {
			$status = isset( $check['status'] ) ? $check['status'] : self::STATUS_NA;

			// "Not applicable" and "informational" are neutral: neither may
			// escalate the status of the adapter.
			if ( self::STATUS_NA === $status || self::STATUS_NOTICE === $status ) {
				continue;
			}

			if ( array_search( $status, $order, true ) > array_search( $worst, $order, true ) ) {
				$worst = $status;
			}
		}

		return $worst;
	}

	/**
	 * Normalised version_compare with a sane fallback for weird version strings.
	 *
	 * @param string $version Current version.
	 * @param string $minimum Minimum version.
	 * @return bool
	 */
	public function meets( $version, $minimum ) {
		$version = $this->normalize_version( $version );

		if ( '' === $version || '' === $minimum ) {
			return false;
		}

		return version_compare( $version, $minimum, '>=' );
	}

	/**
	 * "9.9.0-beta.1" => "9.9.0-beta.1"; strips prefixes like "v" or "Version ".
	 *
	 * @param string $version Raw version.
	 * @return string
	 */
	public function normalize_version( $version ) {
		$version = trim( (string) $version );
		$version = preg_replace( '/^(version|v)\s*/i', '', $version );

		return trim( (string) $version );
	}
}
