<?php
/**
 * WordPress db.php drop-in for wp-env local development.
 *
 * This file is mapped to wp-content/db.php by wp-env. It defines constants
 * required by the WordCamp mu-plugins before they load.
 *
 * WordPress loads db.php before mu-plugins, making it the right place
 * to define constants that mu-plugins depend on. Since we don't set $wpdb,
 * WordPress continues with its default database setup.
 *
 * Multisite note: MULTISITE is NOT in .wp-env.json because wp-env doesn't
 * handle multisite installation. Instead, we define it here (db.php loads
 * before the multisite check in wp-settings.php), and multisite-install is
 * run as a post-start step.
 */

$is_installing    = defined( 'WP_INSTALLING' ) && WP_INSTALLING;
$is_tests_env     = defined( 'DB_NAME' ) && 'tests-wordpress' === DB_NAME;

// Check if multisite tables exist in the database.
$multisite_ready = false;
if ( ! $is_installing && defined( 'DB_HOST' ) ) {
	$conn = @new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME ); // phpcs:ignore
	if ( ! $conn->connect_error ) {
		$result = $conn->query( "SHOW TABLES LIKE 'wp_site'" );
		$multisite_ready = $result && $result->num_rows > 0;
		$conn->close();
	}
}

// Bypass mu-plugins in these cases:
// 1. During install (multisite functions not available yet)
// 2. Multisite not set up yet (mu-plugins need multisite)
// 3. Tests environment (test bootstraps handle loading; prevents duplicate loading
//    since mu-plugins are mapped via both wp-content/ and wordcamp-project/)
if ( $is_installing || ! $multisite_ready || $is_tests_env ) {
	$empty_dir = sys_get_temp_dir() . '/wp-empty-mu-plugins';
	if ( ! is_dir( $empty_dir ) ) {
		mkdir( $empty_dir );
	}
	if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
		define( 'WPMU_PLUGIN_DIR', $empty_dir );
	}
}

if ( $multisite_ready ) {
	if ( ! defined( 'MULTISITE' ) ) {
		define( 'MULTISITE', true );
	}

	if ( ! defined( 'SUBDOMAIN_INSTALL' ) ) {
		define( 'SUBDOMAIN_INSTALL', false );
	}

	// Skip SUNRISE in tests - test bootstraps load sunrise.php manually
	// from the project root, avoiding duplicate-load fatal errors.
	if ( ! $is_tests_env && ! defined( 'SUNRISE' ) ) {
		define( 'SUNRISE', true );
	}

	if ( ! defined( 'DOMAIN_CURRENT_SITE' ) ) {
		define( 'DOMAIN_CURRENT_SITE', 'localhost' );
	}

	if ( ! defined( 'PATH_CURRENT_SITE' ) ) {
		define( 'PATH_CURRENT_SITE', '/' );
	}

	if ( ! defined( 'SITE_ID_CURRENT_SITE' ) ) {
		define( 'SITE_ID_CURRENT_SITE', 1 );
	}

	if ( ! defined( 'BLOG_ID_CURRENT_SITE' ) ) {
		define( 'BLOG_ID_CURRENT_SITE', 1 );
	}
}

// Core WordCamp constants required by mu-plugins.
if ( ! defined( 'IS_WORDCAMP_NETWORK' ) ) {
	define( 'IS_WORDCAMP_NETWORK', true );
}

if ( ! defined( 'WORDCAMP_ENVIRONMENT' ) ) {
	define( 'WORDCAMP_ENVIRONMENT', 'local' );
}

if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
	define( 'WP_ENVIRONMENT_TYPE', 'local' );
}

// Network constants.
if ( ! defined( 'WORDCAMP_NETWORK_ID' ) ) {
	define( 'WORDCAMP_NETWORK_ID', 1 );
}

if ( ! defined( 'WORDCAMP_ROOT_BLOG_ID' ) ) {
	define( 'WORDCAMP_ROOT_BLOG_ID', 1 );
}

if ( ! defined( 'EVENTS_NETWORK_ID' ) ) {
	define( 'EVENTS_NETWORK_ID', 2 );
}

if ( ! defined( 'EVENTS_ROOT_BLOG_ID' ) ) {
	define( 'EVENTS_ROOT_BLOG_ID', 47 );
}

if ( ! defined( 'CAMPUS_NETWORK_ID' ) ) {
	define( 'CAMPUS_NETWORK_ID', 3 );
}

if ( ! defined( 'CAMPUS_ROOT_BLOG_ID' ) ) {
	define( 'CAMPUS_ROOT_BLOG_ID', 47 );
}

// Email constants used by mu-plugins.
if ( ! defined( 'EMAIL_DEVELOPER_NOTIFICATIONS' ) ) {
	define( 'EMAIL_DEVELOPER_NOTIFICATIONS', 'developers@example.test' );
}

if ( ! defined( 'EMAIL_CENTRAL_SUPPORT' ) ) {
	define( 'EMAIL_CENTRAL_SUPPORT', 'support@wordcamp.test' );
}

// Service constants (empty for local dev).
if ( ! defined( 'SLACK_ERROR_REPORT_URL' ) ) {
	define( 'SLACK_ERROR_REPORT_URL', '' );
}

if ( ! defined( 'WORDCAMP_LOGS_SLACK_CHANNEL' ) ) {
	define( 'WORDCAMP_LOGS_SLACK_CHANNEL', '' );
}

if ( ! defined( 'WORDCAMP_LOGS_JETPACK_SLACK_CHANNEL' ) ) {
	define( 'WORDCAMP_LOGS_JETPACK_SLACK_CHANNEL', '' );
}

if ( ! defined( 'WORDCAMP_LOGS_GUTENBERG_SLACK_CHANNEL' ) ) {
	define( 'WORDCAMP_LOGS_GUTENBERG_SLACK_CHANNEL', '' );
}

// Meetup API.
if ( ! defined( 'MEETUP_API_BASE_URL' ) ) {
	define( 'MEETUP_API_BASE_URL', 'https://api.meetup.com/' );
}

if ( ! defined( 'MEETUP_MEMBER_ID' ) ) {
	define( 'MEETUP_MEMBER_ID', 72560962 );
}

// QBO.
if ( ! defined( 'WORDCAMP_QBO_HMAC_KEY' ) ) {
	define( 'WORDCAMP_QBO_HMAC_KEY', 'localhmac' );
}

// Content paths.
if ( ! defined( 'WORDCAMP_UTILITIES_DIR' ) ) {
	define( 'WORDCAMP_UTILITIES_DIR', WP_CONTENT_DIR . '/mu-plugins/utilities' );
}
