<?php
/**
 * Native manual-refresh regression with controlled HTTP on disposable CI only.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'http://wordpress.test' !== get_option( 'home' ) ) {
	exit( 'Disposable CI WordPress required.' );
}

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Disposable regression temporarily isolates and restores Core screen/hooks.
// phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores -- Exercise the existing native WordPress load-update-core.php hook.
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
$itd_refresh_hooks = array();
foreach ( array( 'load-update-core.php', 'pre_set_site_transient_update_plugins', 'delete_site_transient_update_plugins' ) as $itd_refresh_hook ) {
	$itd_refresh_hooks[ $itd_refresh_hook ] = clone $GLOBALS['wp_filter'][ $itd_refresh_hook ];
}
remove_all_actions( 'load-update-core.php' );
remove_all_filters( 'pre_set_site_transient_update_plugins' );
remove_all_actions( 'delete_site_transient_update_plugins' );
add_action( 'load-update-core.php', 'wp_update_plugins' );
$itd_refresh_user   = get_current_user_id();
$itd_refresh_screen = $GLOBALS['current_screen'] ?? null;
wp_set_current_user(
	get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
		)
	)[0]->ID
);
$GLOBALS['current_screen']                               = WP_Screen::get( 'update-core' );
$itd_refresh_basename                                    = 'itd-cookies/itd-cookies.php';
$itd_refresh_plugins                                     = get_plugins();
$itd_refresh_fixture                                     = $itd_refresh_plugins;
$itd_refresh_fixture[ $itd_refresh_basename ]['Version'] = '0.4.0';
wp_cache_set( 'plugins', array( '' => $itd_refresh_fixture ), 'plugins' );
$itd_refresh_updater = new ITD_Cookies_Updater( ITD_COOKIES_FILE, '0.4.0' );
$itd_refresh_updater->register();
$itd_refresh_requests = 0;
$itd_refresh_trace    = array();
$itd_refresh_http     = static function ( $pre, $args, $url ) use ( &$itd_refresh_requests, &$itd_refresh_trace ) {
	if ( ITD_Cookies_Updater::API_URL === $url ) {
		++$itd_refresh_requests;
		$itd_refresh_trace[] = 'github';
		$body                = array(
			'tag_name'   => 'v0.4.1',
			'draft'      => false,
			'prerelease' => false,
			'assets'     => array(
				array(
					'name'                 => 'itd-cookies-0.4.1.zip',
					'browser_download_url' => 'https://github.com/itdream24/itd-cookies/releases/download/v0.4.1/itd-cookies-0.4.1.zip',
					'state'                => 'uploaded',
					'size'                 => 20000,
				),
			),
		);
	} elseif ( false !== strpos( $url, 'api.wordpress.org/plugins/update-check/' ) ) {
		$itd_refresh_trace[] = 'WordPress';
		$body                = array(
			'plugins'      => array(),
			'no_update'    => array(),
			'translations' => array(),
		);
	} else {
		return $pre;
	}
	return array(
		'response' => array( 'code' => 200 ),
		'headers'  => array(),
		'body'     => wp_json_encode( $body ),
		'cookies'  => array(),
	);
};
add_filter( 'pre_http_request', $itd_refresh_http, 10, 3 );
set_transient(
	ITD_Cookies_Updater::CACHE_KEY,
	array(
		'release' => array(
			'version' => '0.4.0',
			'package' => 'old',
		),
	),
	6 * HOUR_IN_SECONDS
);
set_site_transient(
	'update_plugins',
	(object) array(
		'last_checked' => time(),
		'checked'      => array( $itd_refresh_basename => '0.4.0' ),
		'no_update'    => array( $itd_refresh_basename => (object) array( 'new_version' => '0.4.0' ) ),
	)
);
$itd_refresh_before = static function () use ( &$itd_refresh_trace ) {
	if ( false !== get_transient( ITD_Cookies_Updater::CACHE_KEY ) || false !== get_site_transient( 'update_plugins' ) ) {
		WP_CLI::error( 'Both caches must be absent before native priority 10.' );
	}
	$itd_refresh_trace[] = 'cleared-before-core';
};
// The ordinary admin view must retain its cache and make no GitHub request.
do_action( 'load-update-core.php' );
if ( 0 !== $itd_refresh_requests || '0.4.0' !== get_transient( ITD_Cookies_Updater::CACHE_KEY )['release']['version'] ) {
	WP_CLI::error( 'Ordinary admin view invalidated the metadata cache.' );
}
// Core may contact WordPress.org on an ordinary view; trace only the manual request below.
$itd_refresh_trace = array();
add_action( 'load-update-core.php', $itd_refresh_before, 9 );
$_GET['force-check'] = '1';
do_action( 'load-update-core.php' );
remove_action( 'load-update-core.php', $itd_refresh_before, 9 );
$itd_refresh_record = get_site_transient( 'update_plugins' );
if ( 1 !== $itd_refresh_requests || '0.4.1' !== $itd_refresh_record->response[ $itd_refresh_basename ]->new_version || isset( $itd_refresh_record->no_update[ $itd_refresh_basename ] ) || '0.4.1' !== get_transient( ITD_Cookies_Updater::CACHE_KEY )['release']['version'] ) {
	WP_CLI::error( 'Stale cached 0.4.0 did not offer 0.4.1 after native manual refresh.' );
}
if ( array( 'cleared-before-core', 'WordPress', 'github' ) !== $itd_refresh_trace ) {
	WP_CLI::error( 'Manual hook ordering failed: ' . wp_json_encode( $itd_refresh_trace ) );
}
// Old Core may repeat its own HTTP check; our GitHub call remains deduplicated.
do_action( 'load-update-core.php' );
$itd_refresh_updater->release();
if ( 1 !== $itd_refresh_requests ) {
	WP_CLI::error( 'Per-request GitHub HTTP deduplication failed.' );
}
remove_filter( 'pre_http_request', $itd_refresh_http, 10 );
foreach ( $itd_refresh_hooks as $itd_refresh_hook => $itd_refresh_saved ) {
	$GLOBALS['wp_filter'][ $itd_refresh_hook ] = $itd_refresh_saved;
}
wp_cache_set( 'plugins', array( '' => $itd_refresh_plugins ), 'plugins' );
$GLOBALS['current_screen'] = $itd_refresh_screen;
wp_set_current_user( $itd_refresh_user );
unset( $_GET['force-check'] );
delete_transient( ITD_Cookies_Updater::CACHE_KEY );
delete_site_transient( 'update_plugins' );
WP_CLI::success( 'Controlled HTTP/cache regression PASS: old 0.4.0 offers 0.4.1, caches clear before Core, exactly one GitHub request; this is not public-release E2E.' );
