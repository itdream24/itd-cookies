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

if ( ! function_exists( 'itd_cookies_allowed' ) || ! is_callable( 'itd_cookies_allowed' ) ) {
	$fail( 'Supported bootstrap did not expose its consent API.' );
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

// Product 0.2.0 checks run on real disposable WordPress 5.2 and latest.
$legal  = new ITD_Cookies_Legal();
$before = get_option( 'itd_cookies_settings' );
if ( 0 !== ITD_Cookies_Settings::get()['auto_footer'] || '' !== ITD_Cookies_Settings::get()['link_4_url'] ) {
	$fail( 'New product defaults changed existing installations.' );
}
$services = ITD_Cookies_Services::get( ITD_Cookies_Settings::get() );
if ( 2 !== count( ITD_Cookies_Services::in_category( $services, 'analytics' ) ) || ITD_Cookies_Services::in_category( $services, 'marketing' ) ) {
	$fail( 'Actual services do not match configured categories.' );
}
if ( false === strpos( do_shortcode( '[itd_cookies_policy]' ), esc_html( __( 'Yandex Metrika', 'itd-cookies' ) ) ) || false === strpos( do_shortcode( '[itd_cookies_legal_links]' ), 'data-itd-cookies-open' ) ) {
	$fail( 'Product shortcodes were not registered.' );
}
$policy_page_id = ITD_Cookies_Legal::ensure_page();
if ( is_wp_error( $policy_page_id ) || ITD_Cookies_Legal::ensure_page() !== $policy_page_id || '[itd_cookies_policy]' !== get_post( $policy_page_id )->post_content ) {
	$fail( 'Managed policy creation is not idempotent.' );
}
wp_update_post(
	array(
		'ID'           => $policy_page_id,
		'post_content' => 'Owner edited content',
	)
);
ITD_Cookies_Legal::ensure_page();
if ( 'Owner edited content' !== get_post( $policy_page_id )->post_content || get_option( 'itd_cookies_settings' ) !== $before ) {
	$fail( 'Policy action overwrote content or existing options.' );
}
$links = ITD_Cookies_Legal::links( ITD_Cookies_Settings::get() );
if ( ! hash_equals( (string) get_permalink( $policy_page_id ), (string) $links[0]['url'] ) ) {
	$fail( 'Managed policy fallback is missing.' );
}
wp_trash_post( $policy_page_id );
$replacement = ITD_Cookies_Legal::ensure_page();
if ( is_wp_error( $replacement ) || in_array( $replacement, array( $policy_page_id ), true ) ) {
	$fail( 'A deleted managed page could not be replaced.' );
}
wp_delete_post( $policy_page_id, true );
wp_delete_post( $replacement, true );
delete_option( ITD_Cookies_Legal::PAGE_OPTION );
ob_start();
$legal->render_footer();
if ( '' !== ob_get_clean() ) {
	$fail( 'Auto footer was unexpectedly enabled.' );
}
$footer_settings                = ITD_Cookies_Settings::get();
$footer_settings['auto_footer'] = 1;
$footer_settings['link_4_url']  = '/agreement/';
update_option( ITD_Cookies_Settings::OPTION_NAME, $footer_settings );
ob_start();
$legal->render_footer();
$footer = ob_get_clean();
if ( false === strpos( $footer, '/agreement/' ) || false === strpos( $footer, 'itd-cookies-footer' ) ) {
	$fail( 'Optional footer or fourth link did not render.' );
}
update_option( ITD_Cookies_Settings::OPTION_NAME, $before );

// Provider 0.3.0 checks preserve the stored 0.2.0 option and policy state.
$original = get_option( ITD_Cookies_Settings::OPTION_NAME );
$marker   = get_option( ITD_Cookies_Settings::MIGRATION_MARKER );
$old      = $original;
foreach ( array( 'gtm_enabled', 'gtm_container_id', 'clarity_enabled', 'clarity_project_id', 'meta_enabled', 'meta_pixel_id' ) as $key ) {
	unset( $old[ $key ] );
}
update_option( ITD_Cookies_Settings::OPTION_NAME, $old );
$page_id      = ITD_Cookies_Legal::ensure_page();
$page_content = get_post( $page_id )->post_content;
ITD_Cookies_Settings::migrate_legacy();
$new = ITD_Cookies_Settings::get();
if ( get_option( ITD_Cookies_Settings::OPTION_NAME ) !== $old || get_option( ITD_Cookies_Settings::MIGRATION_MARKER ) !== $marker || ITD_Cookies_Legal::page_id() !== $page_id || get_post( $page_id )->post_content !== $page_content || 1 !== ITD_Cookies_Consent::SCHEMA ) {
	$fail( '0.2.0 options, marker, managed policy or consent schema changed.' );
}
foreach ( array( 'gtm', 'clarity', 'meta' ) as $provider_type ) {
	if ( 0 !== $new[ $provider_type . '_enabled' ] ) {
		$fail( 'A new provider was enabled by default.' );
	}
}
$new['gtm_enabled']        = 1;
$new['gtm_container_id']   = 'GTM-TEST123';
$new['clarity_enabled']    = 1;
$new['clarity_project_id'] = 'test12345';
$new['meta_enabled']       = 1;
$new['meta_pixel_id']      = '123456789012345';
update_option( ITD_Cookies_Settings::OPTION_NAME, $new );
$registry = ITD_Cookies_Services::get( $new );
if ( 4 !== count( ITD_Cookies_Services::in_category( $registry, 'analytics' ) ) || 1 !== count( ITD_Cookies_Services::in_category( $registry, 'marketing' ) ) || 5 !== count( ITD_Cookies_Services::providers( $new ) ) ) {
	$fail( 'Native provider configuration did not match the registry.' );
}
$policy = do_shortcode( '[itd_cookies_policy]' );
foreach ( array( 'Google Tag Manager', 'Microsoft Clarity', 'Meta Pixel' ) as $name ) {
	if ( false === strpos( $policy, $name ) ) {
		$fail( 'Dynamic policy omitted ' . $name );
	}
}
( new ITD_Cookies_Plugin() )->enqueue_assets();
$scripts = wp_scripts();
if ( ! wp_script_is( 'itd-cookies-providers', 'enqueued' ) || ! in_array( 'itd-cookies-providers', $scripts->registered['itd-cookies-consent']->deps, true ) ) {
	$fail( 'Provider loader is not a consent controller dependency.' );
}
update_option( ITD_Cookies_Settings::OPTION_NAME, $original );
wp_delete_post( $page_id, true );
delete_option( ITD_Cookies_Legal::PAGE_OPTION );


// Exercise the real Settings API callback with WordPress URL escaping and storage.
require_once ABSPATH . 'wp-admin/includes/template.php';
( new ITD_Cookies_Settings() )->register_option();
$original               = get_option( ITD_Cookies_Settings::OPTION_NAME );
$previous               = ITD_Cookies_Settings::get();
$previous['link_1_url'] = 'https://example.org/previous';
update_option( ITD_Cookies_Settings::OPTION_NAME, $previous );
foreach ( array( 'http://external.example/policy', '//external.example/policy', 'not a URL', '/\\external.example' ) as $invalid ) {
	unset( $GLOBALS['wp_settings_errors'] );
	$submitted               = $previous;
	$submitted['link_1_url'] = $invalid;
	update_option( ITD_Cookies_Settings::OPTION_NAME, $submitted );
	$url_errors = get_settings_errors( ITD_Cookies_Settings::OPTION_NAME );
	if ( 'https://example.org/previous' !== get_option( ITD_Cookies_Settings::OPTION_NAME )['link_1_url'] || 1 !== count( $url_errors ) || 'error' !== $url_errors[0]['type'] || false === strpos( $url_errors[0]['message'], 'Прежняя ссылка сохранена' ) ) {
		$fail( 'A rejected legal URL lost the previous link or its translated error.' );
	}
}
foreach ( array(
	'https://example.org/new'     => 'https://example.org/new',
	'/policy?language=ru#cookies' => '/policy?language=ru#cookies',
	home_url( '/policy' )         => '/policy',
	''                            => '',
) as $valid => $expected ) {
	unset( $GLOBALS['wp_settings_errors'] );
	$submitted               = $previous;
	$submitted['link_1_url'] = $valid;
	update_option( ITD_Cookies_Settings::OPTION_NAME, $submitted );
	if ( 0 !== strcmp( $expected, get_option( ITD_Cookies_Settings::OPTION_NAME )['link_1_url'] ) || get_settings_errors( ITD_Cookies_Settings::OPTION_NAME ) ) {
		$fail( 'A valid legal URL did not save through the Settings API.' );
	}
}
unset( $GLOBALS['wp_settings_errors'] );
ITD_Cookies_Settings::get();
if ( get_settings_errors( ITD_Cookies_Settings::OPTION_NAME ) ) {
	$fail( 'Reading settings emitted save-time errors.' );
}
update_option( ITD_Cookies_Settings::OPTION_NAME, $original );

WP_CLI::success( $persisted ? 'ITD Cookies reactivation preserved migrated settings.' : 'ITD Cookies activation, migration, consent defaults, UI, and Russian translation passed.' );
