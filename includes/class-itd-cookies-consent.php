<?php
/**
 * Consent model for integrations that execute in PHP.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read the browser's versioned consent signal for PHP integrations.
 */
final class ITD_Cookies_Consent {
	const COOKIE_NAME = 'itd_cookies_consent';
	const SCHEMA      = 1;

	/**
	 * Read one category from a strictly validated first-party cookie.
	 * Full-page caches must not vary shared HTML solely from this value.
	 *
	 * @param string $category Category name.
	 * @param array  $settings Sanitized settings.
	 * @return bool
	 */
	public static function allowed( $category, array $settings ) {
		if ( 'necessary' === $category ) {
			return true;
		}

		if ( empty( $settings['enabled'] ) ) {
			return false;
		}

		if ( ! in_array( $category, array( 'functional', 'analytics', 'marketing' ), true ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON is parsed and every expected field is validated below.
		$raw = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) : '';
		if ( ! is_string( $raw ) || strlen( $raw ) > 2048 ) {
			return false;
		}

		$state = json_decode( rawurldecode( $raw ), true );
		if ( ! is_array( $state ) || JSON_ERROR_NONE !== json_last_error() ) {
			return false;
		}

		if (
			4 !== count( $state ) ||
			! isset( $state['schema'], $state['version'], $state['expiresAt'], $state['categories'] ) ||
			self::SCHEMA !== $state['schema'] ||
			! is_string( $state['version'] ) ||
			! hash_equals( (string) $settings['consent_version'], $state['version'] ) ||
			! is_int( $state['expiresAt'] ) ||
			$state['expiresAt'] <= ( time() * 1000 ) ||
			! is_array( $state['categories'] ) ||
			4 !== count( $state['categories'] ) ||
			! isset( $state['categories']['necessary'] ) ||
			true !== $state['categories']['necessary'] ||
			! isset( $state['categories']['functional'], $state['categories']['analytics'], $state['categories']['marketing'] ) ||
			! is_bool( $state['categories']['functional'] ) ||
			! is_bool( $state['categories']['analytics'] ) ||
			! is_bool( $state['categories']['marketing'] )
		) {
			return false;
		}

		return isset( $state['categories'][ $category ] ) && true === $state['categories'][ $category ];
	}
}
