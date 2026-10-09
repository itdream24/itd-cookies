<?php
/**
 * Tests for the independent ITD Cookies settings and consent model.
 *
 * @package ITD\Cookies\Tests
 */

namespace ITD\Cookies\Tests;

use Brain\Monkey\Functions;

require_once dirname( __DIR__, 2 ) . '/admin/class-itd-cookies-settings.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-itd-cookies-consent.php';

/**
 * Exercise legacy migration without a WordPress database.
 */
final class ITDCookiesSettingsTest extends TestCase {
	/**
	 * Set up WordPress sanitization doubles for each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		unset( $_COOKIE['itd_cookies_consent'] );
		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $text ) {
				return trim( strip_tags( (string) $text ) );
			}
		);
		Functions\when( 'sanitize_textarea_field' )->alias(
			static function ( $text ) {
				return trim( strip_tags( (string) $text ) );
			}
		);
		Functions\when( 'wp_parse_url' )->alias(
			static function ( $url, $component = -1 ) {
				return parse_url( $url, $component );
			}
		);
		Functions\when( 'esc_url_raw' )->alias(
			static function ( $url ) {
				return $url;
			}
		);
		Functions\when( 'home_url' )->justReturn( 'http://itd-cookies.local/' );
		Functions\when( 'wp_unslash' )->alias(
			static function ( $value ) {
				return $value;
			}
		);
	}

	/**
	 * Normalize legal links without introducing settings notices on reads.
	 *
	 * @return void
	 */
	public function test_legal_url_normalization_is_safe_and_quiet() {
		Functions\expect( 'add_settings_error' )->never();
		$cases = array(
			'https://example.org/policy?lang=ru#cookies' => 'https://example.org/policy?lang=ru#cookies',
			'https://пример.рф/политика'                 => 'https://пример.рф/политика',
			'/policy?lang=ru#cookies'                    => '/policy?lang=ru#cookies',
			'http://itd-cookies.local/policy?lang=ru#cookies' => '/policy?lang=ru#cookies',
			'http://ITD-COOKIES.local:80'                => '/',
			'http://external.example/policy'             => '',
			'http://itd-cookies.local:8080/policy'       => '',
			'http://itd-cookies.local.external.example/policy' => '',
			'http://itd-cookies.local//external.example' => '',
			'http://user@itd-cookies.local/policy'       => '',
			'//external.example/policy'                  => '',
			'/\external.example'                         => '',
			'/%2fexternal.example'                       => '',
			'/%5cexternal.example'                       => '',
			'https://bad..example/policy'                => '',
			'https://example.org:99999/policy'           => '',
			'https://example.org/a b'                    => '',
			'https://'                                   => '',
			'not a URL'                                  => '',
			'javascript:alert(1)'                        => '',
		);
		foreach ( $cases as $input => $expected ) {
			self::assertSame( $expected, \ITD_Cookies_Settings::sanitize( array( 'link_1_url' => $input ) )['link_1_url'], $input );
		}
		Functions\when( 'get_option' )->justReturn( array( 'link_1_url' => 'http://external.example' ) );
		self::assertSame( '', \ITD_Cookies_Settings::get()['link_1_url'] );
	}

