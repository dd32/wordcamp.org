<?php

namespace WordCamp\Tests;
use WP_UnitTestCase, WP_UnitTest_Factory;

/**
 * Provides a mock WordCamp.org network of sites to test against.
 *
 * Test classes that need to interact with the database should extend this class, instead of extending
 * `WP_UnitTestCase` directly.
 *
 * Other test cases should extend `WP_UnitTestCase` directly, to avoid the performance delays that this
 * introduces.
 */
class Database_TestCase extends WP_UnitTestCase {
	protected static $central_site_id;
	protected static $events_root_site_id;
	protected static $wordcamp_root_site_id;
	protected static $year_dot_2018_site_id;
	protected static $year_dot_2019_site_id;
	protected static $slash_year_2016_site_id;
	protected static $slash_year_2018_dev_site_id;
	protected static $slash_year_2020_site_id;
	protected static $yearless_site_id;
	protected static $slash_year_with_yearless_site_id;
	protected static $events_rome_training_site_id;
	protected static $year_dot_2020_cancelled_site_id;
	protected static $slash_year_2021_cancelled_site_id;

	/**
	 * Create sites we'll need for the tests.
	 *
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		ms_upload_constants();

		global $wpdb;
		// Reset the sites table, so the IDs are predictable. WordPress doesn't respect network_id=1.
		$wpdb->query( "TRUNCATE TABLE $wpdb->site" );
		$wpdb->query( "TRUNCATE TABLE $wpdb->sitemeta" );

		$factory->network->create( array(
			'domain'           => 'wordcamp.test',
			'path'              => '/',
			'subdomain_install' => true,
			'network_id'        => WORDCAMP_NETWORK_ID,
		) );
		$factory->network->create( array(
			'domain'     => 'events.wordpress.test',
			'path'       => '/',
			'network_id' => EVENTS_NETWORK_ID,
		) );

		self::$wordcamp_root_site_id = $factory->blog->create( array(
			'domain'     => 'wordcamp.test',
			'path'       => '/',
			'blog_id'    => WORDCAMP_ROOT_BLOG_ID,
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		self::$events_root_site_id = $factory->blog->create( array(
			'domain'     => 'events.wordpress.test',
			'path'       => '/',
			'blog_id'    => EVENTS_ROOT_BLOG_ID,
			'network_id' => EVENTS_NETWORK_ID,
		) );

		self::$central_site_id = $factory->blog->create( array(
			'domain'     => 'central.wordcamp.test',
			'path'       => '/',
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		self::$year_dot_2018_site_id = $factory->blog->create( array(
			'domain'     => '2018.seattle.wordcamp.test',
			'path'       => '/',
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		self::$year_dot_2019_site_id = $factory->blog->create( array(
			'domain'     => '2019.seattle.wordcamp.test',
			'path'       => '/',
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		self::$slash_year_2016_site_id = $factory->blog->create( array(
			'domain'     => 'vancouver.wordcamp.test',
			'path'       => '/2016/',
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		self::$slash_year_2018_dev_site_id = $factory->blog->create( array(
			'domain'     => 'vancouver.wordcamp.test',
			'path'       => '/2018-developers/',
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		self::$slash_year_2020_site_id = $factory->blog->create( array(
			'domain'     => 'vancouver.wordcamp.test',
			'path'       => '/2020/',
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		// Sites like this are old edge cases from before a consistent structure was enforced.
		self::$yearless_site_id = $factory->blog->create( array(
			'domain'     => 'japan.wordcamp.test',
			'path'       => '/',
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		// Sites like this are newer and conform to the existing structure, but share a domain with a site that doesn't.
		self::$slash_year_with_yearless_site_id = $factory->blog->create( array(
			'domain'     => 'japan.wordcamp.test',
			'path'       => '/2021/',
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		self::$events_rome_training_site_id = $factory->blog->create( array(
			'domain'     => 'events.wordpress.test',
			'path'       => '/rome/2024/training/',
			'network_id' => EVENTS_NETWORK_ID,
		) );

		// Cancelled sites: these are the "newest" but should be skipped due to cancelled status.
		self::$year_dot_2020_cancelled_site_id = $factory->blog->create( array(
			'domain'     => '2020.seattle.wordcamp.test',
			'path'       => '/',
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		self::$slash_year_2021_cancelled_site_id = $factory->blog->create( array(
			'domain'     => 'vancouver.wordcamp.test',
			'path'       => '/2021/',
			'network_id' => WORDCAMP_NETWORK_ID,
		) );

		// Simulate renamed sites by adding old_home_url blogmeta.
		add_site_meta( self::$slash_year_2020_site_id, 'old_home_url', 'https://vancouver.wordcamp.test/2019/' );
		add_site_meta( self::$events_rome_training_site_id, 'old_home_url', 'https://events.wordpress.test/rome/2023/training/' );

		// Create WordCamp posts on the central/root blog with 'wcpt-cancelled' status for the cancelled sites.
		switch_to_blog( WORDCAMP_ROOT_BLOG_ID );

		$cancelled_seattle_post_id = wp_insert_post( array(
			'post_type'   => 'wordcamp',
			'post_status' => 'wcpt-cancelled',
			'post_title'  => 'WordCamp Seattle 2020 (Cancelled)',
		) );
		update_post_meta( $cancelled_seattle_post_id, '_site_id', self::$year_dot_2020_cancelled_site_id );

		$cancelled_vancouver_post_id = wp_insert_post( array(
			'post_type'   => 'wordcamp',
			'post_status' => 'wcpt-cancelled',
			'post_title'  => 'WordCamp Vancouver 2021 (Cancelled)',
		) );
		update_post_meta( $cancelled_vancouver_post_id, '_site_id', self::$slash_year_2021_cancelled_site_id );

		restore_current_blog();
	}

	/**
	 * Revert the persistent changes from `wpSetUpBeforeClass()` that won't be automatically cleaned up.
	 */
	public static function wpTearDownAfterClass() {
		global $wpdb;

		wp_delete_site( self::$central_site_id );
		wp_delete_site( self::$wordcamp_root_site_id );
		wp_delete_site( self::$events_root_site_id );
		wp_delete_site( self::$year_dot_2018_site_id );
		wp_delete_site( self::$year_dot_2019_site_id );
		wp_delete_site( self::$slash_year_2016_site_id );
		wp_delete_site( self::$slash_year_2018_dev_site_id );
		wp_delete_site( self::$slash_year_2020_site_id );
		wp_delete_site( self::$events_rome_training_site_id );
		wp_delete_site( self::$year_dot_2020_cancelled_site_id );
		wp_delete_site( self::$slash_year_2021_cancelled_site_id );

		foreach ( [ WORDCAMP_NETWORK_ID, EVENTS_NETWORK_ID ] as $network_id ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE site_id = %d", $network_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->site}     WHERE id      = %d", $network_id ) );
		}
	}
}
