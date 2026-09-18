<?php
/**
 * Ring-buffer logger (stored in a single option, capped).
 *
 * @package DashWoo
 */

namespace DashWoo\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Lightweight logger.
 */
final class Logger {

	const OPTION   = 'dashwoo_log';
	const MAX_ROWS = 200;

	/**
	 * Singleton.
	 *
	 * @var Logger|null
	 */
	private static $instance = null;

	/**
	 * In-request buffer.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $buffer = array();

	/**
	 * Singleton accessor.
	 *
	 * @return Logger
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'dashwoo_log_flush', array( $this, 'flush' ) );
	}

	/**
	 * Write a log row.
	 *
	 * @param string              $level   debug|info|warning|error.
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	public function log( $level, $message, $context = array() ) {
		$row = array(
			'time'    => gmdate( 'c' ),
			'level'   => (string) $level,
			'message' => (string) $message,
			'context' => $context,
		);

		$this->buffer[] = $row;

		/**
		 * Fires after a DashWoo log row was created.
		 *
		 * @param array<string,mixed> $row Log row.
		 */
		do_action( 'dashwoo_log', $row );
	}

	/**
	 * Convenience helpers.
	 *
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	public function debug( $message, $context = array() ) {
		$this->log( 'debug', $message, $context );
	}

	/**
	 * Info level.
	 *
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	public function info( $message, $context = array() ) {
		$this->log( 'info', $message, $context );
	}

	/**
	 * Warning level.
	 *
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	public function warning( $message, $context = array() ) {
		$this->log( 'warning', $message, $context );
	}

	/**
	 * Error level.
	 *
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	public function error( $message, $context = array() ) {
		$this->log( 'error', $message, $context );
	}

	/**
	 * Persist the buffer to the option ring buffer.
	 *
	 * @return int Number of rows now stored.
	 */
	public function flush() {
		if ( ! $this->buffer ) {
			return count( (array) get_option( self::OPTION, array() ) );
		}

		$stored = (array) get_option( self::OPTION, array() );
		$stored = array_merge( $stored, $this->buffer );
		$stored = array_slice( $stored, -self::MAX_ROWS );

		update_option( self::OPTION, $stored, false );
		$this->buffer = array();

		return count( $stored );
	}

	/**
	 * Read stored rows (newest last).
	 *
	 * @param int $limit Max rows.
	 * @return array<int,array<string,mixed>>
	 */
	public function all( $limit = 50 ) {
		// Rows created during this request are shown as well, even before the
		// shutdown flush, so the admin screen is never stale.
		$stored = array_merge( (array) get_option( self::OPTION, array() ), $this->buffer );

		if ( $limit > 0 ) {
			$stored = array_slice( $stored, -$limit );
		}

		return array_values( $stored );
	}

	/**
	 * Drop all rows.
	 *
	 * @return void
	 */
	public function clear() {
		$this->buffer = array();
		update_option( self::OPTION, array(), false );
	}

	/**
	 * Reset for tests.
	 *
	 * @return void
	 */
	public function reset() {
		$this->buffer = array();
	}
}
