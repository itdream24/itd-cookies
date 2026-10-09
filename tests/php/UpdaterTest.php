<?php
/**
 * GitHub adapter contract tests without network access.
 *
 * @package ITD_Cookies
 */

namespace ITD\Cookies\Tests;

use Brain\Monkey\Functions;

require_once dirname( __DIR__, 2 ) . '/includes/class-itd-cookies-updater.php';

/**
 * Exercise release validation, native update records and negative caching.
 */
final class UpdaterTest extends TestCase {
	/**
	 * Cached fixture.
	 *
	 * @var mixed
	 */
	private $cache = false;
	/**
	 * HTTP fixture.
	 *
	 * @var mixed
	 */
	private $http;
	/**
	 * Requests made.
	 *
	 * @var int
	 */
	private $requests = 0;
	/**
	 * Last cache duration.
	 *
	 * @var int
	 */
	private $ttl = 0;
	/**
	 * Whether the request is admin.
	 *
	 * @var bool
	 */
	private $admin = true;

	/**
	 * Plugin update permission.
	 *
	 * @var bool
	 */
	private $allowed = true;
	/**
	 * AJAX request.
	 *
	 * @var bool
	 */
	private $ajax = false;
	/**
	 * Active hook.
	 *
	 * @var string
	 */
	private $hook = 'load-update-core.php';
	/**
	 * WordPress cache invalidations.
	 *
	 * @var int
	 */
	private $deletions = 0;


	/**
	 * Configure a small HTTP/transient boundary.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->http = array(
			'code' => 200,
			'body' => json_encode( $this->fixture() ),
		);
		Functions\when( 'plugin_basename' )->justReturn( 'itd-cookies/itd-cookies.php' );
		Functions\when( 'is_admin' )->alias(
			function () {
				return $this->admin;
			}
		);
		Functions\when( 'wp_doing_cron' )->justReturn( false );
		Functions\when( 'wp_doing_ajax' )->alias(
			function () {
				return $this->ajax;
			}
		);
		Functions\when( 'current_filter' )->alias(
			function () {
				return $this->hook;
			}
		);
		Functions\when( 'current_user_can' )->alias(
			function ( $cap ) {
				self::assertSame( 'update_plugins', $cap );
				return $this->allowed;
			}
		);
		Functions\when( 'delete_site_transient' )->alias(
			function ( $key ) {
				self::assertSame( 'update_plugins', $key );
				++$this->deletions;
				return true;
			}
		);

		Functions\when( 'get_transient' )->alias(
			function () {
				return $this->cache;
			}
		);
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value, $ttl ) {
				self::assertSame( \ITD_Cookies_Updater::CACHE_KEY, $key );
				$this->cache = $value;
				$this->ttl   = $ttl;
				return true;
			}
		);
		Functions\when( 'delete_transient' )->alias(
			function () {
				$this->cache = false;
				return true;
			}
		);
		Functions\when( 'wp_remote_get' )->alias(
			function ( $url, $args ) {
				self::assertSame( \ITD_Cookies_Updater::API_URL, $url );
				self::assertSame( 10, $args['timeout'] );
				self::assertArrayNotHasKey( 'Authorization', $args['headers'] );
				++$this->requests;
				return $this->http;
			}
		);
		Functions\when( 'is_wp_error' )->alias(
			static function ( $value ) {
				return is_object( $value );
			}
		);
		Functions\when( 'wp_remote_retrieve_response_code' )->alias(
			static function ( $value ) {
				return $value['code'];
			}
		);
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static function ( $value ) {
				return $value['body'];
			}
		);
		Functions\when( 'esc_html' )->alias(
			static function ( $value ) {
				return htmlspecialchars( $value, ENT_QUOTES );
			}
		);
		Functions\when( 'esc_html__' )->returnArg( 1 );
	}

	/**
	 * Create metadata for one stable asset.
	 *
	 * @param string $version Version.
	 * @return array
	 */
	private function fixture( $version = '1.1.0' ) {
		return array(
			'tag_name'   => 'v' . $version,
			'draft'      => false,
			'prerelease' => false,
			'body'       => '<script>alert(1)</script>',
			'assets'     => array(
				array(
					'name'                 => 'itd-cookies-' . $version . '.zip',
					'browser_download_url' => 'https://github.com/itdream24/itd-cookies/releases/download/v' . $version . '/itd-cookies-' . $version . '.zip',
					'state'                => 'uploaded',
					'size'                 => 20000,
				),
			),
		);
	}

