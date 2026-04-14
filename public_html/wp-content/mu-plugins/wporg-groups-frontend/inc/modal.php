<?php
/**
 * Mounts the React event modal on every front-end page where the current
 * user can manage events.
 *
 * The modal itself is rendered entirely in JavaScript — see
 * `assets/js/event-modal.js`. This file is responsible for:
 *
 *   1. Enqueueing the script + style with the right `wp-*` dependencies so
 *      `@wordpress/components`, `@wordpress/block-editor`, and the core
 *      block library are available as globals on the front end.
 *   2. Outputting the empty `<div>` mount point + a small inline script
 *      that wires up `[data-wporg-groups-modal]` buttons in the theme to
 *      the React app.
 *   3. Capability gating — visitors and unprivileged users get nothing.
 *
 * @package WordCamp\Groups\Frontend
 */

namespace WordCamp\Groups\Frontend\Modal;

defined( 'WPINC' ) || die();

use function WordCamp\Groups\Frontend\Capabilities\current_user_can_manage_events;

const SCRIPT_HANDLE = 'wporg-groups-event-modal';
const STYLE_HANDLE  = 'wporg-groups-event-modal';

/**
 * Bootstrap the event modal assets and mount point.
 */
function bootstrap(): void {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );
	add_action( 'wp_footer', __NAMESPACE__ . '\render_mount_point' );
}

/**
 * Enqueue the React app and its dependencies.
 *
 * The dependency list pulls in everything needed for the inline Gutenberg
 * editor: `wp-element` for React, `wp-components` for Modal/TextControl,
 * `wp-block-editor` for the BlockEditorProvider, `wp-blocks` for the parse
 * /serialize helpers, `wp-block-library` for `registerCoreBlocks()`, and
 * `wp-data` for the underlying store. WordPress will also pull in the CSS
 * for `wp-edit-blocks` automatically when we declare the editor handles.
 */
function enqueue_assets(): void {
	if ( ! current_user_can_manage_events() ) {
		return;
	}

	$base_dir = dirname( __DIR__ );
	$base_url = plugins_url( '', $base_dir . '/wporg-groups-frontend.php' );

	// Load the media library frame so the modal can show the standard
	// "Choose a featured image" picker (uploads + library). `wp_enqueue_media`
	// must be called before our script enqueues `media-editor` as a dep.
	wp_enqueue_media();

	wp_enqueue_script(
		SCRIPT_HANDLE,
		$base_url . '/assets/js/event-modal.js',
		array(
			'wp-element',
			'wp-components',
			'wp-block-editor',
			'wp-blocks',
			'wp-block-library',
			'wp-data',
			'wp-api-fetch',
			'wp-i18n',
			'media-editor',
		),
		(string) filemtime( $base_dir . '/assets/js/event-modal.js' ),
		true
	);

	// Block editor component stylesheets — only the scoped ones needed for
	// the modal's inline editor. Avoid wp-edit-blocks and
	// wp-reset-editor-styles as they set body-level font/background
	// overrides that break the front-end page appearance.
	wp_enqueue_style( 'wp-components' );

	wp_enqueue_style(
		STYLE_HANDLE,
		$base_url . '/assets/css/event-modal.css',
		array( 'wp-components' ),
		(string) filemtime( $base_dir . '/assets/css/event-modal.css' )
	);

	// Hand the JS app the URLs and labels it needs to bootstrap. Avoids
	// every component having to import or compute these.
	wp_localize_script(
		SCRIPT_HANDLE,
		'wporgGroupsEventModal',
		array(
			'restNamespace' => 'wporg-groups/v1',
		)
	);
}

/**
 * Output the mount point for the React app.
 *
 * Rendered on `wp_footer` so it's available on every page (front page,
 * single event, archive, etc.) without the theme having to remember to
 * include a template part.
 */
function render_mount_point(): void {
	if ( ! current_user_can_manage_events() ) {
		return;
	}

	echo '<div id="wporg-groups-event-modal-root"></div>';
}
