<?php
/**
 * Public adapter registration contract and diagnostic boundaries.
 *
 * @package ITD\Cookies\Tests
 */

namespace ITD\Cookies\Tests;

use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;
use ReflectionProperty;

require_once dirname( __DIR__, 2 ) . '/includes/class-itd-cookies-services.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-itd-cookies-script-adapters.php';
require_once dirname( __DIR__, 2 ) . '/includes/functions-script-adapters.php';

/** Exercise the same global API used by third-party integrations. */
final class ScriptAdaptersTest extends TestCase {
	/**
	 * Reset request singleton; WordPress core is doubled only at its boundaries.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$registry = new ReflectionProperty( 'ITD_Cookies_Script_Adapters', 'instance' );
		$registry->setAccessible( true );
		$registry->setValue( null, null );
		Mockery::mock( 'overload:WP_Error' );
		Functions\when( 'did_action' )->justReturn( 0 );
		Functions\when( 'sanitize_text_field' )->alias( 'strip_tags' );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
	}

	/**
	 * All existing consent categories are accepted; necessary remains always on.
	 *
	 * @return void
	 */
	public function test_all_existing_categories_and_safe_diagnostics() {
		foreach ( array( 'necessary', 'functional', 'analytics', 'marketing' ) as $category ) {
			$this->assertTrue(
				itd_cookies_register_script_group(
					$category,
					array(
						'category'       => $category,
						'handles'        => array( 'script-' . $category ),
						'provider_types' => array( 'provider-' . $category ),
						'resources'      => array(),
						'label'          => 'Fixture service',
					)
				)
			);
		}
		$this->assertCount( 4, itd_cookies_get_script_group_diagnostics() );
		foreach ( itd_cookies_get_script_group_diagnostics() as $event ) {
			$this->assertSame( array( 'group_id', 'code' ), array_keys( $event ) );
			$this->assertSame( 'REGISTERED', $event['code'] );
		}
	}

	/** Stable identity ignores labels but changes with explicit revisions/handles.
	 *
	 * @return void
	 */
	public function test_script_group_consent_revision() {
		$args = array(
			'category'         => 'functional',
			'handles'          => array( 'chat-library', 'chat-ui' ),
			'provider_types'   => array( 'chat' ),
			'resources'        => array(),
			'consent_revision' => '2',
		);
		self::assertTrue( itd_cookies_register_script_group( 'chat', $args ) );
		$registry = \ITD_Cookies_Script_Adapters::instance();
		$first    = $registry->services( array() )[0];
		self::assertSame( 'functional', $first['category'] );
		self::assertSame( hash( 'sha256', json_encode( array( $args['handles'], $args['provider_types'], '2' ) ) ), $first['consent_revision'] );
		self::assertNotSame( hash( 'sha256', json_encode( array( $args['handles'], $args['provider_types'], '1' ) ) ), $first['consent_revision'] );
		self::assertNotSame(
			true,
			itd_cookies_register_script_group(
				'bad-revision',
				array_merge(
					$args,
					array(
						'handles'          => array( 'other' ),
						'consent_revision' => array( 'unsafe' ),
					)
				)
			)
		);
	}

	/** Block-theme policy declarations happen once, without prematurely freezing.
	 *
	 * @return void
	 */
	public function test_early_policy_registration_does_not_freeze_or_repeat() {
		Functions\when( 'is_admin' )->justReturn( false );
		Actions\expectDone( 'itd_cookies_register_script_groups' )->once();
		self::assertTrue(
			itd_cookies_register_script_group(
				'early-chat',
				array(
					'category'       => 'functional',
					'handles'        => array( 'early-chat-sdk' ),
					'provider_types' => array( 'early-chat' ),
					'resources'      => array(),
				)
			)
		);
		$registry = \ITD_Cookies_Script_Adapters::instance();
		$registry->register_groups();
		$registry->register_groups();
		self::assertCount( 1, $registry->services( array() ) );
		self::assertTrue(
			itd_cookies_register_script_group(
				'later-chat',
				array(
					'category'       => 'functional',
					'handles'        => array( 'later-chat-sdk' ),
					'provider_types' => array( 'later-chat' ),
					'resources'      => array(),
				)
			)
		);
	}

	/**
	 * Invalid declarations cannot supply executable code, URLs or ambiguous IDs.
	 *
	 * @dataProvider invalid_declarations
	 * @param string $id Candidate group ID.
	 * @param array  $changes Changed arguments.
	 * @return void
	 */
	public function test_invalid_declaration( $id, array $changes ) {
		$args = array_merge(
			array(
				'category'       => 'analytics',
				'handles'        => array( 'fixture-sdk' ),
				'provider_types' => array( 'fixture-provider' ),
				'resources'      => array(),
			),
			$changes
		);
		$this->assertNotSame( true, itd_cookies_register_script_group( $id, $args ) );
	}

	/**
	 * Negative lexical and structural API inputs.
	 *
	 * @return array
	 */
	public function invalid_declarations() {
		return array(
			array( '', array() ),
			array( 'UPPERCASE', array() ),
			array( 'https://example.test/sdk.js', array() ),
			array( str_repeat( 'a', 65 ), array() ),
			array( 'fixture', array( 'category' => 'unknown' ) ),
			array( 'fixture', array( 'category' => array( 'analytics' ) ) ),
			array( 'fixture', array( 'handles' => array() ) ),
			array( 'fixture', array( 'handles' => array( 'duplicate', 'duplicate' ) ) ),
			array( 'fixture', array( 'handles' => array( 'https://example.test/script.js' ) ) ),
			array(
				'fixture',
				array(
					'handles' => array(
						0 => 'valid',
						2 => 'invalid-list',
					),
				),
			),
			array( 'fixture', array( 'provider_types' => array() ) ),
			array( 'fixture', array( 'provider_types' => array( 'provider', 'provider' ) ) ),
			array( 'fixture', array( 'resources' => 'pixel' ) ),
			array( 'fixture', array( 'javascript' => 'alert(1)' ) ),
			array( 'fixture', array( 'url' => 'https://example.test/script.js' ) ),
			array( 'fixture', array( 'label' => array( 'not-text' ) ) ),
			array( 'fixture', array( 'handles' => array( 'itd-cookies-consent' ) ) ),
		);
	}

	/**
	 * Explicit tracking fallback cannot be silently accepted as scripts-only.
	 *
	 * @return void
	 */
	public function test_ancillary_late_and_overlap_diagnostics() {
		$args = array(
			'category'       => 'analytics',
			'handles'        => array( 'fixture-sdk' ),
			'provider_types' => array( 'fixture-provider' ),
			'resources'      => array( array( 'type' => 'pixel' ) ),
		);
		$this->assertNotSame( true, itd_cookies_register_script_group( 'fixture', $args ) );
		$args['resources'] = array();
		$this->assertNotSame( true, itd_cookies_register_script_group( 'overlap', $args ) );
		Functions\when( 'did_action' )->justReturn( 1 );
		$this->assertNotSame( true, itd_cookies_register_script_group( 'late', $args ) );
		$this->assertSame( array( 'UNSUPPORTED_ANCILLARY_RESOURCES', 'FAILED_OWNERSHIP_CONFLICT', 'LATE_REGISTRATION' ), array_column( itd_cookies_get_script_group_diagnostics(), 'code' ) );
	}
}
