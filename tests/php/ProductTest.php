<?php
/**
 * Product integration behavior with isolated WordPress doubles.
 *
 * @package ITD\Cookies\Tests
 */

namespace ITD\Cookies\Tests;

use Brain\Monkey\Functions;

require_once dirname( __DIR__, 2 ) . '/admin/class-itd-cookies-settings.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-itd-cookies-services.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-itd-cookies-legal.php';
require_once dirname( __DIR__, 2 ) . '/public/class-itd-cookies-plugin.php';

/** Tests settings compatibility, registry and dynamic legal integration. */
final class ProductTest extends TestCase {
	/**
	 * Stored options.
	 *
	 * @var array
	 */
	private $options = array();
	/**
	 * Managed pages.
	 *
	 * @var array
	 */
	private $pages = array();

	/**
	 * Set up only the WordPress boundaries used by product rendering.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( 'strip_tags' );
		Functions\when( 'sanitize_textarea_field' )->alias( 'strip_tags' );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->alias( 'htmlspecialchars' );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'absint' )->alias( 'intval' );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'home_url' )->alias(
			static function ( $url ) {
				return 'https://example.test' . $url;
			}
		);
		Functions\when( 'get_permalink' )->alias(
			static function ( $id ) {
				return 'https://example.test/?page_id=' . $id;
			}
		);
		Functions\when( 'get_option' )->alias(
			function ( $key, $fallback = false ) {
				return $this->options[ $key ] ?? $fallback;
			}
		);
		Functions\when( 'update_option' )->alias(
			function ( $key, $value ) {
				$this->options[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'get_post' )->alias(
			function ( $id ) {
				return isset( $this->pages[ $id ] ) ? (object) $this->pages[ $id ] : null;
			}
		);
		Functions\when( 'get_post_status' )->alias(
			function ( $id ) {
				return $this->pages[ $id ]['post_status'] ?? false;
			}
		);
		Functions\when( 'wp_insert_post' )->alias(
			function ( $data ) {
				$id                 = count( $this->pages ) + 1;
				$this->pages[ $id ] = $data;
				return $id;
			}
		);
	}

	/**
	 * Existing v0.1.0 fields and policy version survive safe new defaults.
	 *
	 * @return void
	 */
	public function test_old_settings_and_fourth_link() {
		$old = \ITD_Cookies_Settings::defaults();
		unset( $old['link_4_label'], $old['link_4_url'], $old['auto_footer'] );
		$old['consent_version'] = 'old-policy';
		foreach ( array( 1, 2, 3 ) as $index ) {
			$old[ 'link_' . $index . '_url' ] = '/legal-' . $index;
		}
		$this->options['itd_cookies_settings'] = $old;
		$new                                   = \ITD_Cookies_Settings::get();
		foreach ( $old as $key => $value ) {
			self::assertSame( $value, $new[ $key ], $key );
		}
		self::assertSame( 0, $new['auto_footer'] );
		self::assertSame( '', $new['link_4_url'] );
		$new['link_4_url'] = 'javascript:alert(1)';
		self::assertSame( '', \ITD_Cookies_Settings::sanitize( $new )['link_4_url'] );
		$new['link_4_url'] = 'https://example.test/agreement';
		self::assertSame( $new['link_4_url'], \ITD_Cookies_Settings::sanitize( $new )['link_4_url'] );
	}

	/**
	 * Registry mirrors enabled flags and validated provider IDs.
	 *
	 * @return void
	 */
	public function test_registry_and_empty_categories() {
		$settings = \ITD_Cookies_Settings::get();
		$list     = \ITD_Cookies_Services::get( $settings );
		self::assertCount( 2, $list );
		self::assertCount( 0, \ITD_Cookies_Services::in_category( $list, 'analytics' ) );
		$settings['metrika_enabled']    = 1;
		$settings['metrika_id']         = '12345678';
		$settings['ga4_enabled']        = 1;
		$settings['ga4_measurement_id'] = 'G-TEST12345';
		$list                           = \ITD_Cookies_Services::get( $settings );
		self::assertSame( array( 'yandex-metrika', 'google-analytics-4' ), array_column( $list, 'id' ) );
		self::assertCount( 2, \ITD_Cookies_Services::in_category( $list, 'analytics' ) );
		self::assertCount( 0, \ITD_Cookies_Services::in_category( $list, 'marketing' ) );
	}

