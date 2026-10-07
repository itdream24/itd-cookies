<?php
/**
 * Plugin Name: ITD Cookies Limited Adapters Reference Fixture
 * Description: Local-only developer reference and acceptance counters. Never ship.
 * Version: 1.0.0
 * License: GPL-2.0-or-later
 */
if ( ! defined( 'ABSPATH' ) || 'itd-cookies.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	return;
}

function itd_adapter_fixture_mode() {
	$mode = isset( $_GET['itd_adapter_fixture'] ) ? sanitize_key( wp_unslash( $_GET['itd_adapter_fixture'] ) ) : '';
	return in_array( $mode, array( 'zero', 'supported', 'missing-handle', 'missing-dependency', 'cycle', '404', 'timeout', 'ownership', 'late', 'ancillary', 'unprinted', 'nonce', 'hash' ), true ) ? $mode : '';
}
function itd_adapter_fixture_case() {
	return isset( $_GET['itd_case'] ) ? substr( sanitize_key( wp_unslash( $_GET['itd_case'] ) ), 0, 40 ) : 'default';
}
add_action( 'init', static function () {
	if ( isset( $_GET['itd_adapter_counts'] ) ) {
		nocache_headers();
		$counts = array();
        foreach (array('library','dependent','marketing','missing','slow') as $name) {
            $count = (int) get_option('itd_adapter_fixture_count_' . itd_adapter_fixture_case() . '_' . $name, 0);
            if ($count) $counts[$name] = $count;
        }
        wp_send_json($counts);
	}
	if ( ! isset( $_GET['itd_adapter_sdk'] ) ) return;
	$name = sanitize_key( wp_unslash( $_GET['itd_adapter_sdk'] ) );
	if ( ! in_array( $name, array( 'library', 'dependent', 'marketing', 'missing', 'slow' ), true ) ) return;
	$case = itd_adapter_fixture_case();
    global $wpdb;
    $key = 'itd_adapter_fixture_count_' . $case . '_' . $name;
    $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'no') ON DUPLICATE KEY UPDATE option_value = CAST(option_value AS UNSIGNED) + 1", $key));
    wp_cache_delete($key, 'options');
	nocache_headers();
	header( 'Content-Type: application/javascript; charset=UTF-8' );
	if ( 'missing' === $name ) { status_header( 404 ); exit; }
	if ( 'slow' === $name ) { usleep( 7000000 ); }
	echo 'window.itdFixtureTrace = window.itdFixtureTrace || []; window.itdFixtureTrace.push(' . wp_json_encode( $name ) . ');';
	exit;
}, 0 );

add_action( 'wp_enqueue_scripts', static function () {
	$mode = itd_adapter_fixture_mode();
	if ( ! $mode ) return;
	$base = plugin_dir_url( __FILE__ );
	wp_enqueue_script( 'itd-fixture-application', $base . 'application.js', array( 'jquery' ), '4', false );
	wp_add_inline_script( 'itd-fixture-application', 'window.itdFixtureNeighbor = (window.itdFixtureNeighbor || 0) + 1;' );
	wp_enqueue_script( 'itd-fixture-unknown-cdn', 'https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.11.13/dayjs.min.js', array(), '1', false );
	if ( 'zero' === $mode || ! function_exists( 'itd_cookies_register_script_group' ) ) return;
	$case = itd_adapter_fixture_case();
	$source = static function ( $name ) use ( $case ) {
		return add_query_arg( array( 'itd_adapter_sdk' => $name, 'itd_case' => $case ), home_url( '/' ) );
	};
	$deps = 'cycle' === $mode ? array( 'itd-fixture-dependent' ) : array();
	wp_register_script( 'itd-fixture-library', $source( '404' === $mode ? 'missing' : ( 'timeout' === $mode ? 'slow' : 'library' ) ), $deps, '1', false );
	wp_register_script( 'itd-fixture-dependent', $source( 'dependent' ), array( 'missing-dependency' === $mode ? 'itd-fixture-absent-dependency' : 'itd-fixture-library' ), '1', true );
	wp_register_script( 'itd-fixture-marketing', $source( 'marketing' ), array(), '1', true );
	if ( 'unprinted' !== $mode ) wp_enqueue_script( 'itd-fixture-dependent' );
	wp_enqueue_script( 'itd-fixture-marketing' );
	wp_localize_script( 'itd-fixture-library', 'ITDFixtureData', array( 'value' => 'Localized <&> fixture' ) );
	wp_add_inline_script( 'itd-fixture-library', "window.itdFixtureTrace.push('before:' + ITDFixtureData.value);\n// Preserve this exact known body.", 'before' );
	wp_add_inline_script( 'itd-fixture-library', "window.itdFixtureTrace.push('after');", 'after' );
	wp_add_inline_script( 'itd-fixture-dependent', "window.itdFixtureTrace.push('dependent-init');", 'after' );
	wp_add_inline_script( 'itd-fixture-marketing', "window.itdFixtureTrace.push('marketing-init');", 'after' );
}, 10 );

