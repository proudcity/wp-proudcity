<?php
/**
 * Unit tests for the proud-block-anon-post mu-plugin (wp-proudcity#2998).
 *
 * The mu-plugin itself is required once, by tests/bootstrap.php.
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class BlockAnonPostTest extends TestCase {

	private const ABS = '/app/wordpress/';

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		putenv( 'PC_ALLOW_ANON_POST' );
		putenv( 'PC_ANON_POST_MODE' );
	}

	protected function tearDown(): void {
		putenv( 'PC_ALLOW_ANON_POST' );
		putenv( 'PC_ANON_POST_MODE' );
		unset( $GLOBALS['proud_test_added_actions'] );
		$_SERVER = array_diff_key( $_SERVER, array_flip( array( 'REQUEST_METHOD', 'SCRIPT_FILENAME', 'REQUEST_URI', 'HTTP_HOST', 'REMOTE_ADDR', 'HTTP_USER_AGENT' ) ) );
		$_GET    = array();
		$_POST   = array();
		$_COOKIE = array();
		Monkey\tearDown();
		parent::tearDown();
	}

	private function server( array $over = array() ): array {
		return array_merge(
			array(
				'REQUEST_METHOD'  => 'POST',
				'SCRIPT_FILENAME' => self::ABS . 'index.php',
				'REQUEST_URI'     => '/library/',
				'HTTP_HOST'       => 'Example.GOV',
				'REMOTE_ADDR'     => '203.0.113.7',
				'HTTP_USER_AGENT' => 'Mozilla/5.0',
			),
			$over
		);
	}

	private function decide( array $server = array(), array $get = array(), array $post = array(), array $cookie = array(), string $slug = '' ): string {
		return proud_anon_post_decide( $this->server( $server ), $get, $post, $cookie, self::ABS, $slug, 'apache2handler' );
	}

	public function test_disabled_only_for_literal_one(): void {
		$this->assertTrue( proud_anon_post_disabled( '1' ) );
		foreach ( array( false, '', '0', 'true', 'yes' ) as $v ) {
			$this->assertFalse( proud_anon_post_disabled( $v ) );
		}
	}

	public function test_mode_defaults_to_log_and_honors_exact_values(): void {
		$this->assertSame( 'log', proud_anon_post_mode( false ) );
		$this->assertSame( 'log', proud_anon_post_mode( 'ENFORCE' ) );
		$this->assertSame( 'log', proud_anon_post_mode( 'bogus' ) );
		$this->assertSame( 'log', proud_anon_post_mode( 'log' ) );
		$this->assertSame( 'enforce', proud_anon_post_mode( 'enforce' ) );
		$this->assertSame( 'log', PROUD_ANON_POST_DEFAULT_MODE );
	}

	public function test_non_post_and_cli_always_allowed(): void {
		foreach ( array( 'GET', 'HEAD', 'OPTIONS' ) as $m ) {
			$this->assertStringStartsWith( 'allow', $this->decide( array( 'REQUEST_METHOD' => $m ) ) );
		}
		$this->assertStringStartsWith( 'allow', proud_anon_post_decide( $this->server(), array(), array(), array(), self::ABS, '', 'cli' ) );
	}

	public function test_anonymous_plain_post_is_blocked(): void {
		$this->assertSame( 'block:no_cookie', $this->decide( array(), array(), array( 'log' => 'x' ) ) );
	}

	public function test_script_allowlist(): void {
		$this->assertSame( 'allow:wp_login', $this->decide( array( 'SCRIPT_FILENAME' => self::ABS . 'wp-login.php' ) ) );
		$this->assertSame( 'allow:admin_ajax', $this->decide( array( 'SCRIPT_FILENAME' => self::ABS . 'wp-admin/admin-ajax.php' ) ) );
		$this->assertSame( 'allow:wp_cron', $this->decide( array( 'SCRIPT_FILENAME' => self::ABS . 'wp-cron.php' ) ) );
		$this->assertSame( 'allow:comments_post', $this->decide( array( 'SCRIPT_FILENAME' => self::ABS . 'wp-comments-post.php' ) ) );
	}

	public function test_script_lookalikes_are_blocked(): void {
		$this->assertSame( 'block:no_cookie', $this->decide( array( 'REQUEST_URI' => '/library/wp-login.php' ) ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array( 'REQUEST_URI' => '/wp-admin/admin-ajax.php' ) ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array( 'SCRIPT_FILENAME' => '/elsewhere/wp-login.php' ) ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array( 'SCRIPT_FILENAME' => self::ABS . 'wp-admin/post.php' ) ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array( 'SCRIPT_FILENAME' => self::ABS . 'xmlrpc.php' ) ) );
	}

	public function test_rest_allowed_by_path_or_rest_route(): void {
		$this->assertSame( 'allow:rest', $this->decide( array( 'REQUEST_URI' => '/wp-json/gf/v2/forms' ) ) );
		$this->assertSame( 'allow:rest', $this->decide( array( 'REQUEST_URI' => '/wp-json' ) ) );
		$this->assertSame( 'allow:rest', $this->decide( array(), array( 'rest_route' => '/wp/v2/posts' ) ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array( 'REQUEST_URI' => '/library/?x=/wp-json/' ), array( 'x' => '/wp-json/' ) ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array( 'REQUEST_URI' => '/wp-jsonfoo' ) ) );
	}

	public function test_stripe_webhook_allowed(): void {
		$this->assertSame( 'allow:gf_stripe', $this->decide( array(), array( 'callback' => 'gravityformsstripe' ) ) );
		$this->assertSame( 'allow:gf_stripe', $this->decide( array(), array( 'stripe_callback' => '1' ) ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array(), array( 'callback' => 'gravityformsstripeX' ) ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array(), array( 'callback' => 'GravityFormsStripe' ) ) );
	}

	public function test_gf_upload_page_matches_option_slug_only(): void {
		$this->assertSame( 'allow:gf_upload', $this->decide( array(), array( 'gf_page' => 'abc123' ), array(), array(), 'abc123' ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array(), array( 'gf_page' => 'upload' ), array(), array(), 'abc123' ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array(), array( 'gf_page' => '' ), array(), array(), '' ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array(), array( 'gf_page' => array( 'abc123' ) ), array(), array(), 'abc123' ) );
	}

	public function test_mailoptin_allowed(): void {
		$this->assertSame( 'allow:mailoptin', $this->decide( array(), array( 'mailoptin-ajax' => 'track' ) ) );
	}

	public function test_post_field_allowlist(): void {
		$this->assertSame( 'allow:gf_submit', $this->decide( array(), array(), array( 'gform_submit' => '3' ) ) );
		$this->assertSame( 'allow:gf_resume_link', $this->decide( array(), array(), array( 'gform_send_resume_link' => 'x' ) ) );
		$this->assertSame( 'allow:search', $this->decide( array(), array(), array( 'search_term' => 'x' ) ) );
		$this->assertSame( 'allow:teaser_filter', $this->decide( array(), array(), array( 'form-proud-teaser-filter' => '1' ) ) );
		$this->assertSame( 'allow:translate', $this->decide( array(), array(), array( 'pct_translate_page' => '1' ) ) );
	}

	public function test_translate_requires_exactly_one(): void {
		$this->assertSame( 'block:no_cookie', $this->decide( array(), array(), array( 'pct_translate_page' => '0' ) ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array(), array(), array( 'pct_translate_page' => array( '1' ) ) ) );
	}

	public function test_cookie_presence_defers(): void {
		$this->assertSame( 'defer', $this->decide( array(), array(), array(), array( 'wordpress_logged_in_abc' => 'v' ) ) );
		$this->assertSame( 'block:no_cookie', $this->decide( array(), array(), array(), array( 'wordpress_test_cookie' => 'v', 'wordpress_sec_abc' => 'v' ) ) );
	}

	public function test_filter_can_allow(): void {
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value, $context = null ) {
				return 'proud_anon_post_allow' === $hook && '/library/' === $context['path'] && array( 'foo' ) === $context['fields'] ? true : $value;
			}
		);
		$this->assertSame( 'allow:filter', $this->decide( array(), array(), array( 'foo' => 'bar' ) ) );
	}

	public function test_filter_false_leaves_decision(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );
		$this->assertSame( 'block:no_cookie', $this->decide() );
	}

	public function test_check_early_does_nothing_when_disabled(): void {
		putenv( 'PC_ALLOW_ANON_POST=1' );
		$_SERVER = array_merge( $_SERVER, $this->server() );
		$logged  = array();
		proud_anon_post_check_early( 'apache2handler', array( 'log' => function ( $l ) use ( &$logged ) { $logged[] = $l; } ) );
		$this->assertSame( array(), $logged );
	}

	public function test_check_early_logs_only_in_log_mode(): void {
		$_SERVER = array_merge( $_SERVER, $this->server() );
		$_POST   = array( 'foo' => 'SECRETVALUE' );
		$logged  = array();
		$sent    = false;
		proud_anon_post_check_early(
			'apache2handler',
			array(
				'log'     => function ( $l ) use ( &$logged ) { $logged[] = $l; },
				'respond' => function () use ( &$sent ) { $sent = true; },
			)
		);
		$this->assertCount( 1, $logged );
		$this->assertStringStartsWith( 'proud-anon-post: would_block ', $logged[0] );
		$this->assertStringContainsString( 'reason=no_cookie', $logged[0] );
		$this->assertFalse( $sent );
	}

	public function test_check_early_enforce_mode_responds(): void {
		putenv( 'PC_ANON_POST_MODE=enforce' );
		$_SERVER = array_merge( $_SERVER, $this->server() );
		$logged  = array();
		$sent    = false;
		proud_anon_post_check_early(
			'apache2handler',
			array(
				'log'     => function ( $l ) use ( &$logged ) { $logged[] = $l; },
				'respond' => function () use ( &$sent ) { $sent = true; },
			)
		);
		$this->assertTrue( $sent );
		$this->assertStringStartsWith( 'proud-anon-post: blocked ', $logged[0] );
	}

	public function test_check_early_registers_stage_two_for_cookie_requests(): void {
		$_SERVER = array_merge( $_SERVER, $this->server() );
		$_COOKIE = array( 'wordpress_logged_in_abc' => 'v' );
		$GLOBALS['proud_test_added_actions'] = array();
		$logged = array();
		proud_anon_post_check_early( 'apache2handler', array( 'log' => function ( $l ) use ( &$logged ) { $logged[] = $l; } ) );
		$this->assertSame( array(), $logged );
		$this->assertSame( array( array( 'plugins_loaded', 'proud_anon_post_check_cookie', PHP_INT_MIN, 1 ) ), $GLOBALS['proud_test_added_actions'] );
	}

	public function test_stage_two_valid_cookie_allows(): void {
		Functions\expect( 'wp_validate_auth_cookie' )->once()->with( '', 'logged_in' )->andReturn( 5 );
		$logged = array();
		proud_anon_post_check_cookie( array( 'log' => function ( $l ) use ( &$logged ) { $logged[] = $l; } ) );
		$this->assertSame( array(), $logged );
	}

	public function test_stage_two_invalid_cookie_refused(): void {
		putenv( 'PC_ANON_POST_MODE=enforce' );
		Functions\when( 'wp_validate_auth_cookie' )->justReturn( false );
		$_SERVER = array_merge( $_SERVER, $this->server() );
		$logged  = array();
		$sent    = false;
		proud_anon_post_check_cookie(
			array(
				'log'     => function ( $l ) use ( &$logged ) { $logged[] = $l; },
				'respond' => function () use ( &$sent ) { $sent = true; },
			)
		);
		$this->assertTrue( $sent );
		$this->assertStringContainsString( 'reason=invalid_cookie', $logged[0] );
	}

	public function test_stripe_webhook_never_blocked_in_enforce_mode(): void {
		putenv( 'PC_ANON_POST_MODE=enforce' );
		$_SERVER = array_merge( $_SERVER, $this->server( array( 'REQUEST_URI' => '/?callback=gravityformsstripe' ) ) );
		$_GET    = array( 'callback' => 'gravityformsstripe' );
		$logged  = array();
		$sent    = false;
		proud_anon_post_check_early(
			'apache2handler',
			array(
				'log'     => function ( $l ) use ( &$logged ) { $logged[] = $l; },
				'respond' => function () use ( &$sent ) { $sent = true; },
			)
		);
		$this->assertSame( array(), $logged );
		$this->assertFalse( $sent );
	}

	public function test_send_forbidden_sets_403_and_body(): void {
		Functions\expect( 'status_header' )->once()->with( 403 );
		Functions\expect( 'nocache_headers' )->once();
		$this->expectOutputString( 'Forbidden' );
		proud_anon_post_send_forbidden( false );
	}

	public function test_log_line_has_names_not_values(): void {
		$line = proud_anon_post_format_log(
			'log',
			'no_cookie',
			$this->server( array( 'REQUEST_URI' => '/library/?token=SECRETVALUE', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8', 'HTTP_REFERER' => 'https://example.gov/x' ) ),
			array( 'token' => 'SECRETVALUE' ),
			array( 'log' => 'SECRETVALUE', 'pwd' => 'SECRETVALUE' )
		);
		$this->assertStringNotContainsString( 'SECRETVALUE', $line );
		$this->assertSame(
			'proud-anon-post: would_block host=example.gov ip=203.0.113.7 reason=no_cookie path="/library/" qkeys=token fields=log,pwd ct=application/x-www-form-urlencoded ref=same ua="Mozilla/5.0"',
			$line
		);
	}

	public function test_log_prefix_and_ref_values(): void {
		$line = proud_anon_post_format_log( 'enforce', 'no_cookie', $this->server( array( 'HTTP_REFERER' => 'https://other.example/' ) ), array(), array() );
		$this->assertStringStartsWith( 'proud-anon-post: blocked ', $line );
		$this->assertStringContainsString( 'ref=other', $line );
		$this->assertStringContainsString( 'qkeys=none fields=none ct=none ref=other', $line );
		$line = proud_anon_post_format_log( 'log', 'no_cookie', $this->server(), array(), array() );
		$this->assertStringContainsString( 'ref=none', $line );
	}

	public function test_log_neutralizes_injection(): void {
		$line = proud_anon_post_format_log(
			'log',
			'no_cookie',
			$this->server(
				array(
					'HTTP_HOST'       => "evil\nhost.gov\"",
					'REMOTE_ADDR'     => "1.2.3.4\nproud-anon-post: fake",
					'REQUEST_URI'     => "/a\r\n\"b\\c\xc3\xa9",
					'HTTP_USER_AGENT' => "UA\n\" ip=9.9.9.9 \\ \x00\xff",
				)
			),
			array( "k\ney\"" => 'v' ),
			array( "a\nb c" => 'v' )
		);
		$this->assertStringNotContainsString( "\n", $line );
		$this->assertStringNotContainsString( "\r", $line );
		$this->assertSame( 1, preg_match( '/^[\x20-\x7e]+$/', $line ) );
		$this->assertStringContainsString( 'ip=unknown', $line );
		$this->assertStringContainsString( 'host=evilhost.gov ', $line );
		$this->assertStringContainsString( 'path="/a_b_c"', $line );
		$this->assertStringContainsString( 'ua="UA_ ip=9.9.9.9 _ "', $line );
		$this->assertStringContainsString( 'qkeys=key ', $line );
		$this->assertStringContainsString( 'fields=abc ', $line );
	}

	public function test_names_are_capped(): void {
		$post = array();
		for ( $i = 0; $i < 40; $i++ ) {
			$post[ 'f' . $i ] = 'v';
		}
		$names = proud_anon_post_names( $post );
		$this->assertStringContainsString( ',f', $names );
		$this->assertMatchesRegularExpression( '/\+\d+$/', $names );
		$this->assertLessThanOrEqual( 150 + 4, strlen( $names ) );

		$this->assertSame( str_repeat( 'a', 40 ), proud_anon_post_names( array( str_repeat( 'a', 100 ) => 'v' ) ) );
	}

	public function test_log_line_stays_under_1kb_with_oversized_input(): void {
		$big  = str_repeat( 'x', 5000 );
		$keys = array();
		for ( $i = 0; $i < 100; $i++ ) {
			$keys[ str_repeat( 'k', 90 ) . $i ] = 'v';
		}
		$line = proud_anon_post_format_log(
			'enforce',
			'invalid_cookie',
			$this->server( array( 'HTTP_HOST' => $big, 'REQUEST_URI' => '/' . $big . '?' . $big, 'HTTP_USER_AGENT' => $big, 'CONTENT_TYPE' => $big, 'REMOTE_ADDR' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334' ) ),
			$keys,
			$keys
		);
		$this->assertLessThan( 1024, strlen( $line ) + 1 );
		$this->assertStringContainsString( 'path="/' . str_repeat( 'x', 199 ) . '"', $line );
	}
}
