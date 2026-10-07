<?php
/**
 * Explicit, closed classic WordPress script groups. No document scanning.
 *
 * @package ITD_Cookies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Capture native script output before WordPress prints owned handles. */
final class ITD_Cookies_Script_Adapters {
	/**
	 * Request-local registry.
	 *
	 * @var self|null
	 */
	private static $instance;
	/**
	 * Registered groups.
	 *
	 * @var array
	 */
	private $groups = array();
	/**
	 * Explicit handle owners.
	 *
	 * @var array
	 */
	private $owners = array();
	/**
	 * Safe developer events, without URLs or script bodies.
	 *
	 * @var array
	 */
	private $diagnostics = array();
	/**
	 * Registration closes before native dependency traversal.
	 *
	 * @var bool
	 */
	private $prepared = false;
	/**
	 * Manifest has been emitted.
	 *
	 * @var bool
	 */
	private $emitted = false;

	/**
	 * Request-local instance, also available to early integration registration.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Attach frontend-only lifecycle hooks. Empty registry adds no assets/output.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'registration_hook' ), 999 );
		add_action( 'wp_print_scripts', array( $this, 'prepare' ), 0 );
		add_action( 'wp_print_footer_scripts', array( $this, 'prepare' ), 0 );
		add_filter( 'script_loader_tag', array( $this, 'restore_tag' ), PHP_INT_MIN, 2 );
		add_filter( 'script_loader_tag', array( $this, 'capture_tag' ), PHP_INT_MAX, 2 );
		add_filter( 'itd_cookies_services', array( $this, 'services' ) );
		add_action( 'wp_footer', array( $this, 'manifest' ), 10000 );
	}

	/**
	 * Dedicated integration hook after ordinary enqueues, before queue printing.
	 *
	 * @return void
	 */
	public function registration_hook() {
		if ( ! is_admin() ) {
			do_action( 'itd_cookies_register_script_groups' );
			// WP 5.2 queries dependencies for datepicker at priority 1000.
			// Quarantine owned cycles before that native recursive query, too.
			$this->prepare();
		}
	}

	/**
	 * Register only identifiers and existing WP handles, never code or URLs.
	 *
	 * @param string $id Group identifier.
	 * @param array  $args Category, handles, provider_types, resources and label.
	 * @return true|WP_Error
	 */
	public function add( $id, array $args ) {
		if ( ! self::identifier( $id ) ) {
			return new WP_Error( 'INVALID_GROUP', 'Invalid script group identifier.' );
		}
		if ( $this->prepared || did_action( 'wp_print_scripts' ) || did_action( 'wp_print_footer_scripts' ) ) {
			return $this->error( $id, 'LATE_REGISTRATION' );
		}
		if ( isset( $this->groups[ $id ] ) || count( $this->groups ) >= 32 || array_diff( array_keys( $args ), array( 'category', 'handles', 'provider_types', 'resources', 'label' ) ) ) {
			return $this->error( $id, 'INVALID_GROUP' );
		}
		if ( ! isset( $args['category'], $args['handles'], $args['provider_types'], $args['resources'] ) || ! is_string( $args['category'] ) || ! isset( ITD_Cookies_Services::categories()[ $args['category'] ] ) || ! self::identifiers( $args['handles'], 32 ) || ! self::identifiers( $args['provider_types'], 8 ) || ! is_array( $args['resources'] ) || ( isset( $args['label'] ) && ( ! is_string( $args['label'] ) || '' === trim( $args['label'] ) || strlen( $args['label'] ) > 160 ) ) ) {
			return $this->error( $id, 'INVALID_GROUP' );
		}
		if ( count( $this->owners ) + count( $args['handles'] ) > 256 ) {
			return $this->error( $id, 'INVALID_GROUP' );
		}
		foreach ( $args['handles'] as $handle ) {
			if ( isset( $this->owners[ $handle ] ) || 0 === strpos( $handle, 'itd-cookies-' ) ) {
				return $this->error( $id, 'FAILED_OWNERSHIP_CONFLICT' );
			}
		}
		$this->groups[ $id ] = array(
			'group_id'       => $id,
			'category'       => $args['category'],
			'handles'        => $args['handles'],
			'provider_types' => $args['provider_types'],
			'label'          => isset( $args['label'] ) ? sanitize_text_field( $args['label'] ) : '',
			'failure'        => '',
			'nodes'          => array(),
		);
		foreach ( $args['handles'] as $handle ) {
			$this->owners[ $handle ] = $id;
		}
		if ( $args['resources'] ) {
			$this->groups[ $id ]['failure'] = 'UNSUPPORTED_ANCILLARY_RESOURCES';
			return $this->error( $id, 'UNSUPPORTED_ANCILLARY_RESOURCES' );
		}
		$this->event( $id, 'REGISTERED' );
		return true;
	}

