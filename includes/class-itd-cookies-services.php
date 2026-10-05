<?php
/**
 * Descriptive service registry; never executes registered service code.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Describes the actual configuration for the UI and policy. */
final class ITD_Cookies_Services {
	/**
	 * Return the four stable consent categories and their descriptions.
	 *
	 * @return array
	 */
	public static function categories() {
		return array(
			'necessary'  => array(
				'name'        => __( 'Necessary', 'itd-cookies' ),
				'description' => __( 'Needed for site operation and saving your privacy choices.', 'itd-cookies' ),
			),
			'functional' => array(
				'name'        => __( 'Functional', 'itd-cookies' ),
				'description' => __( 'Support additional site features.', 'itd-cookies' ),
			),
			'analytics'  => array(
				'name'        => __( 'Analytics', 'itd-cookies' ),
				'description' => __( 'Help understand how the site is used.', 'itd-cookies' ),
			),
			'marketing'  => array(
				'name'        => __( 'Marketing', 'itd-cookies' ),
				'description' => __( 'Used by advertising and marketing services.', 'itd-cookies' ),
			),
		);
	}

	/**
	 * Filter descriptive entries. Extensions must gate their own runtime using
	 * itd_cookies_allowed / ITDCookies.allowed and the consent changed event.
	 *
	 * @param array $settings Sanitized plugin settings.
	 * @return array
	 */
	public static function get( array $settings ) {
		$entries = array(
			array(
				'id'          => 'yandex-metrika',
				'name'        => __( 'Yandex Metrika', 'itd-cookies' ),
				'category'    => 'analytics',
				'enabled'     => (bool) ( $settings['metrika_enabled'] && '' !== $settings['metrika_id'] ),
				'description' => __( 'Site usage statistics provided by Yandex.', 'itd-cookies' ),
			),
			array(
				'id'          => 'google-analytics-4',
				'name'        => __( 'Google Analytics 4', 'itd-cookies' ),
				'category'    => 'analytics',
				'enabled'     => (bool) ( $settings['ga4_enabled'] && '' !== $settings['ga4_measurement_id'] ),
				'description' => __( 'Site usage statistics provided by Google.', 'itd-cookies' ),
			),
		);
		$entries = apply_filters( 'itd_cookies_services', $entries, $settings );
		$result  = array();
		if ( ! is_array( $entries ) ) {
			return $result;
		}
		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['id'], $entry['name'], $entry['category'], $entry['enabled'], $entry['description'] ) ) {
				continue;
			}
			foreach ( array( 'id', 'name', 'category', 'description' ) as $key ) {
				if ( ! is_string( $entry[ $key ] ) ) {
					continue 2;
				}
			}
			$id = sanitize_key( $entry['id'] );
			if ( '' === $id || isset( $result[ $id ] ) || '' === trim( $entry['name'] ) || ! isset( self::categories()[ $entry['category'] ] ) ) {
				continue;
			}
			$result[ $id ] = array(
				'id'          => $id,
				'name'        => sanitize_text_field( $entry['name'] ),
				'category'    => $entry['category'],
				'enabled'     => in_array( $entry['enabled'], array( true, 1, '1' ), true ),
				'description' => sanitize_text_field( $entry['description'] ),
			);
		}
		return array_values( $result );
	}

	/**
	 * Select enabled services in one category.
	 *
	 * @param array  $services Registry entries.
	 * @param string $category Category identifier.
	 * @return array
	 */
	public static function in_category( array $services, $category ) {
		return array_values(
			array_filter(
				$services,
				static function ( $service ) use ( $category ) {
					return $service['enabled'] && $category === $service['category'];
				}
			)
		);
	}
}