	/**
	 * An invalid submission keeps each previous link and reports each error.
	 *
	 * @return void
	 */
	public function test_rejected_legal_urls_preserve_previous_values_on_save() {
		Functions\when( 'get_option' )->justReturn(
			array(
				'link_1_url' => 'https://example.org/old',
				'link_2_url' => '/old-policy',
				'link_3_url' => '/third',
				'link_4_url' => '/fourth',
			)
		);
		$errors = array();
		Functions\when( 'add_settings_error' )->alias(
			static function ( $setting, $code, $message, $type ) use ( &$errors ) {
				$errors[] = array( $setting, $code, $message, $type );
			}
		);
		$output = \ITD_Cookies_Settings::sanitize_submission(
			array(
				'banner_title' => 'Updated title',
				'link_1_url'   => 'http://external.example',
				'link_2_url'   => 'invalid',
				'link_3_url'   => '//external.example',
				'link_4_url'   => array( 'unexpected' ),
			)
		);
		self::assertSame( 'Updated title', $output['banner_title'] );
		self::assertSame( 'https://example.org/old', $output['link_1_url'] );
		self::assertSame( '/old-policy', $output['link_2_url'] );
		self::assertSame( '/third', $output['link_3_url'] );
		self::assertSame( '/fourth', $output['link_4_url'] );
		self::assertCount( 4, $errors );
		foreach ( $errors as $index => $error ) {
			self::assertSame( 'itd_cookies_settings', $error[0] );
			self::assertSame( 'link_' . ( $index + 1 ) . '_url', $error[1] );
			self::assertStringContainsString( 'previous link has been kept', $error[2] );
			self::assertSame( 'error', $error[3] );
		}
	}

	/**
	 * Valid saves and deliberate clearing must not create errors.
	 *
	 * @return void
	 */
	public function test_valid_legal_urls_can_replace_or_clear_previous_links() {
		Functions\when( 'get_option' )->justReturn(
			array(
				'link_1_url' => '/old',
				'link_4_url' => '/clear-me',
			)
		);
		Functions\expect( 'add_settings_error' )->never();
		$output = \ITD_Cookies_Settings::sanitize_submission(
			array(
				'link_1_url' => 'https://example.org/new',
				'link_2_url' => '/new',
				'link_3_url' => 'http://itd-cookies.local/new',
				'link_4_url' => '',
			)
		);
		self::assertSame( 'https://example.org/new', $output['link_1_url'] );
		self::assertSame( '/new', $output['link_2_url'] );
		self::assertSame( '/new', $output['link_3_url'] );
		self::assertSame( '', $output['link_4_url'] );
	}

	/**
	 * HTTP conversion requires the site's exact scheme, host and port.
	 *
	 * @return void
	 */
	public function test_http_conversion_requires_exact_current_origin() {
		Functions\when( 'home_url' )->justReturn( 'http://itd-cookies.local:8080/subdirectory/' );
		self::assertSame( '/policy', \ITD_Cookies_Settings::sanitize( array( 'link_1_url' => 'http://itd-cookies.local:8080/policy' ) )['link_1_url'] );
		self::assertSame( '', \ITD_Cookies_Settings::sanitize( array( 'link_1_url' => 'http://itd-cookies.local/policy' ) )['link_1_url'] );
		Functions\when( 'home_url' )->justReturn( 'https://itd-cookies.local/' );
		self::assertSame( '', \ITD_Cookies_Settings::sanitize( array( 'link_1_url' => 'http://itd-cookies.local/policy' ) )['link_1_url'] );
	}

	/**
	 * Import only consent and provider fields, preserving edits on repeat.
	 *
	 * @return void
	 */
	public function test_legacy_settings_import_only_relevant_fields_once() {
		$options = array(
			'itd_modubricks_settings' => array(
				'enabled'            => 1,
				'banner_title'       => 'Existing title',
				'metrika_enabled'    => 1,
				'metrika_id'         => '12345678',
				'ga4_enabled'        => 1,
				'ga4_measurement_id' => 'G-TEST12345',
				'consent_version'    => 'old-policy',
				'analytics_mode'     => 'informational',
				'update_channel'     => 'beta',
			),
		);
		Functions\when( 'get_option' )->alias(
			static function ( $name, $fallback = false ) use ( &$options ) {
				return array_key_exists( $name, $options ) ? $options[ $name ] : $fallback;
			}
		);
		Functions\when( 'add_option' )->alias(
			static function ( $name, $value ) use ( &$options ) {
				if ( array_key_exists( $name, $options ) ) {
					return false;
				}
				$options[ $name ] = $value;
				return true;
			}
		);

		\ITD_Cookies_Settings::migrate_legacy();
		self::assertSame( 'copied-v1', $options['itd_cookies_migration_version'] );
		self::assertSame( '12345678', $options['itd_cookies_settings']['metrika_id'] );
		self::assertSame( 'G-TEST12345', $options['itd_cookies_settings']['ga4_measurement_id'] );
		self::assertSame( 'old-policy', $options['itd_cookies_settings']['consent_version'] );
		self::assertArrayNotHasKey( 'analytics_mode', $options['itd_cookies_settings'] );
		self::assertArrayNotHasKey( 'update_channel', $options['itd_cookies_settings'] );
		$options['itd_cookies_settings']['banner_title'] = 'New title';
		\ITD_Cookies_Settings::migrate_legacy();
		self::assertSame( 'New title', $options['itd_cookies_settings']['banner_title'] );
		self::assertArrayHasKey( 'itd_modubricks_settings', $options );
	}

