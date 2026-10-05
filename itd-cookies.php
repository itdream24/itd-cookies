<?php
/**
 * Plugin Name: ITD Cookies
 * Description: Cookie choices and consent-aware Yandex Metrika and Google Analytics.
 * Version: 0.2.0-dev.1
 * Requires at least: 5.2
 * Requires PHP: 7.4
 * Plugin URI: https://github.com/itdream24/itd-cookies
 * Update URI: https://github.com/itdream24/itd-cookies
 * Author: ITD
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: itd-cookies
 * Domain Path: /languages
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ITD_COOKIES_VERSION', '0.2.0-dev.1' );
define( 'ITD_COOKIES_FILE', __FILE__ );
define( 'ITD_COOKIES_DIR', plugin_dir_path( __FILE__ ) );
define( 'ITD_COOKIES_URL', plugin_dir_url( __FILE__ ) );

// PHPStan runs on a newer runtime; this guard enforces the declared PHP 7.4 boundary.
// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
// @phpstan-ignore smaller.alwaysFalse
if ( PHP_VERSION_ID < 70400 ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'ITD Cookies requires PHP 7.4 or newer.', 'itd-cookies' ) . '</p></div>';
		}
	);
	return;
}

global $wp_version;
if ( version_compare( (string) $wp_version, '5.2', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'ITD Cookies requires WordPress 5.2 or newer.', 'itd-cookies' ) . '</p></div>';
		}
	);
	return;
}

require_once ITD_COOKIES_DIR . 'admin/class-itd-cookies-settings.php';
require_once ITD_COOKIES_DIR . 'includes/class-itd-cookies-consent.php';
require_once ITD_COOKIES_DIR . 'includes/class-itd-cookies-services.php';
require_once ITD_COOKIES_DIR . 'includes/class-itd-cookies-legal.php';
require_once ITD_COOKIES_DIR . 'public/class-itd-cookies-plugin.php';
require_once ITD_COOKIES_DIR . 'includes/class-itd-cookies-updater.php';
( new ITD_Cookies_Updater( ITD_COOKIES_FILE, ITD_COOKIES_VERSION ) )->register();

register_activation_hook( ITD_COOKIES_FILE, array( 'ITD_Cookies_Settings', 'migrate_legacy' ) );

add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'itd-cookies', false, dirname( plugin_basename( ITD_COOKIES_FILE ) ) . '/languages' );
	},
	0
);

add_action(
	'plugins_loaded',
	static function () {
		( new ITD_Cookies_Plugin() )->register();
	}
);

/**
 * Return whether the current browser consent allows a category.
 * This is a preference signal, never an authorization decision.
 *
 * @param string $category Consent category.
 * @return bool
 */
function itd_cookies_allowed( $category ) {
	return ITD_Cookies_Consent::allowed( $category, ITD_Cookies_Settings::get() );
}
