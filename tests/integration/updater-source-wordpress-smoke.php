<?php
/**
 * Synthetic source collision regression using real disposable WordPress hooks.
 * No live directory collision or public GitHub release discovery is claimed.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'http://wordpress.test' !== get_option( 'home' ) ) {
	exit( 'Disposable CI WordPress required.' );
}
$itd_source_mode     = 'success';
$itd_source_requests = 0;
$itd_source_basename = 'itd-cookies/itd-cookies.php';
$itd_source_url      = 'https://github.com/itdream24/itd-cookies/releases/download/v9.0.0/itd-cookies-9.0.0.zip';
$itd_source_other    = (object) array(
	'new_version' => '2.0.0',
	'package'     => 'https://other.example/plugin.zip',
);
$itd_source_foreign  = (object) array(
	'new_version' => '9.9.9',
	'package'     => 'https://downloads.wordpress.org/plugin/itd-cookies.9.9.9.zip',
);
$itd_source_http     = static function ( $pre, $args, $url ) use ( &$itd_source_mode, &$itd_source_requests, $itd_source_url ) {
	if ( ITD_Cookies_Updater::API_URL !== $url ) {
		return $pre;
	}
	++$itd_source_requests;
	if ( 'timeout' === $itd_source_mode ) {
		return new WP_Error( 'http_request_failed', 'Controlled timeout.' );
	}
	$data = array(
		'tag_name'   => 'v9.0.0',
		'draft'      => false,
		'prerelease' => 'invalid' === $itd_source_mode,
		'assets'     => 'no-release' === $itd_source_mode ? array() : array(
			array(
				'name'                 => 'itd-cookies-9.0.0.zip',
				'browser_download_url' => $itd_source_url,
				'state'                => 'uploaded',
				'size'                 => 20000,
			),
		),
	);
	return array(
		'response' => array( 'code' => '500' === $itd_source_mode ? 500 : 200 ),
		'headers'  => array(),
		'body'     => 'malformed' === $itd_source_mode ? '{invalid' : wp_json_encode( $data ),
		'cookies'  => array(),
	);
};
add_filter( 'pre_http_request', $itd_source_http, 10, 3 );
foreach ( array( 'success', 'timeout', '500', 'malformed', 'invalid', 'no-release' ) as $itd_source_mode ) {
	delete_site_transient( 'update_plugins' );
	delete_transient( ITD_Cookies_Updater::CACHE_KEY );
	$itd_source_before = $itd_source_requests;
	$itd_source_record = (object) array(
		'last_checked' => time(),
		'checked'      => array(
			$itd_source_basename => ITD_COOKIES_VERSION,
			'other/plugin.php'   => '1.0.0',
		),
		'response'     => array(
			$itd_source_basename => $itd_source_foreign,
			'other/plugin.php'   => $itd_source_other,
		),
		'no_update'    => array(
			$itd_source_basename => $itd_source_foreign,
			'third/plugin.php'   => $itd_source_other,
		),
	);
	set_site_transient( 'update_plugins', $itd_source_record );
	$itd_source_read = get_site_transient( 'update_plugins' );
	if ( wp_json_encode( $itd_source_other ) !== wp_json_encode( $itd_source_read->response['other/plugin.php'] ) ||
		wp_json_encode( $itd_source_other ) !== wp_json_encode( $itd_source_read->no_update['third/plugin.php'] ) ||
		'1.0.0' !== $itd_source_read->checked['other/plugin.php'] ||
		$itd_source_before + 1 !== $itd_source_requests ) {
		WP_CLI::error( 'Other provider or HTTP count changed: ' . $itd_source_mode );
	}
	if ( 'success' === $itd_source_mode ) {
		if ( $itd_source_url !== $itd_source_read->response[ $itd_source_basename ]->package || isset( $itd_source_read->no_update[ $itd_source_basename ] ) ) {
			WP_CLI::error( 'Trusted GitHub result did not replace collision.' );
		}
	} elseif ( isset( $itd_source_read->response[ $itd_source_basename ] ) || isset( $itd_source_read->no_update[ $itd_source_basename ] ) ) {
		WP_CLI::error( 'Foreign package survived failure: ' . $itd_source_mode );
	}
	// Cached reads and ordinary writes reuse metadata; failed details never fall back.
	set_site_transient( 'update_plugins', $itd_source_record );
	get_site_transient( 'update_plugins' );
	$itd_source_details = apply_filters( 'plugins_api', false, 'plugin_information', (object) array( 'slug' => 'itd-cookies' ) );
	if ( $itd_source_before + 1 !== $itd_source_requests || ( 'success' !== $itd_source_mode && ! is_wp_error( $itd_source_details ) ) ) {
		WP_CLI::error( 'Cached/details fallback regression.' );
	}
	WP_CLI::log( $itd_source_mode . ': collision rejected/replaced, other providers preserved, one controlled GitHub call.' );
}
// Simulate a persisted foreign record that bypassed the save filter: read guard only.
$itd_source_before = $itd_source_requests;
update_option( '_site_transient_update_plugins', $itd_source_record );
$itd_source_read = get_site_transient( 'update_plugins' );
if ( isset( $itd_source_read->response[ $itd_source_basename ] ) || isset( $itd_source_read->no_update[ $itd_source_basename ] ) ||
	wp_json_encode( $itd_source_other ) !== wp_json_encode( $itd_source_read->response['other/plugin.php'] ) || $itd_source_before !== $itd_source_requests ) {
	WP_CLI::error( 'Persisted foreign cache was exposed or read made HTTP.' );
}
remove_filter( 'pre_http_request', $itd_source_http, 10 );
delete_site_transient( 'update_plugins' );
delete_transient( ITD_Cookies_Updater::CACHE_KEY );
WP_CLI::success( 'Synthetic collision regression PASS; not a real WordPress.org collision.' );
