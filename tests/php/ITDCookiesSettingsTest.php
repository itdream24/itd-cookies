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
			static function ( $url, $component ) {
				return parse_url( $url, $component );
			}
		);
		Functions\when( 'esc_url_raw' )->alias(
			static function ( $url ) {
				return $url;
			}
		);
		Functions\when( 'wp_unslash' )->alias(
			static function ( $value ) {
				return $value;
			}
		);
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
