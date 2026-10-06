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
		$settings = ITD_Cookies_Settings::sanitize( $settings );
		$entries  = array(
			array(
				'id'            => 'yandex-metrika',
				'provider_type' => 'yandex',
				'name'          => __( 'Yandex Metrika', 'itd-cookies' ),
				'category'      => 'analytics',
				'enabled'       => (bool) ( $settings['metrika_enabled'] && '' !== $settings['metrika_id'] ),
				'description'   => __( 'Site usage statistics provided by Yandex.', 'itd-cookies' ),
			),
			array(
				'id'            => 'google-analytics-4',
				'provider_type' => 'ga4',
				'name'          => __( 'Google Analytics 4', 'itd-cookies' ),
				'category'      => 'analytics',
				'enabled'       => (bool) ( $settings['ga4_enabled'] && '' !== $settings['ga4_measurement_id'] ),
				'description'   => __( 'Site usage statistics provided by Google.', 'itd-cookies' ),
			),
			array(
				'id'            => 'google-tag-manager',
				'provider_type' => 'gtm',
				'name'          => __( 'Google Tag Manager', 'itd-cookies' ),
				'category'      => 'analytics',
				'enabled'       => (bool) ( $settings['gtm_enabled'] && '' !== $settings['gtm_container_id'] ),
				'description'   => __( 'Loads a Google tag container. Tags inside depend on the container configuration and may use marketing services.', 'itd-cookies' ),
			),
			array(
				'id'            => 'microsoft-clarity',
				'provider_type' => 'clarity',
				'name'          => __( 'Microsoft Clarity', 'itd-cookies' ),
				'category'      => 'analytics',
				'enabled'       => (bool) ( $settings['clarity_enabled'] && '' !== $settings['clarity_project_id'] ),
				'description'   => __( 'Heatmaps and session recordings provided by Microsoft.', 'itd-cookies' ),
			),
			array(
				'id'            => 'meta-pixel',
				'provider_type' => 'meta',
				'name'          => __( 'Meta Pixel', 'itd-cookies' ),
				'category'      => 'marketing',
				'enabled'       => (bool) ( $settings['meta_enabled'] && '' !== $settings['meta_pixel_id'] ),
				'description'   => __( 'Advertising measurement provided by Meta.', 'itd-cookies' ),
			),
		);
		$entries  = apply_filters( 'itd_cookies_services', $entries, $settings );
		$result   = array();
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
			$type          = isset( $entry['provider_type'] ) && is_string( $entry['provider_type'] ) ? sanitize_key( $entry['provider_type'] ) : 'external';
			$result[ $id ] = array(
				'provider_type' => '' !== $type ? $type : 'external',
				'id'            => $id,
				'name'          => sanitize_text_field( $entry['name'] ),
				'category'      => $entry['category'],
				'enabled'       => in_array( $entry['enabled'], array( true, 1, '1' ), true ),
				'description'   => sanitize_text_field( $entry['description'] ),
			);
		}
		return array_values( $result );
	}

	/**
	 * Build only known native integrations from validated settings and registry.
	 * Descriptive extensions do not acquire executable code or arbitrary URLs.
	 *
	 * @param array $settings Plugin settings.
	 * @return array
	 */
	public static function providers( array $settings ) {
		$settings    = ITD_Cookies_Settings::sanitize( $settings );
		$definitions = array(
			'yandex-metrika'     => array( 'yandex', 'analytics', 'metrika_id' ),
			'google-analytics-4' => array( 'ga4', 'analytics', 'ga4_measurement_id' ),
			'google-tag-manager' => array( 'gtm', 'analytics', 'gtm_container_id' ),
			'microsoft-clarity'  => array( 'clarity', 'analytics', 'clarity_project_id' ),
			'meta-pixel'         => array( 'meta', 'marketing', 'meta_pixel_id' ),
		);
		$providers   = array();
		foreach ( self::get( $settings ) as $service ) {
			if ( ! $service['enabled'] || ! isset( $definitions[ $service['id'] ] ) ) {
				continue;
			}
			$definition = $definitions[ $service['id'] ];
			if ( $definition[0] !== $service['provider_type'] || $definition[1] !== $service['category'] || '' === $settings[ $definition[2] ] ) {
				continue;
			}
			$provider = array(
				'type'     => $definition[0],
				'category' => $definition[1],
				'id'       => $settings[ $definition[2] ],
			);
			if ( 'yandex' === $provider['type'] ) {
				$provider['options'] = array(
					'clickmap'            => (bool) $settings['metrika_clickmap'],
					'trackLinks'          => (bool) $settings['metrika_track_links'],
					'accurateTrackBounce' => (bool) $settings['metrika_accurate_track_bounce'],
					'webvisor'            => (bool) $settings['metrika_webvisor'],
				);
				if ( $settings['metrika_ecommerce'] ) {
					$provider['options']['ecommerce'] = $settings['metrika_ecommerce_data_layer'];
				}
			}
			$providers[] = $provider;
		}
		return $providers;
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
