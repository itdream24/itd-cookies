<?php
/**
 * Shipped adapter contract against the real core queue, on every WP CI matrix.
 *
 * @package ITD\Cookies\Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$scenario          = getenv( 'ADAPTER_SCENARIO' );
$scenario          = $scenario ? $scenario : 'supported';
$check             = static function ( $condition, $message ) {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
};
$original_settings = get_option( ITD_Cookies_Settings::OPTION_NAME );
$settings          = ITD_Cookies_Settings::defaults();
if ( 'ownership' === $scenario ) {
	$settings['ga4_enabled']        = 1;
	$settings['ga4_measurement_id'] = 'G-TEST12345';
}
update_option( ITD_Cookies_Settings::OPTION_NAME, $settings );
wp_enqueue_script( 'unknown-functional', '/unknown.js', array(), 'fixture', false );
wp_add_inline_script( 'unknown-functional', 'window.unknownFunctional = true;' );
$scripts        = wp_scripts();
$unknown_inline = $scripts->get_data( 'unknown-functional', 'after' );

if ( 'zero' === $scenario ) {
	do_action( 'wp_enqueue_scripts' );
	do_action( 'wp_print_scripts' );
	ob_start();
	ITD_Cookies_Script_Adapters::instance()->manifest();
	$check( '' === ob_get_clean() && ! wp_script_is( 'itd-cookies-script-adapters', 'enqueued' ), 'Empty registry introduced runtime output.' );
	$check( $unknown_inline === $scripts->get_data( 'unknown-functional', 'after' ), 'Unknown inline changed.' );
	update_option( ITD_Cookies_Settings::OPTION_NAME, $original_settings );
	WP_CLI::success( 'Zero groups: no assets, manifest or unknown-script mutation.' );
	return;
}

wp_register_script( 'fixture-library', '/library.js', array(), 'fixture', false );
wp_register_script( 'fixture-dependent', '/dependent.js', array( 'fixture-library' ), 'fixture', true );
wp_enqueue_script( 'fixture-dependent' );
wp_localize_script( 'fixture-library', 'FixtureData', array( 'value' => 'localization<&' ) );
wp_add_inline_script( 'fixture-library', "window.fixtureBefore = 'before';\n// untouched body", 'before' );
wp_add_inline_script( 'fixture-library', "window.fixtureAfter = 'after';", 'after' );
add_filter(
	'wp_inline_script_attributes',
	static function ( $attributes ) {
		$attributes['nonce'] = 'fixture-nonce';
		return $attributes;
	}
);
add_filter(
	'script_loader_tag',
	static function ( $tag ) {
		return str_replace( '<script ', '<script nonce="fixture-nonce" ', $tag );
	},
	10
);
ob_start();
$scripts->print_extra_script( 'fixture-library' );
$expected_data = ob_get_clean();
ob_start();
if ( method_exists( $scripts, 'get_inline_script_tag' ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Native renderer output, test capture only.
	echo $scripts->get_inline_script_tag( 'fixture-library', 'before' );
} else {
	$scripts->print_inline_script( 'fixture-library', 'before' );
}
$expected_before = apply_filters( 'script_loader_tag', ob_get_clean(), 'fixture-library' );
ob_start();
if ( method_exists( $scripts, 'get_inline_script_tag' ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Native renderer output, test capture only.
	echo $scripts->get_inline_script_tag( 'fixture-library', 'after' );
} else {
	$scripts->print_inline_script( 'fixture-library', 'after' );
}
$expected_after = apply_filters( 'script_loader_tag', ob_get_clean(), 'fixture-library' );

$args     = array(
	'category'       => 'analytics',
	'handles'        => array( 'fixture-library', 'fixture-dependent' ),
	'provider_types' => array( 'ownership' === $scenario ? 'ga4' : 'fixture-analytics' ),
	'resources'      => array(),
);
$expected = '';
switch ( $scenario ) {
	case 'missing-handle':
		$args['handles'][] = 'absent-handle';
		$expected          = 'MISSING_HANDLE';
		break;
	case 'missing-dependency':
		$scripts->registered['fixture-dependent']->deps[] = 'absent-dependency';
		$expected = 'MISSING_DEPENDENCY';
		break;
	case 'cycle':
		$scripts->registered['fixture-library']->deps[] = 'fixture-dependent';
		$expected                                       = 'CYCLE';
		break;
	case 'ownership':
		$expected = 'FAILED_OWNERSHIP_CONFLICT';
		break;
	case 'ancillary':
		$args['resources'] = array( array( 'type' => 'pixel' ) );
		$expected          = 'UNSUPPORTED_ANCILLARY_RESOURCES';
		break;
	case 'unprinted':
		wp_dequeue_script( 'fixture-dependent' );
		$expected = 'UNSUPPORTED_UNPRINTED_HANDLE';
		break;
}
add_action(
	'itd_cookies_register_script_groups',
	static function () use ( $args, $check, $scenario ) {
		$result = itd_cookies_register_script_group( 'fixture-analytics', $args );
		$check( 'ancillary' === $scenario ? is_wp_error( $result ) : true === $result, 'Public registration result mismatch.' );
		wp_enqueue_script( 'fixture-independent', '/independent.js', array(), 'fixture', true );
		$check(
			true === itd_cookies_register_script_group(
				'fixture-independent',
				array(
					'category'       => 'marketing',
					'handles'        => array( 'fixture-independent' ),
					'provider_types' => array( 'fixture-marketing' ),
					'resources'      => array(),
				)
			),
			'Independent group failed registration.'
		);
	}
);
if ( 'cycle' === $scenario ) {
	add_action(
		'wp_enqueue_scripts',
		static function () use ( $scripts, $check ) {
			$check( in_array( 'CYCLE', array_column( itd_cookies_get_script_group_diagnostics(), 'code' ), true ), 'Cycle was not quarantined before priority 1000.' );
			$check( array() === $scripts->registered['fixture-library']->deps, 'Native dependency query can still enter the owned cycle.' );
		},
		1000
	);
}
do_action( 'wp_enqueue_scripts' );
do_action( 'wp_print_scripts' );
ob_start();
$scripts->do_head_items();
$head = ob_get_clean();
ob_start();
do_action( 'wp_print_footer_scripts' );
$scripts->do_footer_items();
$footer = ob_get_clean();
if ( 'late' === $scenario ) {
	$result   = itd_cookies_register_script_group( 'fixture-late', $args );
	$expected = 'LATE_REGISTRATION';
	$check( is_wp_error( $result ) && $expected === $result->get_error_code(), 'Late API call was accepted.' );
}
ob_start();
do_action( 'wp_footer' );
$output = ob_get_clean();
$check( false !== strpos( $head, '/unknown.js' ) && false !== strpos( $head, 'window.unknownFunctional = true;' ), 'Unknown native script/inline was changed.' );
$check( false !== strpos( $footer, 'id="itd-cookies-script-fixture-independent"' ), 'Independent group was blocked.' );
$check( false !== strpos( $output, 'itd-cookies-script-groups' ), 'Graph manifest missing.' );
$events = itd_cookies_get_script_group_diagnostics();
if ( $expected ) {
	$check( in_array( $expected, array_column( $events, 'code' ), true ), 'Missing diagnostic ' . $expected );
	if ( ! in_array( $scenario, array( 'late', 'unprinted' ), true ) ) {
		$check( false === strpos( $head . $footer, 'FixtureData' ) && false === strpos( $head . $footer, '/library.js' ), 'Rejected group leaked native script/data.' );
	}
} else {
	$check( false !== strpos( $head, $expected_data ) && false !== strpos( $head, $expected_before ) && false !== strpos( $head, $expected_after ), 'Native inline tag bytes changed.' );
	$check( 1 === substr_count( $head . $footer, 'id="itd-cookies-script-fixture-library"' ) && 1 === substr_count( $head . $footer, 'id="itd-cookies-script-fixture-dependent"' ), 'Head/footer ownership duplicated or omitted.' );
	$check( '' === $scripts->get_data( 'fixture-library', 'data' ), 'Localization was not retained.' );
}
$check( false === strpos( wp_json_encode( $events ), '/library.js' ) && false === strpos( wp_json_encode( $events ), 'localization' ), 'Diagnostics contained source or URL.' );
update_option( ITD_Cookies_Settings::OPTION_NAME, $original_settings );
WP_CLI::success( 'Production adapter native WordPress scenario: ' . $scenario );
