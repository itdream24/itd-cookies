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
		( new ITD_Cookies_Legal() )->register();
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
		// Policy/footer styles are also available when consent collection is disabled.
		wp_enqueue_style( self::ASSET_HANDLE, ITD_COOKIES_URL . 'assets/css/consent.css', array(), ITD_COOKIES_VERSION );
		if ( ! $settings['enabled'] ) {
			return;
		}
		wp_enqueue_script( 'itd-cookies-providers', ITD_COOKIES_URL . 'assets/js/providers.js', array(), ITD_COOKIES_VERSION, true );
		wp_enqueue_script( self::ASSET_HANDLE, ITD_COOKIES_URL . 'assets/js/consent.js', array( 'itd-cookies-providers' ), ITD_COOKIES_VERSION, true );
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
	 * Only validated built-in provider IDs are accepted. There is no
	 * arbitrary executable snippet setting in the source plugin or this one.
	 *
	 * @param array $settings Sanitized settings.
	 * @return array
	 */
	private function providers( array $settings ) {
		return ITD_Cookies_Services::providers( $settings );
	}

	/**
	 * Render a button that reopens cookie settings.
	 *
	 * @return string
	 */
	public function settings_shortcode() {
		return ITD_Cookies_Legal::opener();
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
		$services = ITD_Cookies_Services::get( $settings );
		?>
		<div class="itd-cookies-backdrop" data-itd-cookies-backdrop hidden aria-hidden="true"></div>
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
				<p class="itd-cookies__description" id="itd-cookies-settings-description"><?php echo esc_html__( 'Choose the optional categories you allow.', 'itd-cookies' ); ?></p>
				<div class="itd-cookies__choices">
					<?php foreach ( ITD_Cookies_Services::categories() as $category_id => $category ) : ?>
						<?php $list = ITD_Cookies_Services::in_category( $services, $category_id ); ?>
						<div class="itd-cookies__category">
							<div class="itd-cookies__category-heading">
								<label for="itd-cookies-category-<?php echo esc_attr( $category_id ); ?>" class="itd-cookies__category-name"><?php echo esc_html( $category['name'] ); ?></label>
								<input class="itd-cookies__toggle" id="itd-cookies-category-<?php echo esc_attr( $category_id ); ?>" type="checkbox" role="switch" aria-describedby="itd-cookies-category-description-<?php echo esc_attr( $category_id ); ?>"
								<?php
								if ( 'necessary' === $category_id ) :
									?>
									checked disabled
									<?php
else :
	?>
									data-itd-cookies-category="<?php echo esc_attr( $category_id ); ?>" <?php disabled( ! $list ); ?><?php endif; ?>>
							</div>
							<p class="itd-cookies__category-description" id="itd-cookies-category-description-<?php echo esc_attr( $category_id ); ?>"><?php echo esc_html( $category['description'] ); ?>
								<?php
								if ( 'necessary' === $category_id ) :
									?>
									<span class="itd-cookies__status"><?php echo esc_html__( 'Always active', 'itd-cookies' ); ?></span>
									<?php
elseif ( ! $list ) :
	?>
									<span class="itd-cookies__status"><?php echo esc_html__( 'Not currently used', 'itd-cookies' ); ?></span><?php endif; ?>
							</p>
							<?php if ( $list ) : ?>
								<ul class="itd-cookies__services">
									<?php
									foreach ( $list as $service ) :
										?>
										<li><strong><?php echo esc_html( $service['name'] ); ?></strong><span><?php echo esc_html( $service['description'] ); ?></span></li><?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="itd-cookies__actions">
					<button type="button" class="itd-cookies__button" data-itd-cookies-save><?php echo esc_html__( 'Save choice', 'itd-cookies' ); ?></button>
					<button type="button" class="itd-cookies__button itd-cookies__button--secondary" data-itd-cookies-accept><?php echo esc_html__( 'Accept all', 'itd-cookies' ); ?></button>
					<button type="button" class="itd-cookies__button itd-cookies__button--secondary" data-itd-cookies-cancel><?php echo esc_html__( 'Back', 'itd-cookies' ); ?></button>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Render up to four configured legal links with the managed policy fallback.
	 *
	 * @param array $settings Sanitized settings.
	 * @return void
	 */
	private function render_legal_links( array $settings ) {
		$links = ITD_Cookies_Legal::links( $settings );
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
