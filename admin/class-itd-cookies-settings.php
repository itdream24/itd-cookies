<?php
/**
 * Settings and one-time import from ITD ModuBricks.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Own the ITD Cookies option schema and one-time ModuBricks import.
 */
final class ITD_Cookies_Settings {
	const OPTION_NAME      = 'itd_cookies_settings';
	const MIGRATION_MARKER = 'itd_cookies_migration_version';
	const LEGACY_OPTION    = 'itd_modubricks_settings';
	const SETTINGS_GROUP   = 'itd_cookies_settings_group';
	const PAGE_SLUG        = 'itd-cookies';

	/**
	 * Defaults never enable an optional tracker without its valid ID.
	 *
	 * @return array<string,int|string>
	 */
	public static function defaults() {
		return array(
			'enabled'                       => 1,
			'banner_title'                  => __( 'Privacy settings', 'itd-cookies' ),
			'banner_text'                   => __( 'Choose which cookies this site may use. You can change your choice later.', 'itd-cookies' ),
			'banner_text_size'              => 'standard',
			'link_1_label'                  => __( 'Privacy policy', 'itd-cookies' ),
			'link_1_url'                    => '',
			'link_2_label'                  => __( 'Personal data processing policy', 'itd-cookies' ),
			'link_2_url'                    => '',
			'link_3_label'                  => __( 'Cookie policy', 'itd-cookies' ),
			'link_3_url'                    => '',
			'link_4_label'                  => __( 'User agreement', 'itd-cookies' ),
			'link_4_url'                    => '',
			'auto_footer'                   => 0,
			'metrika_enabled'               => 0,
			'metrika_id'                    => '',
			'metrika_webvisor'              => 0,
			'metrika_clickmap'              => 1,
			'metrika_track_links'           => 1,
			'metrika_accurate_track_bounce' => 1,
			'metrika_ecommerce'             => 0,
			'metrika_ecommerce_data_layer'  => 'dataLayer',
			'ga4_enabled'                   => 0,
			'ga4_measurement_id'            => '',
			'gtm_enabled'                   => 0,
			'gtm_container_id'              => '',
			'clarity_enabled'               => 0,
			'clarity_project_id'            => '',
			'meta_enabled'                  => 0,
			'meta_pixel_id'                 => '',
			'consent_days'                  => 365,
			'consent_version'               => '1',
		);
	}

	/**
	 * Read and re-sanitize stored settings, or return defaults before activation.
	 *
	 * @return array<string,int|string>
	 */
	public static function get() {
		$stored = get_option( self::OPTION_NAME, array() );
		$stored = is_array( $stored ) ? $stored : array();

		return self::sanitize( array_merge( self::defaults(), $stored ) );
	}

