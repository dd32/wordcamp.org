<?php

namespace WordCamp\WCPT\Tests;
use WP_UnitTestCase;
use WordCamp_Admin;

defined( 'WPINC' ) || die();

/**
 * Tests for Actual Attendees field requirements
 *
 * @group wcpt
 * @group wordcamp
 */
class Test_WordCamp_Actual_Attendees extends WP_UnitTestCase {

	/**
	 * @covers WordCamp_Admin::require_complete_meta_to_publish_wordcamp
	 */
	public function test_closing_without_actual_attendees_reverts_to_scheduled() {
		$wordcamp_admin = new WordCamp_Admin();

		$post_data = array(
			'post_type'   => WCPT_POST_TYPE_ID,
			'post_status' => 'wcpt-closed',
		);

		$post_data_raw = array(
			'ID' => 999999,
		);

		// Simulate admin form submission without Actual Attendees.
		$_POST['action'] = 'editpost';

		$result = $wordcamp_admin->require_complete_meta_to_publish_wordcamp( $post_data, $post_data_raw );

		$this->assertSame( 'wcpt-scheduled', $result['post_status'], 'Status should revert to scheduled when Actual Attendees is missing' );

		unset( $_POST['action'] );
	}

	/**
	 * @covers WordCamp_Admin::require_complete_meta_to_publish_wordcamp
	 */
	public function test_closing_with_actual_attendees_allows_closed_status() {
		$wordcamp_admin = new WordCamp_Admin();

		$post_data = array(
			'post_type'   => WCPT_POST_TYPE_ID,
			'post_status' => 'wcpt-closed',
		);

		$post_data_raw = array(
			'ID' => 999999,
		);

		// Simulate admin form submission with Actual Attendees.
		$_POST['action']              = 'editpost';
		$_POST['wcpt_actual_attendees'] = '150';

		$result = $wordcamp_admin->require_complete_meta_to_publish_wordcamp( $post_data, $post_data_raw );

		$this->assertSame( 'wcpt-closed', $result['post_status'], 'Status should remain closed when Actual Attendees is provided' );

		unset( $_POST['action'], $_POST['wcpt_actual_attendees'] );
	}

	/**
	 * @covers WordCamp_Admin::require_complete_meta_to_publish_wordcamp
	 */
	public function test_cron_auto_close_is_not_blocked() {
		$wordcamp_admin = new WordCamp_Admin();

		$post_data = array(
			'post_type'   => WCPT_POST_TYPE_ID,
			'post_status' => 'wcpt-closed',
		);

		$post_data_raw = array(
			'ID' => 999999,
		);

		// No $_POST['action'] set - simulates cron context.

		$result = $wordcamp_admin->require_complete_meta_to_publish_wordcamp( $post_data, $post_data_raw );

		$this->assertSame( 'wcpt-closed', $result['post_status'], 'Cron auto-close should not be blocked by the validation' );
	}

	/**
	 * @covers WordCamp_Admin::meta_keys
	 */
	public function test_actual_attendees_visible_when_scheduled() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => WCPT_POST_TYPE_ID,
				'post_title'  => 'Test WordCamp Scheduled',
				'post_status' => 'wcpt-needs-vetting',
			)
		);

		// Directly update status to avoid transition hooks.
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_status' => 'wcpt-scheduled' ), array( 'ID' => $post_id ) );
		clean_post_cache( $post_id );

		// Set up the global post so get_post() / get_post_status() work.
		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		$keys = WordCamp_Admin::meta_keys( 'wordcamp' );

		$this->assertArrayHasKey( 'Actual Attendees', $keys, 'Actual Attendees should be visible when status is wcpt-scheduled' );

		wp_reset_postdata();
	}

	/**
	 * @covers WordCamp_Admin::meta_keys
	 */
	public function test_actual_attendees_hidden_before_scheduled() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => WCPT_POST_TYPE_ID,
				'post_title'  => 'Test WordCamp Pre-Planning',
				'post_status' => 'wcpt-needs-vetting',
			)
		);

		// Set up the global post so get_post() / get_post_status() work.
		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		$keys = WordCamp_Admin::meta_keys( 'wordcamp' );

		$this->assertArrayNotHasKey( 'Actual Attendees', $keys, 'Actual Attendees should be hidden before wcpt-scheduled status' );

		wp_reset_postdata();
	}
}
