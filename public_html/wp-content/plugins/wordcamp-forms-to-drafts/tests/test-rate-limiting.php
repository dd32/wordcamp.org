<?php

defined( 'WPINC' ) || die();

/**
 * Tests for IP-based rate limiting in WordCamp_Forms_To_Drafts.
 *
 * @covers WordCamp_Forms_To_Drafts::rate_limit_submissions
 */
class Test_Rate_Limiting extends WP_UnitTestCase {

	/**
	 * @var WordCamp_Forms_To_Drafts
	 */
	protected static $plugin;

	/**
	 * Set up shared fixtures before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$plugin = $GLOBALS['wordcamp_forms_to_drafts'];
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down() {
		// Clear any rate limit transients set during tests.
		// The key format is: form-spam-prevention-wcfd-{md5(ip)}.
		delete_transient( 'form-spam-prevention-wcfd-' . md5( '127.0.0.1' ) );

		// Reset to logged-out state.
		wp_set_current_user( 0 );

		// Reset REMOTE_ADDR.
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		parent::tear_down();
	}

	/**
	 * Verify logged-in users bypass rate limiting entirely.
	 */
	public function test_logged_in_user_bypasses_rate_limiting() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		$result = self::$plugin->rate_limit_submissions( false );

		$this->assertFalse( $result );
	}

	/**
	 * Verify already-spam submissions pass through unchanged.
	 */
	public function test_already_spam_passes_through() {
		wp_set_current_user( 0 );
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		$error = new WP_Error( 'spam', 'Already spam' );
		$result = self::$plugin->rate_limit_submissions( $error );

		$this->assertWPError( $result );
		$this->assertSame( 'spam', $result->get_error_code() );
	}

	/**
	 * Verify truthy non-WP_Error spam value passes through.
	 */
	public function test_truthy_spam_value_passes_through() {
		wp_set_current_user( 0 );
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		$result = self::$plugin->rate_limit_submissions( true );

		$this->assertTrue( $result );
	}

	/**
	 * Verify the first few submissions succeed for logged-out users.
	 *
	 * With score_threshold=4 and each submission adding 1 point,
	 * submissions 1-3 should succeed (scores 1, 2, 3 -- all below threshold).
	 */
	public function test_first_submissions_succeed() {
		wp_set_current_user( 0 );
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		// First submission: score goes to 1.
		$result1 = self::$plugin->rate_limit_submissions( false );
		$this->assertFalse( $result1, 'First submission should succeed.' );

		// Second submission: score goes to 2.
		$result2 = self::$plugin->rate_limit_submissions( false );
		$this->assertFalse( $result2, 'Second submission should succeed.' );

		// Third submission: score goes to 3.
		$result3 = self::$plugin->rate_limit_submissions( false );
		$this->assertFalse( $result3, 'Third submission should succeed.' );
	}

	/**
	 * Verify the 4th submission returns WP_Error for logged-out users.
	 *
	 * After 3 successful submissions the score is 3. The 4th call checks
	 * is_ip_address_throttled() which returns true when score >= 4, but
	 * at the start of the 4th call the score is 3 (not yet >= 4), so
	 * the 4th call also adds 1 (score becomes 4). The 5th call sees
	 * score=4 >= threshold=4 and returns WP_Error.
	 */
	public function test_rate_limited_after_threshold() {
		wp_set_current_user( 0 );
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		// Make 4 submissions to reach score of 4.
		for ( $i = 0; $i < 4; $i++ ) {
			self::$plugin->rate_limit_submissions( false );
		}

		// 5th submission should be rate limited (score is now 4, which >= threshold).
		$result = self::$plugin->rate_limit_submissions( false );

		$this->assertWPError( $result );
		$this->assertSame( 'rate_limited', $result->get_error_code() );
	}

	/**
	 * Verify rate limit resets after the transient is cleared.
	 *
	 * In production the transient expires after HOUR_IN_SECONDS.
	 * We simulate this by deleting the transient directly.
	 */
	public function test_rate_limit_resets_after_transient_expires() {
		wp_set_current_user( 0 );
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		// Exceed the threshold.
		for ( $i = 0; $i < 4; $i++ ) {
			self::$plugin->rate_limit_submissions( false );
		}

		// Confirm rate limited.
		$result = self::$plugin->rate_limit_submissions( false );
		$this->assertWPError( $result );

		// Simulate transient expiry.
		delete_transient( 'form-spam-prevention-wcfd-' . md5( '127.0.0.1' ) );

		// Should succeed again after reset.
		$result = self::$plugin->rate_limit_submissions( false );
		$this->assertFalse( $result, 'Submission should succeed after transient expires.' );
	}

	/**
	 * Verify logged-in users bypass rate limiting even when IP is already throttled.
	 */
	public function test_logged_in_user_bypasses_even_when_throttled() {
		wp_set_current_user( 0 );
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		// Exceed the threshold as logged-out user.
		for ( $i = 0; $i < 4; $i++ ) {
			self::$plugin->rate_limit_submissions( false );
		}

		// Log in.
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Should bypass even though IP is throttled.
		$result = self::$plugin->rate_limit_submissions( false );
		$this->assertFalse( $result );
	}
}