	/**
	 * Sanitize the complete Settings API payload.
	 *
	 * @param mixed $input Submitted option.
	 * @return array<string,int|string>
	 */
	public static function sanitize( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$days    = self::scalar( $input, 'consent_days' );
		$days    = preg_match( '/\A[0-9]+\z/D', $days ) ? (int) $days : 365;
		$size    = self::scalar( $input, 'banner_text_size' );
		$size    = in_array( $size, array( 'very-small', 'small', 'standard', 'large', 'very-large' ), true ) ? $size : 'standard';
		$id      = self::scalar( $input, 'metrika_id' );
		$ga4     = self::scalar( $input, 'ga4_measurement_id' );
		$gtm     = isset( $input['gtm_container_id'] ) && is_string( $input['gtm_container_id'] ) ? trim( $input['gtm_container_id'] ) : '';
		$clarity = isset( $input['clarity_project_id'] ) && is_string( $input['clarity_project_id'] ) ? trim( $input['clarity_project_id'] ) : '';
		$meta    = isset( $input['meta_pixel_id'] ) && is_string( $input['meta_pixel_id'] ) ? trim( $input['meta_pixel_id'] ) : '';
		$layer   = self::scalar( $input, 'metrika_ecommerce_data_layer' );
		$ver     = sanitize_text_field( self::scalar( $input, 'consent_version' ) );

		$title    = sanitize_text_field( self::scalar( $input, 'banner_title' ) );
		$text     = sanitize_textarea_field( self::scalar( $input, 'banner_text' ) );
		$defaults = self::defaults();
		$output   = array(
			'enabled'                       => self::flag( $input, 'enabled' ),
			'auto_footer'                   => self::flag( $input, 'auto_footer' ),
			'banner_title'                  => '' !== $title ? $title : $defaults['banner_title'],
			'banner_text'                   => '' !== $text ? $text : $defaults['banner_text'],
			'banner_text_size'              => $size,
			'metrika_enabled'               => self::flag( $input, 'metrika_enabled' ),
			'metrika_id'                    => preg_match( '/\A[1-9][0-9]{0,14}\z/D', $id ) ? $id : '',
			'metrika_webvisor'              => self::flag( $input, 'metrika_webvisor' ),
			'metrika_clickmap'              => self::flag( $input, 'metrika_clickmap' ),
			'metrika_track_links'           => self::flag( $input, 'metrika_track_links' ),
			'metrika_accurate_track_bounce' => self::flag( $input, 'metrika_accurate_track_bounce' ),
			'metrika_ecommerce'             => self::flag( $input, 'metrika_ecommerce' ),
			'metrika_ecommerce_data_layer'  => preg_match( '/\A[A-Za-z_$][A-Za-z0-9_$]{0,63}\z/D', $layer ) ? $layer : 'dataLayer',
			'ga4_enabled'                   => self::flag( $input, 'ga4_enabled' ),
			'ga4_measurement_id'            => preg_match( '/\AG-[A-Z0-9]{4,32}\z/D', $ga4 ) ? $ga4 : '',
			'gtm_enabled'                   => self::flag( $input, 'gtm_enabled' ),
			'gtm_container_id'              => preg_match( '/\AGTM-[A-Z0-9]{4,32}\z/D', $gtm ) ? $gtm : '',
			'clarity_enabled'               => self::flag( $input, 'clarity_enabled' ),
			'clarity_project_id'            => preg_match( '/\A[a-z0-9]{1,64}\z/D', $clarity ) ? $clarity : '',
			'meta_enabled'                  => self::flag( $input, 'meta_enabled' ),
			'meta_pixel_id'                 => preg_match( '/\A[1-9][0-9]{0,19}\z/D', $meta ) ? $meta : '',
			'consent_days'                  => max( 1, min( 3650, $days ) ),
			'consent_version'               => '' !== trim( $ver ) ? substr( $ver, 0, 100 ) : '1',
		);

		for ( $index = 1; $index <= 4; $index++ ) {
			$output[ 'link_' . $index . '_label' ] = sanitize_text_field( self::scalar( $input, 'link_' . $index . '_label' ) );
			if ( '' === $output[ 'link_' . $index . '_label' ] ) {
				$output[ 'link_' . $index . '_label' ] = $defaults[ 'link_' . $index . '_label' ];
			}
			$output[ 'link_' . $index . '_url' ] = self::sanitize_url( self::scalar( $input, 'link_' . $index . '_url' ) );
		}

		return $output;
	}

