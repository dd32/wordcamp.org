<?php

namespace WordCamp\Forms_To_Drafts\Tests;

if ( 'cli' !== php_sapi_name() ) {
	return;
}

$core_tests_directory = getenv( 'WP_TESTS_DIR' );

if ( ! $core_tests_directory ) {
	echo "\nPlease set the WP_TESTS_DIR environment variable to the folder where WordPress' PHPUnit tests live --";
	echo "\ne.g., export WP_TESTS_DIR=/srv/www/wordpress-develop/tests/phpunit\n";

	return;
}

require_once $core_tests_directory . '/includes/functions.php';

/**
 * Load the plugin and its dependencies.
 */
function manually_load_plugin() {
	// Load the utilities autoloader so Form_Spam_Prevention is available.
	require_once SUT_WPMU_PLUGIN_DIR . '/2-autoloader.php';

	require_once dirname( __DIR__ ) . '/wordcamp-forms-to-drafts.php';
}
tests_add_filter( 'muplugins_loaded', __NAMESPACE__ . '\manually_load_plugin' );
