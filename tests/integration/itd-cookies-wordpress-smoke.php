<?php
/**
 * Verify ITD Cookies on a disposable WordPress installation.
 *
 * Run with wp eval-file after activating itd-cookies and seeding the legacy
 * itd_modubricks_settings fixture in the CI workflow.
 *
 * @package ITD\Cookies\Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fail = static function ( $message ) {
	WP_CLI::error( $message );
};

if ( ! is_plugin_active( 'itd-cookies/itd-cookies.php' ) ) {
	$fail( 'ITD Cookies was not activated.' );
}

$settings = get_option( 'itd_cookies_settings', false );
if ( ! is_array( $settings ) ) {
	$fail( 'ITD Cookies settings were not saved.' );
}

$persisted      = '1' === getenv( 'CHECK_PERSISTED' );
$expected_title = $persisted ? 'New consent title' : 'Legacy consent title';

if (
	'copied-v1' !== get_option( 'itd_cookies_migration_version' ) ||
	$expected_title !== $settings['banner_title'] ||
	'12345678' !== $settings['metrika_id'] ||
	1 !== $settings['metrika_enabled'] ||
	'G-TEST12345' !== $settings['ga4_measurement_id'] ||
	1 !== $settings['ga4_enabled'] ||
	'legacy-policy-1' !== $settings['consent_version'] ||
	'standard' !== $settings['banner_text_size'] ||
	isset( $settings['analytics_mode'] ) ||
	isset( $settings['update_channel'] )
) {
	$fail( 'Legacy options were not imported with the expected allowlist.' );
}

if ( ! is_array( get_option( 'itd_modubricks_settings' ) ) ) {
	$fail( 'Legacy settings were removed.' );
}

if ( ! $persisted ) {
	$settings['banner_title'] = 'New consent title';
	update_option( 'itd_cookies_settings', $settings );
	ITD_Cookies_Settings::migrate_legacy();
	if ( 'New consent title' !== get_option( 'itd_cookies_settings' )['banner_title'] ) {
		$fail( 'Repeated migration overwrote new settings.' );
	}
}

if ( itd_cookies_allowed( 'analytics' ) || ! itd_cookies_allowed( 'necessary' ) ) {
	$fail( 'Default consent state is not fail closed.' );
}

$valid_consent                  = array(
	'schema'     => 1,
	'version'    => 'legacy-policy-1',
	'expiresAt'  => ( time() + 3600 ) * 1000,
	'categories' => array(
		'necessary'  => true,
		'functional' => false,
		'analytics'  => true,
		'marketing'  => false,
	),
);
$_COOKIE['itd_cookies_consent'] = rawurlencode( wp_json_encode( $valid_consent ) );
if ( ! itd_cookies_allowed( 'analytics' ) ) {
	$fail( 'Valid PHP consent signal was rejected.' );
}
foreach ( array( '{"schema":1,', wp_json_encode( array_merge( $valid_consent, array( 'schema' => '1' ) ) ), wp_json_encode( array_merge( $valid_consent, array( 'unknown' => true ) ) ) ) as $raw ) {
	$_COOKIE['itd_cookies_consent'] = rawurlencode( $raw );
	if ( itd_cookies_allowed( 'analytics' ) || ! itd_cookies_allowed( 'necessary' ) ) {
		$fail( 'Malformed PHP consent signal did not fail closed.' );
	}
}
unset( $_COOKIE['itd_cookies_consent'] );

$opener = do_shortcode( '[itd_cookies_settings]' );
if ( false === strpos( $opener, 'data-itd-cookies-open' ) ) {
	$fail( 'Reopen shortcode was not registered.' );
}

ob_start();
( new ITD_Cookies_Plugin() )->render_banner();
$banner = ob_get_clean();
foreach ( array( 'data-itd-cookies-accept', 'data-itd-cookies-reject', 'data-itd-cookies-customize', 'data-itd-cookies-category="analytics"' ) as $control ) {
	if ( false === strpos( $banner, $control ) ) {
		$fail( 'Consent UI is missing ' . $control );
	}
}

if ( 'Принять все' !== __( 'Accept all', 'itd-cookies' ) ) {
	$request_locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	$fail(
		'Packaged Russian translation did not load: locale=' . $request_locale .
		', catalog=' . ( is_file( ITD_COOKIES_DIR . 'languages/itd-cookies-ru_RU.mo' ) ? 'present' : 'missing' ) .
		', domain=' . ( is_textdomain_loaded( 'itd-cookies' ) ? 'loaded' : 'unloaded' ) . '.'
	);
}

WP_CLI::success( $persisted ? 'ITD Cookies reactivation preserved migrated settings.' : 'ITD Cookies activation, migration, consent defaults, UI, and Russian translation passed.' );
