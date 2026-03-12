<?php

/**
 * Tests for wcorg-misc.php
 */
class Test_WCOrg_Misc extends WP_UnitTestCase {

	/**
	 * Attachment pages should be disabled on all sites.
	 *
	 * @see https://github.com/WordPress/wordcamp.org/issues/1333
	 */
	public function test_attachment_pages_disabled() {
		$this->assertEquals( 0, get_option( 'wp_attachment_pages_enabled' ) );
	}
}