	/**
	 * Preserve settings created before migration runs.
	 *
	 * @return void
	 */
	public function test_existing_new_settings_are_never_overwritten() {
		$options = array(
			'itd_cookies_settings'    => array( 'banner_title' => 'New title' ),
			'itd_modubricks_settings' => array( 'banner_title' => 'Old title' ),
		);
		Functions\when( 'get_option' )->alias(
			static function ( $name, $fallback = false ) use ( &$options ) {
				return array_key_exists( $name, $options ) ? $options[ $name ] : $fallback;
			}
		);
		Functions\when( 'add_option' )->alias(
			static function ( $name, $value ) use ( &$options ) {
				$options[ $name ] = $value;
				return true;
			}
		);
		\ITD_Cookies_Settings::migrate_legacy();
		self::assertSame( 'New title', $options['itd_cookies_settings']['banner_title'] );
		self::assertSame( 'existing-v1', $options['itd_cookies_migration_version'] );
	}

	/**
	 * Preserve the legacy Metrika default when an ID exists.
	 *
	 * @return void
	 */
	public function test_legacy_metrika_id_without_enable_flag_is_imported() {
		$options = array( 'itd_modubricks_settings' => array( 'metrika_id' => '12345678' ) );
		Functions\when( 'get_option' )->alias(
			static function ( $name, $fallback = false ) use ( &$options ) {
				return array_key_exists( $name, $options ) ? $options[ $name ] : $fallback;
			}
		);
		Functions\when( 'add_option' )->alias(
			static function ( $name, $value ) use ( &$options ) {
				$options[ $name ] = $value;
				return true;
			}
		);
		\ITD_Cookies_Settings::migrate_legacy();
		self::assertSame( 1, $options['itd_cookies_settings']['metrika_enabled'] );
	}

	/**
	 * Reject executable snippets, invalid IDs, and invalid URLs.
	 *
	 * @return void
	 */
	public function test_sanitization_rejects_code_and_invalid_identifiers() {
		$settings = \ITD_Cookies_Settings::sanitize(
			array(
				'enabled'            => '1',
				'banner_title'       => '<script>alert(1)</script>Title',
				'metrika_id'         => '<script>123</script>',
				'ga4_measurement_id' => 'not-ga4',
				'banner_text_size'   => '999%',
				'consent_days'       => '99999',
				'link_1_url'         => 'javascript:alert(1)',
			)
		);
		self::assertSame( '', $settings['metrika_id'] );
		self::assertSame( '', $settings['ga4_measurement_id'] );
		self::assertSame( '', $settings['link_1_url'] );
		self::assertSame( 'standard', $settings['banner_text_size'] );
		self::assertSame( 3650, $settings['consent_days'] );
	}

	/**
	 * Keep the five consent text sizes and a safe default.
	 *
	 * @return void
	 */
	public function test_consent_text_size_presets() {
		self::assertSame( 'standard', \ITD_Cookies_Settings::defaults()['banner_text_size'] );
		foreach ( array( 'very-small', 'small', 'standard', 'large', 'very-large' ) as $size ) {
			self::assertSame( $size, \ITD_Cookies_Settings::sanitize( array( 'banner_text_size' => $size ) )['banner_text_size'] );
		}
		self::assertSame( 'standard', \ITD_Cookies_Settings::sanitize( array( 'banner_text_size' => 'invalid' ) )['banner_text_size'] );
	}

