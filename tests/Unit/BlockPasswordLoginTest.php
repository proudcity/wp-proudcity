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

	public function test_disable_core_handlers_removes_and_replaces_password_filters_when_not_allowed(): void {
		$removed                             = array();
		$GLOBALS['proud_test_added_filters'] = array();

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

		$this->assertSame(
			array(
				array( 'authenticate', 'proud_block_password_login_empty_credentials', 20, 3 ),
				array( 'authenticate', 'proud_block_password_login_final_guard', PHP_INT_MAX, 3 ),
			),
			$GLOBALS['proud_test_added_filters']
		);

		unset( $GLOBALS['proud_test_added_filters'] );
	}

	public function test_disable_core_handlers_is_a_passthrough_when_allowed(): void {
		$called                              = false;
		$GLOBALS['proud_test_added_filters'] = array();

		Functions\when( 'remove_filter' )->alias(
			function ( ...$args ) use ( &$called ) {
				$called = true;
				return true;
			}
		);

		putenv( 'PC_ALLOW_PASSWORD_LOGIN=1' );

		proud_block_password_login_maybe_disable_core_handlers();

		$this->assertFalse( $called );
		$this->assertSame( array(), $GLOBALS['proud_test_added_filters'] );

		unset( $GLOBALS['proud_test_added_filters'] );
		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );
	}

	public function test_empty_credentials_returns_both_codes_when_both_fields_are_empty(): void {
		$result = proud_block_password_login_empty_credentials( null, '', '' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( array( 'empty_username', 'empty_password' ), $result->get_error_codes() );
	}

	public function test_empty_credentials_returns_only_empty_username_when_just_that_is_empty(): void {
		$result = proud_block_password_login_empty_credentials( null, '', 'a-password' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( array( 'empty_username' ), $result->get_error_codes() );
	}

	public function test_empty_credentials_returns_only_empty_password_when_just_that_is_empty(): void {
		$result = proud_block_password_login_empty_credentials( null, 'admin', '' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( array( 'empty_password' ), $result->get_error_codes() );
	}

	public function test_empty_credentials_passes_through_a_real_attempt_untouched(): void {
		$this->assertNull( proud_block_password_login_empty_credentials( null, 'admin', 'a-real-password' ) );
	}

	public function test_empty_credentials_passes_through_an_existing_wp_user(): void {
		$user = new WP_User( 42 );

		$this->assertSame( $user, proud_block_password_login_empty_credentials( $user, '', '' ) );
	}

	public function test_empty_credentials_passes_through_an_existing_wp_error(): void {
		$error = new WP_Error( 'some_other_error', 'already decided' );

		$this->assertSame( $error, proud_block_password_login_empty_credentials( $error, '', '' ) );
	}

	public function test_final_guard_blocks_a_successful_password_result_when_not_allowed(): void {
		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );

		$user   = new WP_User( 1 );
		$result = proud_block_password_login_final_guard( $user, 'admin', 'a-real-password', false );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'authentication_failed', $result->get_error_code() );

		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );
	}

	public function test_final_guard_is_a_passthrough_when_allowed(): void {
		putenv( 'PC_ALLOW_PASSWORD_LOGIN=1' );

		$user = new WP_User( 1 );

		$this->assertSame( $user, proud_block_password_login_final_guard( $user, 'admin', 'a-real-password', false ) );

		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );
	}

	public function test_final_guard_leaves_cookie_reauthentication_alone(): void {
		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );

		// wp_authenticate_cookie() only ever returns a WP_User when both the
		// username and password were empty -- that's what distinguishes it here.
		$user = new WP_User( 1 );

		$this->assertSame( $user, proud_block_password_login_final_guard( $user, '', '', false ) );
	}

	public function test_final_guard_leaves_a_rest_application_password_result_alone(): void {
		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );

		$user = new WP_User( 1 );

		$this->assertSame(
			$user,
			proud_block_password_login_final_guard( $user, 'admin', 'xxxx xxxx xxxx xxxx xxxx xxxx', true )
		);
	}

	public function test_final_guard_passes_through_a_non_user_result(): void {
		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );

		$this->assertNull( proud_block_password_login_final_guard( null, 'admin', 'a-real-password' ) );
	}

	public function test_log_attempt_logs_on_an_authentication_failed_error_when_not_allowed(): void {
		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );

		$_SERVER['REMOTE_ADDR'] = '203.0.113.5';

		$result = proud_block_password_login_log_attempt( 'admin', new WP_Error( 'authentication_failed', 'nope' ) );

		$this->assertNull( $result );

		unset( $_SERVER['REMOTE_ADDR'] );
	}

	public function test_log_attempt_ignores_other_error_codes(): void {
		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );

		$_SERVER['REMOTE_ADDR'] = '203.0.113.5';

		// expired_session (an expired/missing login cookie on a reauth bounce) and the
		// empty_* codes are core behaviour unrelated to a blocked password attempt.
		$this->assertNull( proud_block_password_login_log_attempt( '', new WP_Error( 'expired_session', 'nope' ) ) );
		$this->assertNull( proud_block_password_login_log_attempt( '', new WP_Error( 'empty_username', 'nope' ) ) );
		$this->assertNull( proud_block_password_login_log_attempt( 'admin', null ) );

		unset( $_SERVER['REMOTE_ADDR'] );
	}

	public function test_log_attempt_is_a_passthrough_when_allowed(): void {
		putenv( 'PC_ALLOW_PASSWORD_LOGIN=1' );

		$_SERVER['REMOTE_ADDR'] = '203.0.113.5';

		$result = proud_block_password_login_log_attempt( 'admin', new WP_Error( 'authentication_failed', 'nope' ) );

		$this->assertNull( $result );

		unset( $_SERVER['REMOTE_ADDR'] );
		putenv( 'PC_ALLOW_PASSWORD_LOGIN' );
	}
}
