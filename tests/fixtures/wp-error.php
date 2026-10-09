<?php
/**
 * Minimal native error double for the isolated updater contract test.
 *
 * @package ITD_Cookies
 */

/** Preserve the error code; real Core behavior is covered by integration tests. */
class WP_Error {
	/** @var string */
	private $code;
	/**
	 * Initialize the error double.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 */
	public function __construct( $code, $message ) {
		$this->code = $code;
	}
	/**
	 * Return the error code.
	 *
	 * @return string
	 */
	public function get_error_code() {
		return $this->code;
	}
}