	/**
	 * Validate the PHP consent API against version and schema errors.
	 *
	 * @return void
	 */
	public function test_php_consent_api_is_fail_closed_and_versioned() {
		$settings = \ITD_Cookies_Settings::defaults();
		unset( $_COOKIE['itd_cookies_consent'] );
		self::assertTrue( \ITD_Cookies_Consent::allowed( 'necessary', $settings ) );
		self::assertFalse( \ITD_Cookies_Consent::allowed( 'analytics', $settings ) );
		$_COOKIE['itd_cookies_consent'] = rawurlencode(
			json_encode(
				array(
					'schema'     => 1,
					'version'    => '1',
					'expiresAt'  => ( time() + 3600 ) * 1000,
					'categories' => array(
						'necessary'  => true,
						'functional' => false,
						'analytics'  => true,
						'marketing'  => false,
					),
				)
			)
		);
		self::assertTrue( \ITD_Cookies_Consent::allowed( 'analytics', $settings ) );
		$disabled            = $settings;
		$disabled['enabled'] = 0;
		self::assertFalse( \ITD_Cookies_Consent::allowed( 'analytics', $disabled ) );
		$valid_cookie = $_COOKIE['itd_cookies_consent'];
		$malformed    = json_decode( rawurldecode( $_COOKIE['itd_cookies_consent'] ), true );
		unset( $malformed['categories']['marketing'] );
		$_COOKIE['itd_cookies_consent'] = rawurlencode( json_encode( $malformed ) );
		self::assertFalse( \ITD_Cookies_Consent::allowed( 'analytics', $settings ) );
		$_COOKIE['itd_cookies_consent'] = $valid_cookie;
		$settings['consent_version']    = '2';
		self::assertFalse( \ITD_Cookies_Consent::allowed( 'analytics', $settings ) );
		unset( $_COOKIE['itd_cookies_consent'] );
	}

	/**
	 * Reject malformed and unknown fields in the server-side consent signal.
	 *
	 * @return void
	 */
	public function test_php_consent_api_rejects_unknown_or_malformed_fields() {
		$settings = \ITD_Cookies_Settings::defaults();
		$valid    = array(
			'schema'     => 1,
			'version'    => '1',
			'expiresAt'  => ( time() + 3600 ) * 1000,
			'categories' => array(
				'necessary'  => true,
				'functional' => true,
				'analytics'  => true,
				'marketing'  => true,
			),
		);
		$cases    = array( '{"schema":1,' );
		foreach ( array( '1', 0, 2 ) as $schema ) {
			$cases[] = json_encode( array_merge( $valid, array( 'schema' => $schema ) ) );
		}
		$cases[]    = json_encode( array_merge( $valid, array( 'unknown' => true ) ) );
		$categories = $valid['categories'];
		unset( $categories['marketing'] );
		$cases[] = json_encode( array_merge( $valid, array( 'categories' => $categories ) ) );
		$cases[] = json_encode( array_merge( $valid, array( 'categories' => array_merge( $valid['categories'], array( 'unknown' => true ) ) ) ) );
		$cases[] = json_encode( array_merge( $valid, array( 'categories' => array_merge( $valid['categories'], array( 'analytics' => 'true' ) ) ) ) );
		foreach ( $cases as $raw ) {
			$_COOKIE['itd_cookies_consent'] = rawurlencode( $raw );
			self::assertTrue( \ITD_Cookies_Consent::allowed( 'necessary', $settings ) );
			foreach ( array( 'functional', 'analytics', 'marketing' ) as $category ) {
				self::assertFalse( \ITD_Cookies_Consent::allowed( $category, $settings ), $category );
			}
		}
		unset( $_COOKIE['itd_cookies_consent'] );
	}
}
