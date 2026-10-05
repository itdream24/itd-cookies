<?php
/**
 * Frontend and admin integration for ITD Cookies.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connect the consent controller and banner to WordPress.
 */
final class ITD_Cookies_Plugin {
	const ASSET_HANDLE = 'itd-cookies-consent';

	/**
	 * Register frontend assets, banner, and reopen shortcode.
	 *
	 * @return void
	 */
	public function register() {
		( new ITD_Cookies_Settings() )->register();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_banner' ), 5 );
		add_shortcode( 'itd_cookies_settings', array( $this, 'settings_shortcode' ) );
	}

	/**
	 * Ship only local controller assets and data before a browser choice.
	 * Analytics provider scripts are created by the controller after consent.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		$settings = ITD_Cookies_Settings::get();
		if ( ! $settings['enabled'] ) {
			return;
		}

		wp_enqueue_style( self::ASSET_HANDLE, ITD_COOKIES_URL . 'assets/css/consent.css', array(), ITD_COOKIES_VERSION );
		wp_enqueue_script( self::ASSET_HANDLE, ITD_COOKIES_URL . 'assets/js/consent.js', array(), ITD_COOKIES_VERSION, true );
		wp_localize_script(
			self::ASSET_HANDLE,
			'ITDCookiesConfig',
			array(
				'cookieName'       => ITD_Cookies_Consent::COOKIE_NAME,
				'legacyCookieName' => 'itd_modubricks_consent',
				'legacyMarkerName' => 'itd_cookies_legacy_migrated',
				'schema'           => ITD_Cookies_Consent::SCHEMA,
				'version'          => $settings['consent_version'],
				'lifetimeDays'     => $settings['consent_days'],
				'providers'        => $this->providers( $settings ),
			)
		);
	}

	/**
	 * Only numeric Yandex IDs and canonical GA4 IDs are accepted. There is no
	 * arbitrary executable snippet setting in the source plugin or this one.
	 *
	 * @param array $settings Sanitized settings.
	 * @return array
	 */
	private function providers( array $settings ) {
		$providers = array();
		if ( $settings['metrika_enabled'] && '' !== $settings['metrika_id'] ) {
			$options = array(
				'clickmap'            => (bool) $settings['metrika_clickmap'],
				'trackLinks'          => (bool) $settings['metrika_track_links'],
				'accurateTrackBounce' => (bool) $settings['metrika_accurate_track_bounce'],
				'webvisor'            => (bool) $settings['metrika_webvisor'],
			);
			if ( $settings['metrika_ecommerce'] ) {
				$options['ecommerce'] = $settings['metrika_ecommerce_data_layer'];
			}
			$providers[] = array(
				'type'    => 'yandex',
				'id'      => $settings['metrika_id'],
				'options' => $options,
			);
		}
		if ( $settings['ga4_enabled'] && '' !== $settings['ga4_measurement_id'] ) {
			$providers[] = array(
				'type' => 'ga4',
				'id'   => $settings['ga4_measurement_id'],
			);
		}
		return $providers;
	}

	/**
	 * Render a button that reopens cookie settings.
	 *
	 * @return string
	 */
	public function settings_shortcode() {
		$settings = ITD_Cookies_Settings::get();
		if ( ! $settings['enabled'] ) {
			return '';
		}
		return '<button type="button" class="itd-cookies-open" data-itd-cookies-open>' . esc_html__( 'Cookie settings', 'itd-cookies' ) . '</button>';
	}

