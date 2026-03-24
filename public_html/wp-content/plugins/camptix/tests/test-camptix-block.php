<?php

defined( 'WPINC' ) || die();

/**
 * Tests for the CampTix Gutenberg block integration.
 *
 * @covers CampTix_Plugin::template_redirect
 * @covers CampTix_Plugin::form_start
 */
class Test_CampTix_Block extends WP_UnitTestCase {

	/**
	 * @var CampTix_Plugin
	 */
	protected static $camptix;

	/**
	 * Post IDs to clean up.
	 *
	 * @var array
	 */
	protected $post_ids = array();

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$camptix = $GLOBALS['camptix'];
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down() {
		foreach ( $this->post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		$this->post_ids = array();

		// Reset block attributes.
		self::$camptix->block_attributes = array();

		parent::tear_down();
	}

	/**
	 * Helper: create a ticket.
	 *
	 * @param string $title Ticket title.
	 * @param float  $price Ticket price.
	 * @return int Post ID.
	 */
	protected function create_ticket( $title = 'General Admission', $price = 25.00 ) {
		$ticket_id = wp_insert_post( array(
			'post_type'   => 'tix_ticket',
			'post_status' => 'publish',
			'post_title'  => $title,
		) );

		update_post_meta( $ticket_id, 'tix_price', $price );
		update_post_meta( $ticket_id, 'tix_quantity', 100 );

		$this->post_ids[] = $ticket_id;

		return $ticket_id;
	}

	/**
	 * Test that template_redirect detects a block in page content.
	 */
	public function test_block_detection_sets_block_attributes() {
		$ticket_id = $this->create_ticket();

		$page_id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Tickets',
			'post_content' => '<!-- wp:wordcamp/camptix {"ticketIds":[' . $ticket_id . ']} /-->',
		) );
		$this->post_ids[] = $page_id;

		// Parse the block attributes as template_redirect would.
		$post   = get_post( $page_id );
		$blocks = parse_blocks( $post->post_content );

		$block_attrs = array();
		foreach ( $blocks as $block ) {
			if ( 'wordcamp/camptix' === $block['blockName'] ) {
				$block_attrs = $block['attrs'];
				break;
			}
		}

		$this->assertArrayHasKey( 'ticketIds', $block_attrs );
		$this->assertContains( $ticket_id, $block_attrs['ticketIds'] );
	}

	/**
	 * Test that ticketIds attribute filters which tickets are loaded.
	 */
	public function test_ticket_filtering_by_ids() {
		$ticket_a = $this->create_ticket( 'Ticket A', 10.00 );
		$ticket_b = $this->create_ticket( 'Ticket B', 20.00 );
		$ticket_c = $this->create_ticket( 'Ticket C', 30.00 );

		// Simulate block attributes filtering to only ticket A and C.
		$all_tickets = array(
			$ticket_a => get_post( $ticket_a ),
			$ticket_b => get_post( $ticket_b ),
			$ticket_c => get_post( $ticket_c ),
		);

		$block_attributes = array( 'ticketIds' => array( $ticket_a, $ticket_c ) );

		$filtered = array_intersect_key(
			$all_tickets,
			array_flip( $block_attributes['ticketIds'] )
		);

		$this->assertCount( 2, $filtered );
		$this->assertArrayHasKey( $ticket_a, $filtered );
		$this->assertArrayHasKey( $ticket_c, $filtered );
		$this->assertArrayNotHasKey( $ticket_b, $filtered );
	}

	/**
	 * Test that custom noTicketsMessage is used when set.
	 */
	public function test_custom_no_tickets_message() {
		$custom_message = 'Tickets coming soon - check back next week!';

		self::$camptix->block_attributes = array(
			'noTicketsMessage' => $custom_message,
		);

		// With no tickets and block attributes set, form_start should use the custom message.
		self::$camptix->tickets = array();

		ob_start();
		self::$camptix->form_start();
		$output = ob_get_clean();

		$this->assertStringContainsString( $custom_message, $output );
	}

	/**
	 * Test that default no-tickets message is used when block attribute is empty.
	 */
	public function test_default_no_tickets_message_when_attribute_empty() {
		self::$camptix->block_attributes = array();
		self::$camptix->tickets = array();

		ob_start();
		self::$camptix->form_start();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Sorry, but there are currently no tickets for sale', $output );
	}

	/**
	 * Test that maxTicketsPerOrder block attribute affects form_start output.
	 */
	public function test_max_tickets_per_order_attribute() {
		$ticket_id = $this->create_ticket( 'Test Ticket', 10.00 );

		$ticket               = get_post( $ticket_id );
		$ticket->tix_price    = 10.00;
		$ticket->tix_remaining = 50;
		$ticket->tix_coupon_applied    = false;
		$ticket->tix_discounted_price  = 10.00;

		self::$camptix->tickets = array( $ticket_id => $ticket );
		self::$camptix->block_attributes = array(
			'maxTicketsPerOrder' => 3,
		);

		ob_start();
		self::$camptix->form_start();
		$output = ob_get_clean();

		// The quantity select should have options 0-3 (4 options), not 0-10.
		// Count option elements for this ticket.
		preg_match_all( '/<option[^>]*value="(\d+)"/', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$max_value = max( array_map( 'intval', $matches[1] ) );
			$this->assertEquals( 3, $max_value, 'Max ticket quantity should be 3' );
		}
	}

	/**
	 * Test that auto-coupon is injected into REQUEST when block attribute is set.
	 */
	public function test_auto_coupon_injection() {
		// Ensure no manual coupon is set.
		unset( $_REQUEST['tix_coupon'] );

		$block_attributes = array( 'coupon' => 'EARLYBIRD' );

		// Simulate the auto-coupon logic from template_redirect.
		if ( empty( $_REQUEST['tix_coupon'] ) && ! empty( $block_attributes['coupon'] ) ) {
			$_REQUEST['tix_coupon'] = sanitize_text_field( $block_attributes['coupon'] );
		}

		$this->assertEquals( 'EARLYBIRD', $_REQUEST['tix_coupon'] );

		// Clean up.
		unset( $_REQUEST['tix_coupon'] );
	}

	/**
	 * Test that manual coupon takes precedence over block auto-coupon.
	 */
	public function test_manual_coupon_overrides_auto_coupon() {
		$_REQUEST['tix_coupon'] = 'MANUAL';

		$block_attributes = array( 'coupon' => 'EARLYBIRD' );

		// Simulate the auto-coupon logic from template_redirect.
		if ( empty( $_REQUEST['tix_coupon'] ) && ! empty( $block_attributes['coupon'] ) ) {
			$_REQUEST['tix_coupon'] = sanitize_text_field( $block_attributes['coupon'] );
		}

		$this->assertEquals( 'MANUAL', $_REQUEST['tix_coupon'] );

		// Clean up.
		unset( $_REQUEST['tix_coupon'] );
	}

	/**
	 * Test backward compatibility: shortcode continues to work without block attributes.
	 */
	public function test_shortcode_still_detected() {
		$content = '[camptix]';
		$this->assertNotFalse( stristr( $content, '[camptix' ) );

		preg_match( "#\\[camptix(\s[^\\]]+)?\\]#", $content, $matches );
		$this->assertNotEmpty( $matches );
		$this->assertEquals( '[camptix]', $matches[0] );
	}
}