function itd_adapter_fixture_register() {
	$mode = itd_adapter_fixture_mode();
	if ( ! $mode || 'zero' === $mode || ! function_exists( 'itd_cookies_register_script_group' ) ) return;
	$handles = array( 'itd-fixture-library', 'itd-fixture-dependent' );
	if ( 'missing-handle' === $mode ) $handles[] = 'itd-fixture-absent';
	$result = itd_cookies_register_script_group( 'fixture-analytics', array(
		'category' => 'analytics', 'handles' => $handles,
		'provider_types' => array( 'ownership' === $mode ? 'ga4' : 'fixture-statistics' ),
		'resources' => 'ancillary' === $mode ? array( array( 'type' => 'preload' ) ) : array(),
		'label' => 'Локальная тестовая статистика',
	) );
	// Real integrations should stop their own ancillary output on WP_Error.
	$GLOBALS['itd_adapter_fixture_registration'] = is_wp_error( $result ) ? $result->get_error_code() : 'REGISTERED';
	itd_cookies_register_script_group( 'fixture-marketing', array(
		'category' => 'marketing', 'handles' => array( 'itd-fixture-marketing' ),
		'provider_types' => array( 'fixture-advertising' ), 'resources' => array(),
		'label' => 'Локальный тестовый маркетинг',
	) );
}
add_action( 'itd_cookies_register_script_groups', static function () {
	if ( 'late' !== itd_adapter_fixture_mode() ) itd_adapter_fixture_register();
} );
add_action( 'wp_footer', static function () {
	if ( 'late' === itd_adapter_fixture_mode() ) itd_adapter_fixture_register();
}, 15 );