	/**
	 * Render the consent banner and category settings panel.
	 *
	 * @return void
	 */
	public function render_banner() {
		if ( is_admin() ) {
			return;
		}
		$settings = ITD_Cookies_Settings::get();
		if ( ! $settings['enabled'] ) {
			return;
		}
		?>
		<section class="itd-cookies itd-cookies--text-<?php echo esc_attr( $settings['banner_text_size'] ); ?>" id="itd-cookies-dialog" role="dialog" aria-modal="false" aria-labelledby="itd-cookies-title" aria-describedby="itd-cookies-description" aria-hidden="true" data-itd-cookies-banner hidden>
			<div class="itd-cookies__summary" data-itd-cookies-summary>
				<h2 class="itd-cookies__title" id="itd-cookies-title"><?php echo esc_html( $settings['banner_title'] ); ?></h2>
				<p class="itd-cookies__description" id="itd-cookies-description"><?php echo esc_html( $settings['banner_text'] ); ?></p>
				<?php $this->render_legal_links( $settings ); ?>
				<div class="itd-cookies__actions">
					<button type="button" class="itd-cookies__button" data-itd-cookies-accept><?php echo esc_html__( 'Accept all', 'itd-cookies' ); ?></button>
					<button type="button" class="itd-cookies__button itd-cookies__button--secondary" data-itd-cookies-reject><?php echo esc_html__( 'Reject optional', 'itd-cookies' ); ?></button>
					<button type="button" class="itd-cookies__button itd-cookies__button--secondary" data-itd-cookies-customize><?php echo esc_html__( 'Customize', 'itd-cookies' ); ?></button>
				</div>
			</div>
			<div class="itd-cookies__panel" data-itd-cookies-panel hidden>
				<h2 class="itd-cookies__title" id="itd-cookies-settings-title" tabindex="-1"><?php echo esc_html__( 'Cookie settings', 'itd-cookies' ); ?></h2>
				<p class="itd-cookies__description"><?php echo esc_html__( 'Choose the optional categories you allow.', 'itd-cookies' ); ?></p>
				<div class="itd-cookies__choices">
					<label class="itd-cookies__choice"><span><?php echo esc_html__( 'Necessary', 'itd-cookies' ); ?></span><span><input type="checkbox" checked disabled aria-describedby="itd-cookies-necessary-note"><small id="itd-cookies-necessary-note"><?php echo esc_html__( 'Always active', 'itd-cookies' ); ?></small></span></label>
					<label class="itd-cookies__choice"><span><?php echo esc_html__( 'Functional', 'itd-cookies' ); ?></span><input type="checkbox" data-itd-cookies-category="functional"></label>
					<label class="itd-cookies__choice"><span><?php echo esc_html__( 'Analytics', 'itd-cookies' ); ?></span><input type="checkbox" data-itd-cookies-category="analytics"></label>
					<label class="itd-cookies__choice"><span><?php echo esc_html__( 'Marketing', 'itd-cookies' ); ?></span><input type="checkbox" data-itd-cookies-category="marketing"></label>
				</div>
				<div class="itd-cookies__actions">
					<button type="button" class="itd-cookies__button" data-itd-cookies-save><?php echo esc_html__( 'Save choice', 'itd-cookies' ); ?></button>
					<button type="button" class="itd-cookies__button itd-cookies__button--secondary" data-itd-cookies-cancel><?php echo esc_html__( 'Back', 'itd-cookies' ); ?></button>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Render up to three configured legal links.
	 *
	 * @param array $settings Sanitized settings.
	 * @return void
	 */
	private function render_legal_links( array $settings ) {
		$links = array();
		for ( $index = 1; $index <= 3; $index++ ) {
			$url = $settings[ 'link_' . $index . '_url' ];
			if ( '' === $url ) {
				continue;
			}
			if ( '/' === $url[0] && 0 !== strpos( $url, '//' ) ) {
				$url = home_url( $url );
			}
			$links[] = array(
				'url'   => $url,
				'label' => $settings[ 'link_' . $index . '_label' ],
			);
		}
		if ( ! $links ) {
			return;
		}
		?>
		<nav class="itd-cookies__links" aria-label="<?php echo esc_attr__( 'Legal information', 'itd-cookies' ); ?>">
			<?php foreach ( $links as $link ) : ?>
				<a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
	}
}