	/**
	 * Copy only consent and analytics options once. Never delete legacy data.
	 *
	 * @return void
	 */
	public static function migrate_legacy() {
		if ( false !== get_option( self::MIGRATION_MARKER, false ) ) {
			return;
		}

		if ( false !== get_option( self::OPTION_NAME, false ) ) {
			add_option( self::MIGRATION_MARKER, 'existing-v1', '', false );
			return;
		}

		$legacy = get_option( self::LEGACY_OPTION, false );
		if ( ! is_array( $legacy ) ) {
			$created = add_option( self::OPTION_NAME, self::defaults(), '', false );
			if ( $created ) {
				add_option( self::MIGRATION_MARKER, 'fresh-v1', '', false );
			}
			return;
		}

		$allowed = array_diff(
			array_keys( self::defaults() ),
			array( 'gtm_enabled', 'gtm_container_id', 'clarity_enabled', 'clarity_project_id', 'meta_enabled', 'meta_pixel_id' )
		);
		$copy    = array_intersect_key( $legacy, array_fill_keys( $allowed, true ) );
		$copy    = array_merge( self::defaults(), $copy );
		if ( ! array_key_exists( 'metrika_enabled', $legacy ) && ! empty( $legacy['metrika_id'] ) ) {
			$copy['metrika_enabled'] = 1;
		}
		$created = add_option( self::OPTION_NAME, self::sanitize( $copy ), '', false );
		if ( $created ) {
			add_option( self::MIGRATION_MARKER, 'copied-v1', '', false );
		}
	}

