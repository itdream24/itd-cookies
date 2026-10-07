<?php
/**
 * Public developer API for bounded WordPress scripts.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a bounded, closed classic WordPress script group before printing.
 *
 * @param string $group_id Group identifier.
 * @param array  $args Explicit ownership declaration.
 * @return true|WP_Error
 */
function itd_cookies_register_script_group( $group_id, array $args ) {
	return ITD_Cookies_Script_Adapters::instance()->add( $group_id, $args );
}

/**
 * Get request-local safe developer diagnostics.
 *
 * @return array
 */
function itd_cookies_get_script_group_diagnostics() {
	return ITD_Cookies_Script_Adapters::instance()->diagnostics();
}
