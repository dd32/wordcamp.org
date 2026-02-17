<?php
/**
 * wp-env early-loading mu-plugin.
 *
 * wp-env runs `wp core install` before `wp config set`, so custom constants
 * aren't available when mu-plugins first load during installation. This file
 * provides safe defaults so the mu-plugins don't cause fatal errors during
 * that initial bootstrap.
 *
 * This file is mapped into the mu-plugins directory by .wp-env.json and is
 * NOT part of the production codebase.
 *
 * @see https://github.com/WordPress/wordpress-develop/pull/XXXX (proposed wp_get_mu_plugins filter)
 */

$defaults = array(
	'WORDCAMP_ENVIRONMENT'  => 'local',
	'WORDCAMP_NETWORK_ID'   => 1,
	'WORDCAMP_ROOT_BLOG_ID' => 5,
	'EVENTS_NETWORK_ID'     => 2,
	'EVENTS_ROOT_BLOG_ID'   => 47,
	'CAMPUS_NETWORK_ID'     => 3,
	'CAMPUS_ROOT_BLOG_ID'   => 47,
	'SITE_ID_CURRENT_SITE'  => 1,
	'BLOG_ID_CURRENT_SITE'  => 5,
);

foreach ( $defaults as $name => $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}