add_action( 'wp_enqueue_scripts', static function () {
	$mode = itd_adapter_fixture_mode();
	if ( ! in_array( $mode, array( 'nonce', 'hash' ), true ) ) return;
	if ( 'nonce' === $mode ) {
		add_filter( 'wp_inline_script_attributes', static function ( $attributes ) { $attributes['nonce'] = 'itd-local-fixture-nonce'; return $attributes; } );
		header( "Content-Security-Policy: script-src 'self' https://cdnjs.cloudflare.com 'nonce-itd-local-fixture-nonce'; worker-src 'self' blob:; object-src 'none'" );
	} else {
		$hashes = array();
		$scripts = wp_scripts();
		$native_handles = array_merge($scripts->queue, array('wp-i18n','wp-hooks'));
        for ($index = 0; $index < count($native_handles); $index++) {
            $handle = $native_handles[$index];
            if (!isset($scripts->registered[$handle])) continue;
            foreach ($scripts->registered[$handle]->deps as $dependency) if (!in_array($dependency, $native_handles, true)) $native_handles[] = $dependency;
        }
        foreach (array_unique($native_handles) as $handle) {
            if (!isset($scripts->registered[$handle])) continue;
			ob_start(); $scripts->print_extra_script( $handle ); $tags = ob_get_clean();
			foreach ( array( 'before', 'after' ) as $position ) $tags .= $scripts->get_inline_script_tag( $handle, $position );
			if (method_exists($scripts, 'print_translations')) {
                $translation = $scripts->print_translations($handle, false);
                if ($translation) $tags .= wp_get_inline_script_tag($translation . "\n//# sourceURL=" . rawurlencode($handle . '-js-translations'));
            }
            preg_match_all( '~<script\b[^>]*>(.*?)</script>~s', $tags, $matches );
			foreach ( $matches[1] as $body ) $hashes[] = "'sha256-" . base64_encode( hash( 'sha256', $body, true ) ) . "'";
		}
        if (function_exists('wp_script_modules')) {
            ob_start(); wp_script_modules()->print_import_map(); $native_map = ob_get_clean();
            // Known public core renderer only, on a cloned native queue: do not mark the real wp-i18n handle done.
            if (method_exists(wp_script_modules(), 'print_script_module_translations')) {
                $original_native_queue = $GLOBALS['wp_scripts'];
                $GLOBALS['wp_scripts'] = clone $scripts;
                ob_start();
                try { wp_script_modules()->print_script_module_translations(); }
                finally { $native_map .= ob_get_clean(); $GLOBALS['wp_scripts'] = $original_native_queue; }
            }
            preg_match_all('~<script\b[^>]*>(.*?)</script>~s', $native_map, $map_bodies);
            foreach ($map_bodies[1] as $body) $hashes[] = "'sha256-" . base64_encode(hash('sha256', $body, true)) . "'";
        }
        // Allow the unchanged native WordPress 7.1 emoji module; it is not owned/replayed by adapters.
        $emoji_path = '/js/wp-emoji-loader' . wp_scripts_get_suffix() . '.js';
        if (is_file(ABSPATH . WPINC . $emoji_path)) {
            $emoji_body = rtrim(file_get_contents(ABSPATH . WPINC . $emoji_path)) . "\n//# sourceURL=" . esc_url_raw(includes_url($emoji_path));
            $emoji_tag = wp_get_inline_script_tag($emoji_body, array('type' => 'module'));
            preg_match('~<script\b[^>]*>(.*?)</script>~s', $emoji_tag, $emoji_match);
            $hashes[] = "'sha256-" . base64_encode(hash('sha256', $emoji_match[1], true)) . "'";
        }
		header( "Content-Security-Policy: script-src 'self' https://cdnjs.cloudflare.com " . implode( ' ', array_unique( $hashes ) ) . "; worker-src 'self' blob:; object-src 'none'" );
	}
// Hash the known native bodies before adapters freeze their attachments.
// Keep nonce registration late to exercise the native print-time renderer.
}, 'hash' === itd_adapter_fixture_mode() ? 998 : 1100 );

add_action( 'wp_footer', static function () {
	$mode = itd_adapter_fixture_mode();
	if ( ! $mode ) return;
	$state = array(
		'version' => ITD_COOKIES_VERSION,
		'settings_sha256' => hash( 'sha256', wp_json_encode( get_option( 'itd_cookies_settings' ) ) ),
		'marker' => get_option( 'itd_cookies_migration_version' ),
		'policy_page' => get_option( 'itd_cookies_policy_page_id' ),
		'schema' => ITD_Cookies_Consent::SCHEMA,
		'registration' => $GLOBALS['itd_adapter_fixture_registration'] ?? 'NONE',
		'php_diagnostics' => function_exists('itd_cookies_get_script_group_diagnostics') ? itd_cookies_get_script_group_diagnostics() : array(),
	);
	echo '<section id="itd-fixture" style="background:white;color:#111;padding:20px;overflow-wrap:anywhere"><h2>Local adapter acceptance</h2>';
	echo '<p>Mode: ' . esc_html( $mode ) . '</p><pre id="itd-fixture-baseline" style="white-space:pre-wrap">' . esc_html( wp_json_encode( $state ) ) . '</pre>';
	echo '<button id="itd-fixture-fresh">Clear test consent and reload</button> <button id="itd-fixture-repeat">Repeat consent event</button>';
	echo do_shortcode( '[itd_cookies_settings][itd_cookies_policy][itd_cookies_legal_links]' );
	echo '<pre id="itd-fixture-evidence" aria-live="polite" aria-busy="true" style="white-space:pre-wrap"></pre></section>';
}, 10001 );