	/**
	 * Strict, bounded identifiers; no normalization that could change ownership.
	 *
	 * @param mixed $value Identifier.
	 * @return bool
	 */
	private static function identifier( $value ) {
		return is_string( $value ) && 1 === preg_match( '/\A[a-z][a-z0-9_.-]{0,63}\z/D', $value );
	}

	/**
	 * Nonempty lists of distinct identifiers only.
	 *
	 * @param mixed $values Candidate list.
	 * @param int   $limit Maximum size.
	 * @return bool
	 */
	private static function identifiers( $values, $limit ) {
		if ( ! is_array( $values ) || ! $values || count( $values ) > $limit || array_keys( $values ) !== range( 0, count( $values ) - 1 ) ) {
			return false;
		}
		foreach ( $values as $value ) {
			if ( ! self::identifier( $value ) ) {
				return false;
			}
		}
		return count( array_unique( $values ) ) === count( $values );
	}

	/**
	 * Validate ownership/closed DAG before WordPress recursively walks it.
	 *
	 * @return void
	 */
	public function prepare() {
		if ( is_admin() ) {
			return;
		}
		if ( $this->prepared ) {
			$this->check_frozen_queue();
			return;
		}
		$this->prepared = true;
		if ( ! $this->groups ) {
			return;
		}
		$scripts  = wp_scripts();
		$settings = ITD_Cookies_Settings::get();
		$native   = array_column( ITD_Cookies_Services::providers( $settings ), 'type' );
		$claimed  = array();
		foreach ( $this->groups as $id => &$group ) {
			$failure = $group['failure'];
			if ( ! $failure && ( array_intersect( $group['provider_types'], $native ) || array_intersect( $group['provider_types'], $claimed ) ) ) {
				$failure = 'FAILED_OWNERSHIP_CONFLICT';
			}
			if ( ! $failure && ( ! $settings['enabled'] || $scripts->do_concat ) ) {
				$failure = 'UNSUPPORTED_SCRIPT';
			}
			$graph = array();
			foreach ( $group['handles'] as $handle ) {
				if ( ! isset( $scripts->registered[ $handle ] ) ) {
					$failure = $failure ? $failure : 'MISSING_HANDLE';
					continue;
				}
				$item = $scripts->registered[ $handle ];
				if ( ! $item->src || ! empty( $item->extra['conditional'] ) || ! empty( $item->textdomain ) || in_array( $handle, $scripts->done, true ) ) {
					$failure = $failure ? $failure : 'UNSUPPORTED_SCRIPT';
				}
				$graph[ $handle ] = $item->deps;
				foreach ( $item->deps as $dep ) {
					if ( ! in_array( $dep, $group['handles'], true ) || ! isset( $scripts->registered[ $dep ] ) ) {
						$failure = $failure ? $failure : 'MISSING_DEPENDENCY';
					}
				}
			}
			// A group cannot own a prerequisite used by another registered handle.
			foreach ( $scripts->registered as $handle => $item ) {
				if ( ! in_array( $handle, $group['handles'], true ) && array_intersect( $item->deps, $group['handles'] ) ) {
					$failure = $failure ? $failure : 'UNSUPPORTED_OPEN_GROUP';
				}
			}
			if ( ! $failure && ! self::acyclic( $graph ) ) {
				$failure = 'CYCLE';
			}
			$group['failure'] = $failure;
			if ( $failure ) {
				$this->event( $id, $failure );
			} else {
				$claimed = array_merge( $claimed, $group['provider_types'] );
				$this->event( $id, 'WAITING_FOR_CONSENT' );
			}
			foreach ( $group['handles'] as $handle ) {
				if ( ! isset( $scripts->registered[ $handle ] ) ) {
					continue;
				}
				$attachments = array();
				foreach ( array( 'data', 'before', 'after' ) as $key ) {
					$attachments[ $key ] = $failure ? '' : $scripts->get_data( $handle, $key );
				}
				$group['nodes'][ $handle ] = array(
					'handle'      => $handle,
					'deps'        => $failure ? array() : $graph[ $handle ],
					'template'    => 'itd-cookies-script-' . $handle,
					'inline'      => array(),
					'attachments' => $attachments,
					'printed'     => false,
					'source'      => $scripts->registered[ $handle ]->src,
				);
				foreach ( array( 'data', 'before', 'after' ) as $key ) {
					$scripts->add_data( $handle, $key, '' );
				}
				if ( $failure ) {
					// Avoid core's recursive cycle/missing-dependency traversal entirely.
					wp_dequeue_script( $handle );
					$scripts->registered[ $handle ]->deps = array();
				}
			}
		}
		unset( $group );
		wp_enqueue_script( 'itd-cookies-script-adapters', ITD_COOKIES_URL . 'assets/js/script-adapters.js', array( 'itd-cookies-consent' ), ITD_COOKIES_VERSION, true );
	}

