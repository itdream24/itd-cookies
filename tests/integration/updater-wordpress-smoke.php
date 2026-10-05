<?php
/**
 * Native upgrader integration on disposable CI WordPress only.
 *
 * Metadata is synthetic; the development package/version is not changed.
 * Run: PACKAGE_PATH=/absolute/package.zip wp eval-file this-file.php.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'http://wordpress.test' !== get_option( 'home' ) ) {
	exit( 'Disposable CI WordPress required.' );
}
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

$itd_cookies_package = getenv( 'PACKAGE_PATH' );
if ( ! is_string( $itd_cookies_package ) || ! is_file( $itd_cookies_package ) ) {
	WP_CLI::error( 'Package fixture missing.' );
}
$itd_cookies_mode     = 'valid';
$itd_cookies_basename = 'itd-cookies/itd-cookies.php';
$itd_cookies_options  = get_option( 'itd_cookies_settings' );
$itd_cookies_marker   = get_option( 'itd_cookies_migration_version' );
$itd_cookies_sha      = hash_file( 'sha256', ITD_COOKIES_FILE );

add_filter(
	'pre_http_request',
	static function ( $result, $args, $url ) use ( &$itd_cookies_mode, $itd_cookies_package ) {
		$package_url = 'https://github.com/itdream24/itd-cookies/releases/download/v0.1.0/itd-cookies-0.1.0.zip';
		if ( ITD_Cookies_Updater::API_URL === $url ) {
			if ( 'timeout' === $itd_cookies_mode ) {
				return new WP_Error( 'http_request_failed', 'Synthetic GitHub timeout.' );
			}
			$body = wp_json_encode(
				array(
					'tag_name'   => 'v0.1.0',
					'draft'      => false,
					'prerelease' => false,
					'body'       => 'Disposable CI fixture, no public release.',
					'assets'     => array(
						array(
							'name'                 => 'itd-cookies-0.1.0.zip',
							'browser_download_url' => $package_url,
							'state'                => 'uploaded',
							'size'                 => filesize( $itd_cookies_package ),
						),
					),
				)
			);
		} elseif ( $package_url === $url ) {
			if ( 'network' === $itd_cookies_mode ) {
				return new WP_Error( 'http_request_failed', 'Synthetic package network failure.' );
			}
			$body = 'invalid' === $itd_cookies_mode ? 'Not a ZIP' : file_get_contents( $itd_cookies_package );
			if ( ! empty( $args['stream'] ) && ! empty( $args['filename'] ) ) {
				file_put_contents( $args['filename'], $body );
			}
		} else {
			return $result;
		}
		return array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'headers'  => array(),
			'body'     => $body,
			'cookies'  => array(),
		);
	},
	10,
	3
);

foreach ( array( 'invalid', 'network', 'valid' ) as $itd_cookies_mode ) {
	delete_transient( ITD_Cookies_Updater::CACHE_KEY );
	$itd_cookies_updates = apply_filters( 'pre_set_site_transient_update_plugins', (object) array( 'checked' => array( $itd_cookies_basename => ITD_COOKIES_VERSION ) ) );
	if ( ! isset( $itd_cookies_updates->response[ $itd_cookies_basename ] ) ) {
		WP_CLI::error( 'Synthetic stable metadata did not reach native update record.' );
	}
	set_site_transient( 'update_plugins', $itd_cookies_updates );
	$itd_cookies_skin   = new Automatic_Upgrader_Skin();
	$itd_cookies_result = ( new Plugin_Upgrader( $itd_cookies_skin ) )->upgrade( $itd_cookies_basename );
	if ( 'valid' === $itd_cookies_mode ? true !== $itd_cookies_result : ! is_wp_error( $itd_cookies_result ) ) {
		WP_CLI::error( 'Unexpected native upgrade result: ' . $itd_cookies_mode . ': ' . var_export( $itd_cookies_result, true ) . '; skin=' . wp_json_encode( $itd_cookies_skin->get_errors() ) );
	}
	if (
		! is_file( ITD_COOKIES_FILE ) ||
		hash_file( 'sha256', ITD_COOKIES_FILE ) !== $itd_cookies_sha ||
		get_option( 'itd_cookies_settings' ) !== $itd_cookies_options ||
		get_option( 'itd_cookies_migration_version' ) !== $itd_cookies_marker
	) {
		WP_CLI::error( 'Installation or settings were damaged: ' . $itd_cookies_mode );
	}
	// WP-CLI has no subsequent browser request for reactivation; ensure activation.
	activate_plugin( $itd_cookies_basename );
	if ( ! is_plugin_active( $itd_cookies_basename ) ) {
		WP_CLI::error( 'Updated plugin could not activate.' );
	}
}
$itd_cookies_mode = 'timeout';
delete_transient( ITD_Cookies_Updater::CACHE_KEY );
if ( null !== ( new ITD_Cookies_Updater( ITD_COOKIES_FILE, ITD_COOKIES_VERSION ) )->release() ) {
	WP_CLI::error( 'GitHub timeout did not fail safely.' );
}
WP_CLI::success( 'Native upgrade, invalid ZIP, network failure, timeout and settings preservation passed; dev version unchanged.' );
