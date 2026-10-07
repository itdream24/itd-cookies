<?php
/**
 * Request-local adapter preparation/capture benchmark, never whole-page HTML.
 *
 * @package ITD\Cookies\Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$original_scripts = wp_scripts();
$property         = new ReflectionProperty( 'ITD_Cookies_Script_Adapters', 'instance' );
$property->setAccessible( true );
$samples = array();
foreach ( array( 0, 3 ) as $group_count ) {
	$times = array();
	for ( $iteration = 0; $iteration < 30; ++$iteration ) {
		// Independent simulated document registries. No production reset API.
		$property->setValue( null, null );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Isolated benchmark request queue.
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		$registry              = ITD_Cookies_Script_Adapters::instance();
		$started               = microtime( true );
		for ( $index = 0; $index < $group_count; ++$index ) {
			$library   = 'bench-library-' . $index;
			$dependent = 'bench-dependent-' . $index;
			wp_register_script( $library, '/library.js', array(), 'bench', false );
			wp_enqueue_script( $dependent, '/dependent.js', array( $library ), 'bench', true );
			wp_localize_script( $library, 'BenchData' . $index, array( 'value' => 'local fixture' ) );
			wp_add_inline_script( $library, 'window.benchBefore = 1;', 'before' );
			wp_add_inline_script( $dependent, 'window.benchAfter = 1;' );
			$result = itd_cookies_register_script_group(
				'bench-group-' . $index,
				array(
					'category'       => 'analytics',
					'handles'        => array( $library, $dependent ),
					'provider_types' => array( 'bench-provider-' . $index ),
					'resources'      => array(),
				)
			);
			if ( true !== $result ) {
				WP_CLI::error( 'Benchmark registration failed.' );
			}
		}
		$registered = microtime( true );
		$registry->prepare();
		$prepared = microtime( true );
		ob_start();
		$scripts = wp_scripts();
		foreach ( ( $group_count ? $scripts->registered : array() ) as $handle => $item ) {
			if ( 0 === strpos( $handle, 'bench-' ) ) {
				// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Simulated native tag for capture timing, not site output.
				$registry->capture_tag( $registry->restore_tag( '<script src="/fixture.js"></script>', $handle ), $handle );
			}
		}
		$registry->manifest();
		ob_end_clean();
		$times[] = array( ( $registered - $started ) * 1000, ( $prepared - $registered ) * 1000, ( microtime( true ) - $prepared ) * 1000 );
	}
	$median = array();
	foreach ( array( 0, 1, 2 ) as $phase ) {
		$column = array_column( $times, $phase );
		sort( $column );
		$median[] = round( $column[15], 4 );
	}
	$samples[] = array(
		'groups'              => $group_count,
		'handles'             => $group_count * 2,
		'registration_ms'     => $median[0],
		'preparation_ms'      => $median[1],
		'capture_manifest_ms' => $median[2],
	);
}
// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the benchmark's original request queue.
$GLOBALS['wp_scripts'] = $original_scripts;
WP_CLI::line(
	wp_json_encode(
		array(
			'php'        => PHP_VERSION,
			'wordpress'  => get_bloginfo( 'version' ),
			'iterations' => 30,
			'median'     => $samples,
		)
	)
);
