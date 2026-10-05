<?php
/**
 * Anonymous GitHub Releases updates through the native WordPress upgrader.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stable-only update adapter. WordPress owns downloading and installation.
 */
final class ITD_Cookies_Updater {
	const REPOSITORY = 'itdream24/itd-cookies';
	const API_URL    = 'https://api.github.com/repos/itdream24/itd-cookies/releases/latest';
	const CACHE_KEY  = 'itd_cookies_github_release';

	/**
	 * Canonical plugin basename.
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Installed version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Configure this installation.
	 *
	 * @param string $file    Main plugin file.
	 * @param string $version Installed version.
	 */
	public function __construct( $file, $version ) {
		$this->basename = plugin_basename( $file );
		$this->version  = $version;
	}

	/**
	 * Register hooks without making a network request.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_plugins' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 10, 3 );
		// A native manual "Check again" clears WordPress's update cache.
		add_action( 'delete_site_transient_update_plugins', array( $this, 'clear_cache' ) );
	}

	/**
	 * Forget release metadata when WordPress explicitly clears update data.
	 *
	 * @return void
	 */
	public function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Get validated stable metadata, including cached negative results.
	 *
	 * @return array|null
	 */
	public function release() {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) && isset( $cached['release'] ) ) {
			return is_array( $cached['release'] ) ? $cached['release'] : null;
		}
		// Cron and WP-CLI update checks are allowed; ordinary frontend requests are not.
		if ( ! is_admin() && ! wp_doing_cron() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return null;
		}
		$response = wp_remote_get(
			self::API_URL,
			array(
				'timeout'     => 10,
				'redirection' => 0,
				'headers'     => array(
					'Accept'               => 'application/vnd.github+json',
					'X-GitHub-Api-Version' => '2022-11-28',
					'User-Agent'           => 'ITD-Cookies/' . $this->version,
				),
			)
		);
		$release  = null;
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$release = $this->validate_release( json_decode( wp_remote_retrieve_body( $response ), true ) );
		}
		set_transient( self::CACHE_KEY, array( 'release' => $release ? $release : false ), $release ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );
		return $release;
	}

	/**
	 * Reject ambiguous tags, prereleases, foreign assets and source archives.
	 *
	 * @param mixed $data Decoded GitHub response.
	 * @return array|null
	 */
	public function validate_release( $data ) {
		if (
			! is_array( $data ) ||
			! isset( $data['draft'], $data['prerelease'], $data['tag_name'], $data['assets'] ) ||
			false !== $data['draft'] || false !== $data['prerelease'] ||
			! is_string( $data['tag_name'] ) || ! is_array( $data['assets'] ) ||
			! preg_match( '/^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/D', $data['tag_name'] )
		) {
			return null;
		}
		$version = substr( $data['tag_name'], 1 );
		$name    = 'itd-cookies-' . $version . '.zip';
		$url     = 'https://github.com/' . self::REPOSITORY . '/releases/download/' . $data['tag_name'] . '/' . $name;
		foreach ( $data['assets'] as $asset ) {
			if (
				is_array( $asset ) && isset( $asset['name'], $asset['browser_download_url'], $asset['state'], $asset['size'] ) &&
				$name === $asset['name'] && $url === $asset['browser_download_url'] &&
				'uploaded' === $asset['state'] && is_int( $asset['size'] ) && $asset['size'] > 0
			) {
				return array(
					'version'   => $version,
					'package'   => $url,
					'changelog' => isset( $data['body'] ) && is_string( $data['body'] ) ? $data['body'] : '',
				);
			}
		}
		return null;
	}

	/**
	 * Advertise only newer versions in WordPress's standard update record.
	 *
	 * @param mixed $transient WordPress update data.
	 * @return mixed
	 */
	public function update_plugins( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked[ $this->basename ] ) ) {
			return $transient;
		}
		$release = $this->release();
		if ( ! $release ) {
			return $transient;
		}
		$item = (object) array(
			'id'           => 'https://github.com/' . self::REPOSITORY,
			'slug'         => 'itd-cookies',
			'plugin'       => $this->basename,
			'new_version'  => $release['version'],
			'url'          => 'https://github.com/' . self::REPOSITORY,
			'package'      => $release['package'],
			'requires'     => '5.2',
			'requires_php' => '7.4',
		);
		if ( version_compare( $release['version'], $this->version, '>' ) ) {
			$transient->response[ $this->basename ] = $item;
			unset( $transient->no_update[ $this->basename ] );
		} else {
			$transient->no_update[ $this->basename ] = $item;
			unset( $transient->response[ $this->basename ] );
		}
		return $transient;
	}

	/**
	 * Supply the native plugin details modal; escape untrusted release text.
	 *
	 * @param mixed  $result Existing filter result.
	 * @param string $action API operation.
	 * @param object $args   Requested slug.
	 * @return mixed
	 */
	public function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! is_object( $args ) || ! isset( $args->slug ) || 'itd-cookies' !== $args->slug ) {
			return $result;
		}
		$release = $this->release();
		if ( ! $release ) {
			return $result;
		}
		return (object) array(
			'name'          => 'ITD Cookies',
			'slug'          => 'itd-cookies',
			'version'       => $release['version'],
			'author'        => 'ITD',
			'homepage'      => 'https://github.com/' . self::REPOSITORY,
			'requires'      => '5.2',
			'tested'        => '7.1',
			'requires_php'  => '7.4',
			'download_link' => $release['package'],
			'sections'      => array(
				'description' => esc_html__( 'Cookie choices and consent-aware Yandex Metrika and Google Analytics.', 'itd-cookies' ),
				'changelog'   => '<pre>' . esc_html( $release['changelog'] ) . '</pre>',
			),
		);
	}
}