	/**
	 * Register the WordPress Settings API and the admin page.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( __CLASS__, 'migrate_legacy' ), 5 );
		add_action( 'admin_init', array( $this, 'register_option' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Register the new option and its sanitizer.
	 *
	 * @return void
	 */
	public function register_option() {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'default'           => self::defaults(),
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	/**
	 * Add the admin page under Settings.
	 *
	 * @return void
	 */
	public function add_page() {
		add_options_page(
			__( 'ITD Cookies', 'itd-cookies' ),
			__( 'ITD Cookies', 'itd-cookies' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the settings form with WordPress nonce and capability checks.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'itd-cookies' ) );
		}

		$settings = self::get();
		$sizes    = array(
			'very-small' => __( 'Very small', 'itd-cookies' ),
			'small'      => __( 'Small', 'itd-cookies' ),
			'standard'   => __( 'Standard', 'itd-cookies' ),
			'large'      => __( 'Large', 'itd-cookies' ),
			'very-large' => __( 'Very large', 'itd-cookies' ),
		);
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'ITD Cookies', 'itd-cookies' ); ?></h1>
			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>
				<h2><?php echo esc_html__( 'Consent banner', 'itd-cookies' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::checkbox_row( __( 'Enable consent banner', 'itd-cookies' ), 'enabled', $settings ); ?>
					<?php self::text_row( __( 'Title', 'itd-cookies' ), 'banner_title', $settings ); ?>
					<tr><th scope="row"><label for="itd-cookies-banner-text"><?php echo esc_html__( 'Description', 'itd-cookies' ); ?></label></th><td><textarea id="itd-cookies-banner-text" class="large-text" rows="5" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[banner_text]"><?php echo esc_textarea( $settings['banner_text'] ); ?></textarea></td></tr>
					<tr><th scope="row"><label for="itd-cookies-text-size"><?php echo esc_html__( 'Consent banner text size', 'itd-cookies' ); ?></label></th><td><select id="itd-cookies-text-size" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[banner_text_size]">
						<?php foreach ( $sizes as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $settings['banner_text_size'] ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select></td></tr>
					<?php self::text_row( __( 'Consent lifetime (days)', 'itd-cookies' ), 'consent_days', $settings ); ?>
					<?php self::text_row( __( 'Consent version', 'itd-cookies' ), 'consent_version', $settings ); ?>
				</table>
				<h2><?php echo esc_html__( 'Legal links', 'itd-cookies' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php for ( $index = 1; $index <= 4; $index++ ) : ?>
						<?php // translators: %d is the legal link number (1 to 4). ?>
						<?php self::text_row( sprintf( __( 'Link %d label', 'itd-cookies' ), $index ), 'link_' . $index . '_label', $settings ); ?>
						<?php // translators: %d is the legal link number (1 to 4). ?>
						<?php self::text_row( sprintf( __( 'Link %d URL', 'itd-cookies' ), $index ), 'link_' . $index . '_url', $settings ); ?>
					<?php endfor; ?>
				</table>
				<h2><?php echo esc_html__( 'Footer integration', 'itd-cookies' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::checkbox_row( __( 'Automatically display legal links at the bottom of the site', 'itd-cookies' ), 'auto_footer', $settings ); ?>
				</table>
				<p><?php echo esc_html__( 'Use [itd_cookies_legal_links] to place the links yourself. Automatic output is disabled by default.', 'itd-cookies' ); ?></p>
				<h2><?php echo esc_html__( 'Analytics', 'itd-cookies' ); ?></h2>
				<h3><?php echo esc_html__( 'Yandex Metrika', 'itd-cookies' ); ?></h3>
				<p><?php echo esc_html__( 'Enter the numeric counter ID. Executable snippets are not stored.', 'itd-cookies' ); ?></p>
				<table class="form-table" role="presentation">
					<?php self::checkbox_row( __( 'Enable Yandex Metrika', 'itd-cookies' ), 'metrika_enabled', $settings ); ?>
					<?php self::text_row( __( 'Counter ID', 'itd-cookies' ), 'metrika_id', $settings ); ?>
					<?php self::checkbox_row( __( 'Webvisor', 'itd-cookies' ), 'metrika_webvisor', $settings ); ?>
					<?php self::checkbox_row( __( 'Click map', 'itd-cookies' ), 'metrika_clickmap', $settings ); ?>
					<?php self::checkbox_row( __( 'Outbound link tracking', 'itd-cookies' ), 'metrika_track_links', $settings ); ?>
					<?php self::checkbox_row( __( 'Accurate bounce tracking', 'itd-cookies' ), 'metrika_accurate_track_bounce', $settings ); ?>
					<?php self::checkbox_row( __( 'Ecommerce', 'itd-cookies' ), 'metrika_ecommerce', $settings ); ?>
					<?php self::text_row( __( 'Ecommerce data layer', 'itd-cookies' ), 'metrika_ecommerce_data_layer', $settings ); ?>
				</table>
				<h3><?php echo esc_html__( 'Google Analytics 4', 'itd-cookies' ); ?></h3>
				<table class="form-table" role="presentation">
					<?php self::checkbox_row( __( 'Enable Google Analytics 4', 'itd-cookies' ), 'ga4_enabled', $settings ); ?>
					<?php self::text_row( __( 'Measurement ID', 'itd-cookies' ), 'ga4_measurement_id', $settings ); ?>
				</table>
				<h3><?php echo esc_html__( 'Google Tag Manager', 'itd-cookies' ); ?></h3>
				<table class="form-table" role="presentation">
					<?php self::checkbox_row( __( 'Enable Google Tag Manager', 'itd-cookies' ), 'gtm_enabled', $settings ); ?>
					<?php self::text_row( __( 'Container ID', 'itd-cookies' ), 'gtm_container_id', $settings ); ?>
				</table>
				<p><?php echo esc_html__( 'ITD Cookies controls loading of the GTM container after Analytics consent. Tags inside the container depend on its configuration and may require Marketing consent. Google Consent Mode is not configured automatically.', 'itd-cookies' ); ?></p>
				<h3><?php echo esc_html__( 'Microsoft Clarity', 'itd-cookies' ); ?></h3>
				<table class="form-table" role="presentation">
					<?php self::checkbox_row( __( 'Enable Microsoft Clarity', 'itd-cookies' ), 'clarity_enabled', $settings ); ?>
					<?php self::text_row( __( 'Project ID', 'itd-cookies' ), 'clarity_project_id', $settings ); ?>
				</table>
				<h2><?php echo esc_html__( 'Marketing', 'itd-cookies' ); ?></h2>
				<h3><?php echo esc_html__( 'Meta Pixel', 'itd-cookies' ); ?></h3>
				<table class="form-table" role="presentation">
					<?php self::checkbox_row( __( 'Enable Meta Pixel', 'itd-cookies' ), 'meta_enabled', $settings ); ?>
					<?php self::text_row( __( 'Pixel ID', 'itd-cookies' ), 'meta_pixel_id', $settings ); ?>
				</table>
				<h3><?php echo esc_html__( 'VK Ads', 'itd-cookies' ); ?></h3>
				<p><?php echo esc_html__( 'VK Ads integration is deferred until the official installation method can be verified.', 'itd-cookies' ); ?></p>
				<p><?php echo esc_html__( 'Place [itd_cookies_settings] in a page or footer to let visitors change their choice.', 'itd-cookies' ); ?></p>
				<?php submit_button( __( 'Save settings', 'itd-cookies' ) ); ?>
			</form>
			<h2><?php echo esc_html__( 'Cookie policy', 'itd-cookies' ); ?></h2>
			<p><?php echo esc_html__( 'The page uses [itd_cookies_policy] and follows your current configuration. An explicit Cookie Policy URL takes priority. Existing page content is never overwritten.', 'itd-cookies' ); ?></p>
			<?php $policy_id = ITD_Cookies_Legal::page_id(); ?>
			<?php if ( $policy_id ) : ?>
				<p><a class="button" href="<?php echo esc_url( get_edit_post_link( $policy_id ) ); ?>"><?php echo esc_html__( 'Open page', 'itd-cookies' ); ?></a></p>
			<?php else : ?>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="itd_cookies_create_policy">
					<?php wp_nonce_field( 'itd_cookies_create_policy' ); ?>
					<?php submit_button( __( 'Create Cookie Policy page', 'itd-cookies' ), 'secondary' ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a single text input row.
	 *
	 * @param string $label Field label.
	 * @param string $key Option key.
	 * @param array  $settings Sanitized settings.
	 * @return void
	 */
	private static function text_row( $label, $key, array $settings ) {
		$id = 'itd-cookies-' . str_replace( '_', '-', $key );
		?>
		<tr><th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th><td><input class="regular-text" type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $settings[ $key ] ); ?>"></td></tr>
		<?php
	}

	/**
	 * Render a single checkbox row.
	 *
	 * @param string $label Field label.
	 * @param string $key Option key.
	 * @param array  $settings Sanitized settings.
	 * @return void
	 */
	private static function checkbox_row( $label, $key, array $settings ) {
		$id = 'itd-cookies-' . str_replace( '_', '-', $key );
		?>
		<tr><th scope="row"><?php echo esc_html( $label ); ?></th><td><label for="<?php echo esc_attr( $id ); ?>"><input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( 1, $settings[ $key ] ); ?>><?php echo esc_html__( 'Enabled', 'itd-cookies' ); ?></label></td></tr>
		<?php
	}

	/**
	 * Convert an input field to trimmed scalar text.
	 *
	 * @param array  $input Raw option.
	 * @param string $key Option key.
	 * @return string
	 */
	private static function scalar( array $input, $key ) {
		$value = isset( $input[ $key ] ) ? $input[ $key ] : '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Convert an input field to a strict boolean flag.
	 *
	 * @param array  $input Raw option.
	 * @param string $key Option key.
	 * @return int
	 */
	private static function flag( array $input, $key ) {
		return isset( $input[ $key ] ) && in_array( $input[ $key ], array( true, 1, '1' ), true ) ? 1 : 0;
	}

	/**
	 * Accept only site-relative or HTTPS legal links.
	 *
	 * @param string $url Candidate URL.
	 * @return string
	 */
	private static function sanitize_url( $url ) {
		if ( '' === $url ) {
			return '';
		}
		if ( '/' === $url[0] && 0 !== strpos( $url, '//' ) ) {
			return esc_url_raw( $url );
		}
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		return 'https' === strtolower( (string) $scheme ) ? esc_url_raw( $url, array( 'https' ) ) : '';
	}
}