	/**
	 * Extension entries are validated and descriptive output is escaped.
	 *
	 * @return void
	 */
	public function test_registry_extension_validation() {
		Functions\when( 'apply_filters' )->alias(
			static function ( $hook, $entries ) {
				$entries[] = array(
					'id'          => 'chat',
					'name'        => '<b>Chat</b>',
					'category'    => 'functional',
					'enabled'     => true,
					'description' => 'Chat feature',
				);
				$entries[] = array(
					'id'          => 'bad',
					'name'        => 'Bad',
					'category'    => 'unknown',
					'enabled'     => true,
					'description' => 'Ignored',
				);
				return $entries;
			}
		);
		$services = \ITD_Cookies_Services::get( \ITD_Cookies_Settings::get() );
		self::assertCount( 3, $services );
		self::assertSame( 'Chat', $services[2]['name'] );
		self::assertCount( 1, \ITD_Cookies_Services::in_category( $services, 'functional' ) );
	}

	/**
	 * Dynamic policy lists only configured services and actual consent lifetime.
	 *
	 * @return void
	 */
	public function test_dynamic_policy_shortcode() {
		$legal = new \ITD_Cookies_Legal();
		self::assertStringNotContainsString( 'Yandex Metrika', $legal->policy_shortcode() );
		$settings                              = \ITD_Cookies_Settings::defaults();
		$settings['metrika_enabled']           = 1;
		$settings['metrika_id']                = '12345678';
		$settings['consent_days']              = 120;
		$this->options['itd_cookies_settings'] = $settings;
		$html                                  = $legal->policy_shortcode();
		self::assertStringContainsString( 'Yandex Metrika', $html );
		self::assertStringNotContainsString( 'Google Analytics 4', $html );
		self::assertStringContainsString( '120 days', $html );
		self::assertStringContainsString( 'only after', $html );
		self::assertStringContainsString( 'data-itd-cookies-open', $html );
	}

	/**
	 * Page creation is repeatable, and explicit URLs retain priority.
	 *
	 * @return void
	 */
	public function test_managed_page_lifecycle() {
		$id = \ITD_Cookies_Legal::ensure_page();
		self::assertSame( '[itd_cookies_policy]', $this->pages[ $id ]['post_content'] );
		$this->pages[ $id ]['post_content'] = 'Owner edits';
		self::assertSame( $id, \ITD_Cookies_Legal::ensure_page() );
		self::assertCount( 1, $this->pages );
		self::assertSame( 'Owner edits', $this->pages[ $id ]['post_content'] );
		$settings = \ITD_Cookies_Settings::get();
		self::assertSame( 'https://example.test/?page_id=1', \ITD_Cookies_Legal::links( $settings )[0]['url'] );
		$settings['link_3_url'] = '/custom-policy';
		self::assertSame( 'https://example.test/custom-policy', \ITD_Cookies_Legal::links( $settings )[0]['url'] );
		$this->pages[ $id ]['post_status'] = 'trash';
		self::assertSame( 2, \ITD_Cookies_Legal::ensure_page() );
	}

	/**
	 * Footer is off by default; legal and legacy settings shortcodes work.
	 *
	 * @return void
	 */
	public function test_footer_and_existing_opener() {
		$legal = new \ITD_Cookies_Legal();
		ob_start();
		$legal->render_footer();
		self::assertSame( '', ob_get_clean() );
		$settings                              = \ITD_Cookies_Settings::defaults();
		$settings['link_4_url']                = '/agreement';
		$settings['auto_footer']               = 1;
		$this->options['itd_cookies_settings'] = $settings;
		$html                                  = $legal->links_shortcode();
		self::assertStringContainsString( 'https://example.test/agreement', $html );
		self::assertStringNotContainsString( 'Privacy policy', $html );
		self::assertStringContainsString( 'data-itd-cookies-open', $html );
		ob_start();
		$legal->render_footer();
		self::assertStringContainsString( 'itd-cookies-footer', ob_get_clean() );
		self::assertSame( \ITD_Cookies_Legal::opener(), ( new \ITD_Cookies_Plugin() )->settings_shortcode() );
	}
}
