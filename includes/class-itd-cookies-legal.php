<?php
/**
 * Cookie policy and isolated legal-link integration.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Manages only the Cookie Policy page; other documents are existing URLs. */
final class ITD_Cookies_Legal {
	const PAGE_OPTION = 'itd_cookies_policy_page_id';

	/**
	 * Register shortcodes, an optional footer and an explicit admin action.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'itd_cookies_policy', array( $this, 'policy_shortcode' ) );
		add_shortcode( 'itd_cookies_legal_links', array( $this, 'links_shortcode' ) );
		add_action( 'wp_footer', array( $this, 'render_footer' ), 6 );
		add_action( 'admin_post_itd_cookies_create_policy', array( $this, 'create_page' ) );
	}

	/**
	 * Find a managed page without restoring or rewriting a deleted page.
	 *
	 * @return int
	 */
	public static function page_id() {
		$id   = absint( get_option( self::PAGE_OPTION, 0 ) );
		$page = $id ? get_post( $id ) : null;
		return $page && 'page' === $page->post_type && ! in_array( $page->post_status, array( 'trash', 'auto-draft' ), true ) ? $id : 0;
	}

	/**
	 * Return explicit URLs, with a public managed policy as the third fallback.
	 *
	 * @param array $settings Sanitized settings.
	 * @return array
	 */
	public static function links( array $settings ) {
		$links = array();
		for ( $index = 1; $index <= 4; $index++ ) {
			$url = $settings[ 'link_' . $index . '_url' ];
			if ( 3 === $index && '' === $url ) {
				$id = self::page_id();
				if ( $id && 'publish' === get_post_status( $id ) ) {
					$url = get_permalink( $id );
				}
			}
			if ( ! $url ) {
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
		return $links;
	}

	/**
	 * Return a link-styled, keyboard accessible settings button.
	 *
	 * @return string
	 */
	public static function opener() {
		return ITD_Cookies_Settings::get()['enabled'] ? '<button type="button" class="itd-cookies-open" data-itd-cookies-open>' . esc_html__( 'Cookie settings', 'itd-cookies' ) . '</button>' : '';
	}

	/**
	 * Produce only configured legal links and the available settings action.
	 *
	 * @return string
	 */
	public function links_shortcode() {
		$items = array();
		foreach ( self::links( ITD_Cookies_Settings::get() ) as $link ) {
			$items[] = '<a class="itd-cookies-legal__link" href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a>';
		}
		$opener = self::opener();
		if ( '' !== $opener ) {
			$items[] = $opener;
		}
		return $items ? '<nav class="itd-cookies-legal" aria-label="' . esc_attr__( 'Legal information', 'itd-cookies' ) . '">' . implode( '', $items ) . '</nav>' : '';
	}

	/**
	 * Print an isolated container only after explicit opt-in.
	 *
	 * @return void
	 */
	public function render_footer() {
		if ( ! is_admin() && ITD_Cookies_Settings::get()['auto_footer'] ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Complete HTML is escaped in links_shortcode.
			echo '<div class="itd-cookies-footer">' . $this->links_shortcode() . '</div>';
		}
	}

	/**
	 * Dynamic factual plugin configuration, without invented provider cookies.
	 *
	 * @return string
	 */
	public function policy_shortcode() {
		$settings = ITD_Cookies_Settings::get();
		$services = ITD_Cookies_Services::get( $settings );
		$html     = '<section class="itd-cookies-policy"><h2>' . esc_html__( 'About cookies', 'itd-cookies' ) . '</h2><p>' . esc_html__( 'Cookies are small pieces of information stored in your browser. This plugin stores your privacy choice.', 'itd-cookies' ) . '</p>';
		foreach ( ITD_Cookies_Services::categories() as $id => $category ) {
			$html .= '<h3>' . esc_html( $category['name'] ) . '</h3><p>' . esc_html( $category['description'] ) . '</p>';
			$list  = ITD_Cookies_Services::in_category( $services, $id );
			if ( $list ) {
				$html .= '<ul>';
				foreach ( $list as $service ) {
					$html .= '<li><strong>' . esc_html( $service['name'] ) . '</strong> — ' . esc_html( $service['description'] ) . '</li>';
				}
				$html .= '</ul>';
			} elseif ( 'necessary' !== $id ) {
				$html .= '<p>' . esc_html__( 'Not currently used', 'itd-cookies' ) . '</p>';
			}
		}
		$html .= '<p>' . esc_html__( 'When consent is enabled, this plugin loads analytics services only after you allow the Analytics category. Revoking that choice saves your decision and reloads the page without those providers.', 'itd-cookies' ) . '</p>';
		if ( ! $settings['enabled'] ) {
			$html .= '<p>' . esc_html__( 'Consent collection and analytics loading by this plugin are currently disabled.', 'itd-cookies' ) . '</p>';
		}
		// translators: %d is the configured lifetime of the consent choice in days.
		$html .= '<p>' . esc_html( sprintf( __( 'Your consent choice is stored for %d days, unless you change it, clear browser cookies or the site changes its consent version.', 'itd-cookies' ), $settings['consent_days'] ) ) . '</p>';
		$html .= '<p>' . esc_html__( 'You can change your choice using Cookie settings.', 'itd-cookies' ) . '</p>' . self::opener() . '</section>';
		return $html;
	}

	/**
	 * Create once on an explicit authorized action; never edit existing content.
	 *
	 * @return void
	 */
	public function create_page() {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'publish_pages' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'itd-cookies' ) );
		}
		check_admin_referer( 'itd_cookies_create_policy' );
		$id = self::ensure_page();
		if ( is_wp_error( $id ) ) {
			wp_die( esc_html__( 'The Cookie Policy page could not be created.', 'itd-cookies' ) );
		}
		wp_safe_redirect( admin_url( 'options-general.php?page=itd-cookies' ) );
		exit;
	}

	/**
	 * Create a managed page once. The admin handler checks capability and nonce.
	 * Existing pages, including drafts, retain their content and status.
	 *
	 * @return int|WP_Error
	 */
	public static function ensure_page() {
		$id = self::page_id();
		if ( ! $id ) {
			$id = wp_insert_post(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post_title'     => __( 'Cookie policy', 'itd-cookies' ),
					'post_content'   => '[itd_cookies_policy]',
					'comment_status' => 'closed',
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				return $id;
			}
			update_option( self::PAGE_OPTION, $id, false );
		}
		return $id;
	}
}
