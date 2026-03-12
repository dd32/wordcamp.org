<?php

namespace WordCamp\WCPT\Tests;
use WP_UnitTestCase;
use Meetup_Admin;

defined( 'WPINC' ) || die();

/**
 * Tests for Meetup_Admin column functionality
 *
 * @group wcpt
 * @group meetup
 */
class Test_Meetup_Admin_Columns extends WP_UnitTestCase {

	/**
	 * @var Meetup_Admin
	 */
	private static $meetup_admin;

	/**
	 * Set up the Meetup_Admin instance before running tests.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		self::$meetup_admin = new Meetup_Admin();
	}

	/**
	 * @covers Meetup_Admin::column_headers
	 */
	public function test_column_headers_includes_last_meetup() {
		$columns = self::$meetup_admin->column_headers( array() );
		$this->assertArrayHasKey( 'last_meetup', $columns );
	}

	/**
	 * @covers Meetup_Admin::column_headers
	 */
	public function test_column_headers_includes_number_of_events() {
		$columns = self::$meetup_admin->column_headers( array() );
		$this->assertArrayHasKey( 'number_of_events', $columns );
	}

	/**
	 * @covers Meetup_Admin::sortable_columns
	 */
	public function test_sortable_columns_includes_last_meetup() {
		$columns = self::$meetup_admin->sortable_columns( array() );
		$this->assertArrayHasKey( 'last_meetup', $columns );
		$this->assertSame( 'last_meetup', $columns['last_meetup'] );
	}

	/**
	 * @covers Meetup_Admin::sortable_columns
	 */
	public function test_sortable_columns_includes_number_of_events() {
		$columns = self::$meetup_admin->sortable_columns( array() );
		$this->assertArrayHasKey( 'number_of_events', $columns );
		$this->assertSame( 'number_of_events', $columns['number_of_events'] );
	}

	/**
	 * @covers Meetup_Admin::column_data
	 */
	public function test_column_data_last_meetup_displays_date() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'wp_meetup',
				'post_title'  => 'Test Meetup',
				'post_status' => 'wcpt-mtp-active',
			)
		);

		// Timestamp in milliseconds (as stored by Meetup API).
		$timestamp = 1700000000000;
		update_post_meta( $post_id, 'Last meetup on', $timestamp );

		$_GET['post_type'] = 'wp_meetup';

		ob_start();
		self::$meetup_admin->column_data( 'last_meetup', $post_id );
		$output = ob_get_clean();

		$this->assertSame( '2023-11-14', trim( $output ) );

		unset( $_GET['post_type'] );
	}

	/**
	 * @covers Meetup_Admin::column_data
	 */
	public function test_column_data_last_meetup_displays_dash_when_empty() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'wp_meetup',
				'post_title'  => 'Test Meetup No Date',
				'post_status' => 'wcpt-mtp-active',
			)
		);

		$_GET['post_type'] = 'wp_meetup';

		ob_start();
		self::$meetup_admin->column_data( 'last_meetup', $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( '—', trim( $output ) );

		unset( $_GET['post_type'] );
	}

	/**
	 * @covers Meetup_Admin::column_data
	 */
	public function test_column_data_number_of_events_displays_count() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'wp_meetup',
				'post_title'  => 'Test Meetup Count',
				'post_status' => 'wcpt-mtp-active',
			)
		);

		update_post_meta( $post_id, 'Number of past meetups', '42' );

		$_GET['post_type'] = 'wp_meetup';

		ob_start();
		self::$meetup_admin->column_data( 'number_of_events', $post_id );
		$output = ob_get_clean();

		$this->assertSame( '42', trim( $output ) );

		unset( $_GET['post_type'] );
	}

	/**
	 * @covers Meetup_Admin::column_data
	 */
	public function test_column_data_number_of_events_displays_zero_when_empty() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'wp_meetup',
				'post_title'  => 'Test Meetup No Count',
				'post_status' => 'wcpt-mtp-active',
			)
		);

		$_GET['post_type'] = 'wp_meetup';

		ob_start();
		self::$meetup_admin->column_data( 'number_of_events', $post_id );
		$output = ob_get_clean();

		$this->assertSame( '0', trim( $output ) );

		unset( $_GET['post_type'] );
	}
}