	/**
	 * Reject edits to owned footer handles after the registration boundary.
	 *
	 * @return void
	 */
	private function check_frozen_queue() {
		if ( ! $this->groups ) {
			return;
		}
		$scripts = wp_scripts();
		foreach ( $this->groups as $id => &$group ) {
			foreach ( $group['nodes'] as $handle => $node ) {
				if ( $node['printed'] || ! isset( $scripts->registered[ $handle ] ) ) {
					continue;
				}
				$item = $scripts->registered[ $handle ];
				if ( ! $group['failure'] && ( $item->src !== $node['source'] || $item->deps !== $node['deps'] || ! empty( $item->extra['data'] ) || ! empty( $item->extra['before'] ) || ! empty( $item->extra['after'] ) ) ) {
					$group['failure'] = 'UNSUPPORTED_QUEUE_MUTATION';
					$this->event( $id, $group['failure'] );
				}
				foreach ( array( 'data', 'before', 'after' ) as $key ) {
					$scripts->add_data( $handle, $key, '' );
				}
				if ( $group['failure'] ) {
					wp_dequeue_script( $handle );
					$item->deps = array();
				}
			}
		}
		unset( $group );
	}

	/**
	 * Descriptive entries let the existing consent UI select adapter categories.
	 *
	 * @param array $entries Existing service descriptions.
	 * @return array
	 */
	public function services( $entries ) {
		if ( ! is_array( $entries ) ) {
			return $entries;
		}
		foreach ( $this->groups as $id => $group ) {
			if ( ! $group['failure'] ) {
				$entries[] = array(
					'id'            => 'script-group-' . $id,
					'name'          => $group['label'] ? $group['label'] : __( 'Additional site service', 'itd-cookies' ),
					'category'      => $group['category'],
					'provider_type' => 'external',
					'enabled'       => true,
					'description'   => '',
				);
			}
		}
		return $entries;
	}

