<?php
/**
 * Shared PHPUnit case.
 *
 * @package ITD\Cookies\Tests
 */

namespace ITD\Cookies\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

/**
 * Resets Brain Monkey around every test.
 */
abstract class TestCase extends PhpUnitTestCase {
	/**
	 * Set up WordPress function doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'sanitize_key' )->alias(
			static function ( $value ) {
				$value = strtolower( (string) $value );

				return preg_replace( '/[^a-z0-9_\-]/', '', $value );
			}
		);
	}

	/**
	 * Tear down WordPress function doubles.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
