<?php
/**
 * Public adapter registration contract and diagnostic boundaries.
 *
 * @package ITD\Cookies\Tests
 */

namespace ITD\Cookies\Tests;

use Brain\Monkey\Functions;
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