	/**
	 * Bounded graph walk; no global browser queue or HTML work.
	 *
	 * @param array $graph Closed dependencies.
	 * @return bool
	 */
	private static function acyclic( array $graph ) {
		$done = array();
		$size = count( $graph );
		while ( true ) {
			if ( count( $done ) >= $size ) {
				break;
			}
			$previous = count( $done );
			foreach ( $graph as $handle => $deps ) {
				if ( ! isset( $done[ $handle ] ) && ! array_diff( $deps, array_keys( $done ) ) ) {
					$done[ $handle ] = true;
				}
			}
			if ( count( $done ) === $previous ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Ask the installed core renderer for owned inline tags, including nonce.
	 * Small buffers capture only a known handle's native renderer, never a page.
	 *
	 * @param WP_Scripts $scripts Native queue.
	 * @param string     $handle Owned handle.
	 * @return array
	 */
	private function inline_tags( $scripts, $handle ) {
		ob_start();
		$scripts->print_extra_script( $handle );
		$data = ob_get_clean();
		$tags = array( 'data' => $data );
		foreach ( array( 'before', 'after' ) as $position ) {
			if ( method_exists( $scripts, 'get_inline_script_tag' ) ) {
				$tags[ $position ] = $scripts->get_inline_script_tag( $handle, $position );
			} else {
				ob_start();
				$scripts->print_inline_script( $handle, $position );
				$tags[ $position ] = ob_get_clean();
			}
		}
		return $tags;
	}

	/**
	 * Restore the native before/external/after bundle for other tag filters.
	 * Data/localization remains outside this filter, exactly as in WordPress.
	 *
	 * @param string $tag Native external tag.
	 * @param string $handle WordPress handle.
	 * @return string
	 */
	public function restore_tag( $tag, $handle ) {
		if ( ! isset( $this->owners[ $handle ] ) ) {
			return $tag;
		}
		$id = $this->owners[ $handle ];
		if ( $this->groups[ $id ]['failure'] || ! isset( $this->groups[ $id ]['nodes'][ $handle ] ) ) {
			return '';
		}
		$node = &$this->groups[ $id ]['nodes'][ $handle ];
		if ( ! $node['inline'] ) {
			$scripts = wp_scripts();
			// Render at native print time, after late nonce/attribute filters exist.
			// Only the known handle is restored, inside this synchronous call.
			try {
				foreach ( $node['attachments'] as $key => $value ) {
					$scripts->add_data( $handle, $key, $value );
				}
				$node['inline'] = $this->inline_tags( $scripts, $handle );
			} finally {
				foreach ( array( 'data', 'before', 'after' ) as $key ) {
					$scripts->add_data( $handle, $key, '' );
				}
			}
		}
		$inline = $node['inline'];
		return $inline['before'] . $tag . $inline['after'];
	}
	/**
	 * Retain WordPress's own script tags inside an inert HTML template.
	 * The browser validates only these owned template children before replay.
	 *
	 * @param string $tag Native script tag, after other attribute filters.
	 * @param string $handle WordPress handle.
	 * @return string
	 */
	public function capture_tag( $tag, $handle ) {
		if ( ! isset( $this->owners[ $handle ] ) ) {
			return $tag;
		}
		$id = $this->owners[ $handle ];
		if ( $this->groups[ $id ]['failure'] || ! isset( $this->groups[ $id ]['nodes'][ $handle ] ) ) {
			return '';
		}
		$node = &$this->groups[ $id ]['nodes'][ $handle ];
		if ( $node['printed'] ) {
			return '';
		}
		$node['printed'] = true;
		$inline          = $node['inline'];
		return '<template id="' . esc_attr( $node['template'] ) . '" data-itd-cookies-script>' . $inline['data'] . $tag . '</template>';
	}

	/**
	 * Publish only graph identifiers and safe diagnostics after head/footer print.
	 *
	 * @return void
	 */
	public function manifest() {
		if ( ! $this->groups || $this->emitted || is_admin() ) {
			return;
		}
		$this->emitted = true;
		$groups        = array();
		foreach ( $this->groups as $id => $group ) {
			if ( ! $group['failure'] ) {
				foreach ( $group['handles'] as $handle ) {
					if ( empty( $group['nodes'][ $handle ]['printed'] ) ) {
						$group['failure'] = 'UNSUPPORTED_UNPRINTED_HANDLE';
						$this->event( $id, $group['failure'] );
						break;
					}
				}
			}
			$nodes = array();
			foreach ( $group['nodes'] as $node ) {
				unset( $node['inline'], $node['attachments'], $node['printed'], $node['source'] );
				$nodes[] = $node;
			}
			$groups[] = array(
				'group_id' => $id,
				'category' => $group['category'],
				'failure'  => $group['failure'],
				'nodes'    => $nodes,
			);
		}
		$data = wp_json_encode(
			array(
				'groups'      => $groups,
				'diagnostics' => $this->diagnostics,
			),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_* makes this inert JSON safe in script raw text.
		echo '<script type="application/json" id="itd-cookies-script-groups">' . $data . '</script>';
	}

	/**
	 * Safe read-only event snapshot. No script URLs, source or settings.
	 *
	 * @return array
	 */
	public function diagnostics() {
		return $this->diagnostics;
	}

	/**
	 * Developer action shares only identifiers and state codes.
	 *
	 * @param string $id Group ID.
	 * @param string $code State code.
	 * @return void
	 */
	private function event( $id, $code ) {
		if ( count( $this->diagnostics ) >= 512 ) {
			return;
		}
		$event               = array(
			'group_id' => $id,
			'code'     => $code,
		);
		$this->diagnostics[] = $event;
		do_action( 'itd_cookies_script_group_diagnostic', $event );
	}

	/**
	 * Return an integration error and emit a safe diagnostic.
	 *
	 * @param string $id Group ID.
	 * @param string $code Error code.
	 * @return WP_Error
	 */
	private function error( $id, $code ) {
		$this->event( $id, $code );
		return new WP_Error( $code, 'Script group registration failed: ' . $code );
	}
}
