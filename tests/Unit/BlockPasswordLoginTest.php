<?php
/**
 * Unit tests for the proud-block-password-login mu-plugin (pc-dev-issues#363, finding M2).
 *
 * The mu-plugin itself is required once, by tests/bootstrap.php, so its functions
 * exist for every test here without re-declaring them.
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class BlockPasswordLoginTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_allowed_is_false_when_env_var_unset(): void {
		$this->assertFalse( proud_block_password_login_allowed( false ) );
	}

	public function test_allowed_is_false_for_any_non_matching_value(): void {
		$this->assertFalse( proud_block_password_login_allowed( 'true' ) );
		$this->assertFalse( proud_block_password_login_allowed( 'yes' ) );
		$this->assertFalse( proud_block_password_login_allowed( '0' ) );
		$this->assertFalse( proud_block_password_login_allowed( '' ) );
	}

	public function test_allowed_is_true_only_for_the_literal_string_one(): void {
		$this->assertTrue( proud_block_password_login_allowed( '1' ) );
	}

	public function test_disable_core_handlers_removes_both_password_filters_when_not_allowed(): void {
		$removed = array();

		Functions\when( 'remove_filter' )->alias(
			function ( $hook, $callback, $priority = 10 ) use ( &$removed ) {
				$removed[] = array( $hook, $callback, $priority );
				return true;
			}
		);

		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );

		proud_block_password_login_maybe_disable_core_handlers();

		$this->assertSame(
			array(
				array( 'authenticate', 'wp_authenticate_username_password', 20 ),
				array( 'authenticate', 'wp_authenticate_email_password', 20 ),
			),
			$removed
		);
	}

	public function test_disable_core_handlers_is_a_passthrough_when_allowed(): void {
		$called = false;

		Functions\when( 'remove_filter' )->alias(
			function ( ...$args ) use ( &$called ) {
				$called = true;
				return true;
			}
		);

		putenv( 'PC_ALLOW_PASSWORD_LOGIN=1' );

		proud_block_password_login_maybe_disable_core_handlers();

		$this->assertFalse( $called );

		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );
	}

	public function test_log_attempt_logs_the_remote_addr_when_not_allowed(): void {
		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );

		$_SERVER['REMOTE_ADDR'] = '203.0.113.5';

		$result = proud_block_password_login_log_attempt( 'admin' );

		$this->assertNull( $result );

		unset( $_SERVER['REMOTE_ADDR'] );
	}

	public function test_log_attempt_is_a_passthrough_when_allowed(): void {
		putenv( 'PC_ALLOW_PASSWORD_LOGIN=1' );

		$_SERVER['REMOTE_ADDR'] = '203.0.113.5';

		$result = proud_block_password_login_log_attempt( 'admin' );

		$this->assertNull( $result );

		unset( $_SERVER['REMOTE_ADDR'] );
		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );
	}
}
