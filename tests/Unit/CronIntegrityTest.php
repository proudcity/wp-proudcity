<?php
/**
 * Unit tests for the proud-cron-integrity mu-plugin (wp-proudcity#2961).
 *
 * The mu-plugin itself is required once, by tests/bootstrap.php, so its
 * functions exist for every test here without re-declaring them.
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Minimal stand-in for $wpdb, just enough to drive pre_option_cron's direct
 * SELECT and its fail-open checks.
 */
class ProudCronIntegrityWpdbStub {
	public $options      = 'wp_options';
	public $last_error   = '';
	public $return_value;
	public $return_values = array();
	public $get_var_calls = 0;

	public function prepare( $query, ...$args ) {
		return $query;
	}

	public function get_var( $query ) {
		$this->get_var_calls++;

		if ( ! empty( $this->return_values ) ) {
			return array_shift( $this->return_values );
		}

		return $this->return_value;
	}
}

final class CronIntegrityTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		proud_cron_integrity_armed( false );
	}

	protected function tearDown(): void {
		proud_cron_integrity_armed( false );
		Monkey\tearDown();
		parent::tearDown();
	}

	public static function armingFilterProvider(): array {
		return array(
			'pre_schedule_event'       => array( 'proud_cron_integrity_arm' ),
			'pre_unschedule_event'     => array( 'proud_cron_integrity_arm' ),
			'pre_reschedule_event'     => array( 'proud_cron_integrity_arm' ),
			'pre_clear_scheduled_hook' => array( 'proud_cron_integrity_arm' ),
			'pre_unschedule_hook'      => array( 'proud_cron_integrity_arm' ),
		);
	}

	#[DataProvider( 'armingFilterProvider' )]
	public function test_arm_callback_arms_and_returns_pre_unchanged( string $callback ): void {
		$this->assertFalse( proud_cron_integrity_armed() );

		$this->assertNull( $callback( null ) );
		$this->assertTrue( proud_cron_integrity_armed() );
		proud_cron_integrity_armed( false );

		// A non-null $pre means another plugin already short-circuited; it must survive untouched.
		$already_decided = new \stdClass();
		$this->assertSame( $already_decided, $callback( $already_decided ) );
		$this->assertTrue( proud_cron_integrity_armed() );
		proud_cron_integrity_armed( false );

		$this->assertFalse( $callback( false ) );
		$this->assertTrue( proud_cron_integrity_armed() );
	}

	public function test_pre_option_cron_passes_through_when_unarmed(): void {
		$this->assertFalse( proud_cron_integrity_armed() );
		$this->assertFalse( proud_cron_integrity_pre_option_cron( false ) );
		$this->assertFalse( proud_cron_integrity_armed() );
	}

	public function test_pre_option_cron_returns_fresh_db_value_when_armed(): void {
		proud_cron_integrity_armed( true );

		$cron_array = array(
			'version' => 2,
			12345     => array(
				'proud2961_b' => array(
					'abc' => array(
						'schedule' => false,
						'args'     => array(),
					),
				),
			),
		);

		global $wpdb;
		$wpdb               = new ProudCronIntegrityWpdbStub();
		$wpdb->return_value = serialize( $cron_array );

		$result = proud_cron_integrity_pre_option_cron( false );

		$this->assertSame( $cron_array, $result );
	}

	public function test_pre_option_cron_stays_armed_after_a_read(): void {
		proud_cron_integrity_armed( true );

		global $wpdb;
		$wpdb               = new ProudCronIntegrityWpdbStub();
		$wpdb->return_value = serialize( array( 'version' => 2 ) );

		proud_cron_integrity_pre_option_cron( false );

		// Only pre_update_option_cron disarms; a mutator may call get_option('cron')
		// more than once (its own _get_cron_array() read, then update_option()'s
		// $old_value read) and both must see a fresh value.
		$this->assertTrue( proud_cron_integrity_armed() );
	}

	public function test_pre_option_cron_returns_fresh_value_on_every_call_while_armed(): void {
		proud_cron_integrity_armed( true );

		$first  = array( 'version' => 2, 100 => array( 'hook_a' => array() ) );
		$second = array( 'version' => 2, 100 => array( 'hook_a' => array() ), 200 => array( 'hook_b' => array() ) );

		global $wpdb;
		$wpdb                = new ProudCronIntegrityWpdbStub();
		$wpdb->return_values = array( serialize( $first ), serialize( $second ) );

		// Simulates _get_cron_array()'s read, then update_option()'s $old_value read
		// for the SAME armed mutation: both must hit the database, not a cached copy.
		$result_one = proud_cron_integrity_pre_option_cron( false );
		$result_two = proud_cron_integrity_pre_option_cron( false );

		$this->assertSame( $first, $result_one );
		$this->assertSame( $second, $result_two );
		$this->assertSame( 2, $wpdb->get_var_calls );
	}

	public function test_pre_update_option_cron_disarms_and_returns_value_unchanged(): void {
		proud_cron_integrity_armed( true );

		$value = array( 'version' => 2, 100 => array( 'hook_a' => array() ) );

		$this->assertSame( $value, proud_cron_integrity_disarm( $value ) );
		$this->assertFalse( proud_cron_integrity_armed() );
	}

	public function test_pre_option_cron_passes_through_after_disarm(): void {
		proud_cron_integrity_armed( true );
		proud_cron_integrity_disarm( array() );

		$this->assertFalse( proud_cron_integrity_armed() );
		$this->assertFalse( proud_cron_integrity_pre_option_cron( false ) );
	}

	public function test_pre_option_cron_fails_open_on_null(): void {
		proud_cron_integrity_armed( true );

		global $wpdb;
		$wpdb               = new ProudCronIntegrityWpdbStub();
		$wpdb->return_value = null;

		$this->assertFalse( proud_cron_integrity_pre_option_cron( false ) );
		// Failing open on a bad read does not disarm; the mutation is still in
		// flight and the next read (or pre_update_option_cron) must still see it.
		$this->assertTrue( proud_cron_integrity_armed() );
	}

	public function test_pre_option_cron_fails_open_on_empty_string(): void {
		proud_cron_integrity_armed( true );

		global $wpdb;
		$wpdb               = new ProudCronIntegrityWpdbStub();
		$wpdb->return_value = '';

		$this->assertFalse( proud_cron_integrity_pre_option_cron( false ) );
		$this->assertTrue( proud_cron_integrity_armed() );
	}

	public function test_pre_option_cron_fails_open_on_db_error(): void {
		proud_cron_integrity_armed( true );

		global $wpdb;
		$wpdb               = new ProudCronIntegrityWpdbStub();
		$wpdb->return_value = serialize( array( 'version' => 2 ) );
		$wpdb->last_error   = 'MySQL server has gone away';

		$this->assertFalse( proud_cron_integrity_pre_option_cron( false ) );
		$this->assertTrue( proud_cron_integrity_armed() );
	}

	public function test_guard_blocks_only_when_disabled_doing_cron_and_not_cli(): void {
		$this->assertTrue( proud_cron_integrity_should_block_wp_cron( false, true, true ) );
	}

	public function test_guard_never_blocks_for_wp_cli(): void {
		$this->assertFalse( proud_cron_integrity_should_block_wp_cron( true, true, true ) );
	}

	public function test_guard_never_blocks_when_disable_wp_cron_false(): void {
		$this->assertFalse( proud_cron_integrity_should_block_wp_cron( false, false, true ) );
	}

	public function test_guard_never_blocks_when_disable_wp_cron_undefined(): void {
		// Passing null falls back to the real DISABLE_WP_CRON constant, which is
		// undefined in this process, so the guard must not block.
		$this->assertFalse( proud_cron_integrity_should_block_wp_cron( false, null, true ) );
	}

	public function test_guard_never_blocks_when_not_doing_cron(): void {
		$this->assertFalse( proud_cron_integrity_should_block_wp_cron( false, true, false ) );
	}

	public function test_guard_default_doing_cron_path_delegates_to_wp_doing_cron(): void {
		Functions\when( 'wp_doing_cron' )->justReturn( true );
		$this->assertTrue( proud_cron_integrity_should_block_wp_cron( false, true ) );

		Functions\when( 'wp_doing_cron' )->justReturn( false );
		$this->assertFalse( proud_cron_integrity_should_block_wp_cron( false, true ) );
	}
}
