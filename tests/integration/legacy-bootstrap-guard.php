<?php
/**
 * Verify the unchanged official bootstrap refuses unsupported environments.
 * Never require runtime classes directly or bypass the version guards.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'Disposable CLI required.' );
}
$runtime = getenv( 'ITD_RUNTIME_FILE' );
if ( ! $runtime || ! is_file( $runtime ) || ( PHP_VERSION_ID >= 70400 && version_compare( $GLOBALS['wp_version'], '5.2', '>=' ) ) ) {
	WP_CLI::error( 'This guard probe requires an unsupported disposable environment.' );
}
$settings = get_option( 'itd_cookies_settings', false );
$marker   = get_option( 'itd_cookies_migration_version', false );
require $runtime;
ob_start();
do_action( 'admin_notices' );
$notice   = ob_get_clean();
$expected = PHP_VERSION_ID < 70400 ? 'PHP 7.4' : 'WordPress 5.2';
if ( class_exists( 'ITD_Cookies_Plugin', false ) || class_exists( 'ITD_Cookies_Settings', false ) ||
class_exists( 'ITD_Cookies_Updater', false ) || shortcode_exists( 'itd_cookies_settings' ) ||
false !== strpos( $notice, '<script' ) || false === strpos( $notice, $expected ) ||
get_option( 'itd_cookies_settings', false ) !== $settings || get_option( 'itd_cookies_migration_version', false ) !== $marker ) {
	WP_CLI::error( 'Bootstrap did not stop without activating runtime or mutating options.' );
}
// An unconditional global declaration is compiled even after the early return.
// Observe this legacy API limitation without allowing its Error to escape.
$api_status = 'absent';
if ( function_exists( 'itd_cookies_allowed' ) ) {
	try {
		itd_cookies_allowed( 'necessary' );
		$api_status = 'callable';
	} catch ( Error $error ) {
		$api_status = 'declared_but_unusable_after_guard';
	}
}
WP_CLI::log(
	wp_json_encode(
		array(
			'core'                        => $GLOBALS['wp_version'],
			'php'                         => PHP_VERSION,
			'guard'                       => 'PASS',
			'runtime_loaded'              => false,
			'notice'                      => $expected,
			'pre52_requirement_validator' => function_exists( 'validate_plugin_requirements' ),
			'consent_api'                 => $api_status,
		)
	)
);
WP_CLI::success( 'Unsupported runtime refused; this is not functional compatibility.' );