	/**
	 * Compare newer, equal and older versions without affecting other plugins.
	 *
	 * @return void
	 */
	public function test_version_comparison_and_native_record() {
		foreach ( array(
			'1.0.0' => true,
			'1.1.0' => false,
			'1.2.0' => false,
		) as $installed => $available ) {
			$updater   = new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', $installed );
			$transient = (object) array(
				'checked'  => array( 'itd-cookies/itd-cookies.php' => $installed ),
				'response' => array( 'other/plugin.php' => 'unrelated' ),
			);
			$result    = $updater->update_plugins( $transient );
			self::assertSame( $available, isset( $result->response['itd-cookies/itd-cookies.php'] ) );
			self::assertSame( 'unrelated', $result->response['other/plugin.php'] );
			$item = $available ? $result->response['itd-cookies/itd-cookies.php'] : $result->no_update['itd-cookies/itd-cookies.php'];
			self::assertSame( '1.1.0', $item->new_version );
			self::assertSame( 'itd-cookies/itd-cookies.php', $item->plugin );
			self::assertSame( '5.2', $item->requires );
		}
		self::assertSame( 1, $this->requests );
		self::assertSame( 21600, $this->ttl );
	}

	/**
	 * Reject draft, prerelease, invalid tags, missing/foreign/empty assets.
	 *
	 * @return void
	 */
	public function test_stable_filter_and_asset_validation() {
		$updater = new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '1.0.0' );
		foreach ( array( 'latest', 'release', 'foo', 'v1', '1.2', '1.2.3', 'v01.2.3', 'v1.2.3-beta.1', 'v1.2.3-alpha.1', 'v1.2.3-rc.1', "v1.2.3\n" ) as $tag ) {
			self::assertNull( $updater->validate_release( array_merge( $this->fixture(), array( 'tag_name' => $tag ) ) ) );
		}
		foreach ( array( 'draft', 'prerelease' ) as $flag ) {
			self::assertNull( $updater->validate_release( array_merge( $this->fixture(), array( $flag => true ) ) ) );
		}
		self::assertNull( $updater->validate_release( null ) );
		self::assertNull( $updater->validate_release( array_merge( $this->fixture(), array( 'assets' => array() ) ) ) );
		foreach ( array(
			'name'                 => 'source.zip',
			'browser_download_url' => 'https://evil.example/asset.zip',
			'state'                => 'new',
			'size'                 => 0,
		) as $key => $value ) {
			$fixture                      = $this->fixture();
			$fixture['assets'][0][ $key ] = $value;
			self::assertNull( $updater->validate_release( $fixture ) );
		}
	}

	/**
	 * Cache network errors, HTTP failures and malformed JSON safely.
	 *
	 * @return void
	 */
	public function test_failures_are_negative_cached() {
		$updater = new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '1.0.0' );
		foreach ( array(
			(object) array( 'error' => 'timeout' ),
			array(
				'code' => 403,
				'body' => '{}',
			),
			array(
				'code' => 404,
				'body' => '{}',
			),
			array(
				'code' => 500,
				'body' => '{}',
			),
			array(
				'code' => 200,
				'body' => '{invalid',
			),
		) as $response ) {
			$this->cache = false;
			$this->http  = $response;
			$before      = $this->requests;
			self::assertNull( $updater->release() );
			self::assertNull( $updater->release() );
			self::assertSame( $before + 1, $this->requests );
			self::assertSame( 900, $this->ttl );
		}
	}

	/**
	 * Avoid frontend/unrelated requests; native manual refresh invalidates cache.
	 *
	 * @return void
	 */
	public function test_no_frontend_request_and_manual_refresh() {
		$updater     = new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '1.0.0' );
		$this->admin = false;
		self::assertNull( $updater->release() );
		self::assertSame( 0, $this->requests );
		$this->admin = true;
		self::assertFalse( $updater->update_plugins( false ) );
		$unrelated = (object) array( 'checked' => array( 'other/plugin.php' => '1.0.0' ) );
		self::assertSame( $unrelated, $updater->update_plugins( $unrelated ) );
		self::assertSame( 0, $this->requests );
		self::assertNotNull( $updater->release() );
		$updater->clear_cache();
		self::assertNotNull( $updater->release() );
		self::assertSame( 2, $this->requests );
	}

	/**
	 * Keep other API requests and escape untrusted changelog text.
	 *
	 * @return void
	 */
	public function test_details_modal() {
		$updater = new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '1.0.0' );
		self::assertSame( 'existing', $updater->plugin_information( 'existing', 'plugin_information', (object) array( 'slug' => 'other' ) ) );
		self::assertSame( 0, $this->requests );
		$info = $updater->plugin_information( false, 'plugin_information', (object) array( 'slug' => 'itd-cookies' ) );
		self::assertSame( 'ITD Cookies', $info->name );
		self::assertSame( '1.1.0', $info->version );
		self::assertSame( '7.4', $info->requires_php );
		self::assertStringNotContainsString( '<script>', $info->sections['changelog'] );
	}
	/**
	 * Cached old stable metadata is refreshed once before native filtering.
	 *
	 * @return void
	 */
	public function test_cached_old_stable_manual_refresh() {
		$updater             = new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '0.4.0' );
		$old                 = $updater->validate_release( $this->fixture( '0.4.0' ) );
		$this->cache         = array( 'release' => $old );
		$this->http['body']  = json_encode( $this->fixture( '0.4.1' ) );
		$_GET['force-check'] = '1';
		$updater->register();
		self::assertSame( 1, has_action( 'load-update-core.php', array( $updater, 'manual_refresh' ) ) );
		$updater->manual_refresh();
		self::assertSame( 0, $this->requests );
		self::assertFalse( $this->cache );
		self::assertSame( 1, $this->deletions );
		$record = (object) array(
			'checked'   => array( 'itd-cookies/itd-cookies.php' => '0.4.0' ),
			'no_update' => array( 'itd-cookies/itd-cookies.php' => (object) array( 'new_version' => '0.4.0' ) ),
		);
		$result = $updater->update_plugins( $record );
		self::assertSame( '0.4.1', $result->response['itd-cookies/itd-cookies.php']->new_version );
		self::assertArrayNotHasKey( 'itd-cookies/itd-cookies.php', $result->no_update );
		$updater->manual_refresh();
		$updater->update_plugins( $record );
		self::assertSame( 1, $this->deletions );
		self::assertSame( 1, $this->requests );
		self::assertSame( 21600, $this->ttl );
		unset( $_GET['force-check'] );
	}

	/**
	 * Reject unrelated requests without changing either cache or its TTL.
	 *
	 * @return void
	 */
	public function test_manual_refresh_guards() {
		$this->cache = array( 'release' => array( 'version' => '0.4.0' ) );
		foreach ( array( null, '', '0', 'true', array( '1' ) ) as $flag ) {
			$_GET['force-check'] = $flag;
			( new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '0.4.0' ) )->manual_refresh();
		}
		$_GET['force-check'] = '1';
		$this->allowed       = false;
		( new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '0.4.0' ) )->manual_refresh();
		$this->allowed = true;
		$this->admin   = false;
		( new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '0.4.0' ) )->manual_refresh();
		$this->admin = true;
		$this->ajax  = true;
		( new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '0.4.0' ) )->manual_refresh();
		$this->ajax = false;
		$this->hook = 'load-plugins.php';
		( new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '0.4.0' ) )->manual_refresh();
		self::assertSame( 0, $this->deletions );
		self::assertSame( 0, $this->requests );
		self::assertSame( '0.4.0', $this->cache['release']['version'] );
		unset( $_GET['force-check'] );
	}

	/**
	 * REST requests never invalidate update caches.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @return void
	 */
	public function test_rest_manual_refresh_is_ignored() {
		define( 'REST_REQUEST', true );
		$_GET['force-check'] = '1';
		( new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '0.4.0' ) )->manual_refresh();
		self::assertSame( 0, $this->deletions );
		unset( $_GET['force-check'] );
	}

	/**
	 * Manual retries within a request keep API failures negatively cached.
	 *
	 * @return void
	 */
	public function test_manual_refresh_failure_is_cached_once() {
		$_GET['force-check'] = '1';
		$this->http          = (object) array( 'error' => 'timeout' );
		$updater             = new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '0.4.0' );
		$updater->manual_refresh();
		self::assertNull( $updater->release() );
		$updater->manual_refresh();
		self::assertNull( $updater->release() );
		self::assertSame( 1, $this->requests );
		self::assertSame( 1, $this->deletions );
		self::assertSame( 900, $this->ttl );
		unset( $_GET['force-check'] );
	}
	/**
	 * Core admin_init can fetch before the screen hook; do not fetch again.
	 *
	 * @return void
	 */
	public function test_prior_request_result_is_reused_for_manual_check() {
		foreach ( array( 200, 500 ) as $code ) {
			$this->cache         = false;
			$this->http          = array(
				'code' => $code,
				'body' => json_encode( $this->fixture() ),
			);
			$updater             = new \ITD_Cookies_Updater( '/plugin/itd-cookies.php', '0.4.0' );
			$before              = $this->requests;
			$first               = $updater->release();
			$_GET['force-check'] = '1';
			$updater->manual_refresh();
			self::assertSame( $first, $updater->release() );
			self::assertSame( $before + 1, $this->requests );
			self::assertSame( 200 === $code ? 21600 : 900, $this->ttl );
			unset( $_GET['force-check'] );
		}
	}
}
