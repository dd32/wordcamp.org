<?php
/**
 * GatherPress tweaks for WordPress Group sites.
 *
 * Loaded on the groups network only (sits in the `groups/` mu-plugins folder).
 *
 * @package WordCamp\Groups
 */

namespace WordCamp\Groups\GatherPress_Tweaks;

defined( 'WPINC' ) || die();

/**
 * Disable the "Show Timezone" GatherPress setting so event date blocks
 * never append "GMT+0000" or similar suffixes.
 *
 * Also disable anonymous RSVP at the global setting level.
 */
add_filter(
	'pre_option_gatherpress_settings',
	static function ( $value ) {
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$value['show_timezone']        = 0;
		$value['enable_anonymous_rsvp'] = 0;

		return $value;
	}
);

/**
 * Force anonymous RSVP off for all events on group sites.
 *
 * GatherPress checks `get_post_meta( $id, 'gatherpress_enable_anonymous_rsvp', true )`
 * to decide whether to show the anonymous checkbox. Returning a non-null value
 * from `get_post_metadata` short-circuits the real lookup; wrapping in an array
 * mirrors what WP would return for a single meta value of empty-string.
 */
add_filter(
	'get_post_metadata',
	static function ( $value, $object_id, $meta_key ) {
		if ( 'gatherpress_enable_anonymous_rsvp' === $meta_key ) {
			return array( '' );
		}

		return $value;
	},
	10,
	3
);

/**
 * Make the gatherpress_venue post type non-public so it has no front-end
 * archive or singular URLs. Venues are only used as metadata on events.
 */
add_filter(
	'register_post_type_args',
	static function ( array $args, string $post_type ): array {
		if ( 'gatherpress_venue' === $post_type ) {
			$args['public']             = false;
			$args['publicly_queryable'] = false;
			$args['has_archive']        = false;
		}

		return $args;
	},
	10,
	2
);

/**
 * Inject default inner blocks into the gatherpress/rsvp block when it
 * renders from a theme template with no inner blocks.
 *
 * The rsvp block stores per-status inner block templates in a
 * `serializedInnerBlocks` attribute. When inserted via the editor the JS
 * populates all five statuses; theme templates only carry the visible
 * inner blocks, so the other statuses are empty. This filter fills in the
 * missing templates so every RSVP status renders correctly.
 */
add_filter(
	'render_block_data',
	static function ( array $block ): array {
		if ( 'gatherpress/rsvp' !== ( $block['blockName'] ?? '' ) ) {
			return $block;
		}

		$serialized = $block['attrs']['serializedInnerBlocks'] ?? '[]';
		$decoded    = json_decode( $serialized, true );

		// If already populated with multiple statuses, leave it alone.
		if ( is_array( $decoded ) && count( $decoded ) > 1 ) {
			return $block;
		}

		// If the block has no inner blocks (self-closing in the template),
		// parse in the no_status template as its inner blocks and provide
		// a proper wrapper div so transform_block_content() can extract it.
		if ( empty( $block['innerBlocks'] ) ) {
			$block['innerBlocks']  = parse_blocks( get_rsvp_no_status_markup() );
			$block['innerHTML']    = '<div class="wp-block-gatherpress-rsvp"></div>';
			$block['innerContent'] = array( '<div class="wp-block-gatherpress-rsvp">', null, '</div>' );
		}

		// Provide serialized templates for every status.
		$block['attrs']['serializedInnerBlocks'] = wp_json_encode(
			array(
				'attending'     => get_rsvp_attending_markup(),
				'not_attending' => get_rsvp_not_attending_markup(),
				'waiting_list'  => get_rsvp_not_attending_markup(),
				'past'          => get_rsvp_past_markup(),
			)
		);

		return $block;
	}
);

/**
 * Block markup for the "no_status" RSVP state (user has not RSVPed yet).
 *
 * Shows an RSVP button. Logged-in users get the Attend modal; logged-out
 * users get the Login Required modal.
 */
function get_rsvp_no_status_markup(): string {
	return '<!-- wp:gatherpress/modal-manager -->
<div class="wp-block-gatherpress-modal-manager">
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"tagName":"button","width":100,"className":"gatherpress-modal--trigger-open"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 gatherpress-modal--trigger-open"><button class="wp-block-button__link wp-element-button">RSVP</button></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:gatherpress/modal {"className":"gatherpress-modal--type-rsvp"} -->
<div aria-hidden="true" aria-label="Modal" aria-modal="true" role="dialog" tabindex="-1" class="wp-block-gatherpress-modal gatherpress-modal--type-rsvp">
<!-- wp:gatherpress/modal-content -->
<div class="wp-block-gatherpress-modal-content">
<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0"},"padding":{"top":"0"}}}} -->
<p style="margin-top:0;padding-top:0"><strong>RSVP to this event</strong></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>To confirm your attendance, click the <strong>Attend</strong> button below.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"flex-start"},"style":{"spacing":{"margin":{"bottom":"0"},"padding":{"bottom":"0"}}}} -->
<div class="wp-block-buttons" style="margin-bottom:0;padding-bottom:0">
<!-- wp:button {"tagName":"button","className":"gatherpress-rsvp--trigger-update"} -->
<div class="wp-block-button gatherpress-rsvp--trigger-update"><button class="wp-block-button__link wp-element-button">Attend</button></div>
<!-- /wp:button -->
<!-- wp:button {"tagName":"button","className":"is-style-outline gatherpress-modal--trigger-close"} -->
<div class="wp-block-button is-style-outline gatherpress-modal--trigger-close"><button class="wp-block-button__link wp-element-button">Close</button></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:gatherpress/modal-content -->
</div>
<!-- /wp:gatherpress/modal -->

<!-- wp:gatherpress/modal {"className":"gatherpress-modal--login"} -->
<div aria-hidden="true" aria-label="Modal" aria-modal="true" role="dialog" tabindex="-1" class="wp-block-gatherpress-modal gatherpress-modal--login">
<!-- wp:gatherpress/modal-content -->
<div class="wp-block-gatherpress-modal-content">
<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0"},"padding":{"top":"0"}}}} -->
<p style="margin-top:0;padding-top:0"><strong>Login Required</strong></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"gatherpress--has-login-url"} -->
<p class="gatherpress--has-login-url">Please <a href="#gatherpress-login-url">log in</a> to RSVP to this event.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"flex-start"},"style":{"spacing":{"margin":{"bottom":"0"},"padding":{"bottom":"0"}}}} -->
<div class="wp-block-buttons" style="margin-bottom:0;padding-bottom:0">
<!-- wp:button {"tagName":"button","className":"gatherpress-modal--trigger-close"} -->
<div class="wp-block-button gatherpress-modal--trigger-close"><button class="wp-block-button__link wp-element-button">Close</button></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:gatherpress/modal-content -->
</div>
<!-- /wp:gatherpress/modal -->
</div>
<!-- /wp:gatherpress/modal-manager -->';
}

/**
 * Block markup for the "attending" RSVP state.
 */
function get_rsvp_attending_markup(): string {
	return '<!-- wp:gatherpress/modal-manager {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
<div class="wp-block-gatherpress-modal-manager">
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"tagName":"button","width":100,"className":"gatherpress-modal--trigger-open"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 gatherpress-modal--trigger-open"><button class="wp-block-button__link wp-element-button">Edit RSVP</button></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group">
<!-- wp:gatherpress/icon {"icon":"yes-alt","iconSize":24} /-->
<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0"},"padding":{"top":"0"}}}} -->
<p style="margin-top:0;padding-top:0"><strong>Attending</strong></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:gatherpress/modal {"className":"gatherpress-modal--type-rsvp"} -->
<div aria-hidden="true" aria-label="Modal" aria-modal="true" role="dialog" tabindex="-1" class="wp-block-gatherpress-modal gatherpress-modal--type-rsvp">
<!-- wp:gatherpress/modal-content -->
<div class="wp-block-gatherpress-modal-content">
<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0"},"padding":{"top":"0"}}}} -->
<p style="margin-top:0;padding-top:0"><strong>You are attending</strong></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>To change your attendance status, click <strong>Not Attending</strong> below.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"flex-start"},"style":{"spacing":{"margin":{"bottom":"0"},"padding":{"bottom":"0"}}}} -->
<div class="wp-block-buttons" style="margin-bottom:0;padding-bottom:0">
<!-- wp:button {"tagName":"button","className":"gatherpress-rsvp--trigger-update"} -->
<div class="wp-block-button gatherpress-rsvp--trigger-update"><button class="wp-block-button__link wp-element-button">Not Attending</button></div>
<!-- /wp:button -->
<!-- wp:button {"tagName":"button","className":"is-style-outline gatherpress-modal--trigger-close"} -->
<div class="wp-block-button is-style-outline gatherpress-modal--trigger-close"><button class="wp-block-button__link wp-element-button">Close</button></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:gatherpress/modal-content -->
</div>
<!-- /wp:gatherpress/modal -->
</div>
<!-- /wp:gatherpress/modal-manager -->';
}

/**
 * Block markup for the "not_attending" / "waiting_list" RSVP states.
 */
function get_rsvp_not_attending_markup(): string {
	return '<!-- wp:gatherpress/modal-manager {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
<div class="wp-block-gatherpress-modal-manager">
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"tagName":"button","width":100,"className":"gatherpress-modal--trigger-open"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 gatherpress-modal--trigger-open"><button class="wp-block-button__link wp-element-button">Edit RSVP</button></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group">
<!-- wp:gatherpress/icon {"icon":"dismiss","iconSize":24} /-->
<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0"},"padding":{"top":"0"}}}} -->
<p style="margin-top:0;padding-top:0"><strong>Not Attending</strong></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:gatherpress/modal {"className":"gatherpress-modal--type-rsvp"} -->
<div aria-hidden="true" aria-label="Modal" aria-modal="true" role="dialog" tabindex="-1" class="wp-block-gatherpress-modal gatherpress-modal--type-rsvp">
<!-- wp:gatherpress/modal-content -->
<div class="wp-block-gatherpress-modal-content">
<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0"},"padding":{"top":"0"}}}} -->
<p style="margin-top:0;padding-top:0"><strong>Not attending</strong></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Changed your mind? Click <strong>Attend</strong> below.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"flex-start"},"style":{"spacing":{"margin":{"bottom":"0"},"padding":{"bottom":"0"}}}} -->
<div class="wp-block-buttons" style="margin-bottom:0;padding-bottom:0">
<!-- wp:button {"tagName":"button","className":"gatherpress-rsvp--trigger-update"} -->
<div class="wp-block-button gatherpress-rsvp--trigger-update"><button class="wp-block-button__link wp-element-button">Attend</button></div>
<!-- /wp:button -->
<!-- wp:button {"tagName":"button","className":"is-style-outline gatherpress-modal--trigger-close"} -->
<div class="wp-block-button is-style-outline gatherpress-modal--trigger-close"><button class="wp-block-button__link wp-element-button">Close</button></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:gatherpress/modal-content -->
</div>
<!-- /wp:gatherpress/modal -->
</div>
<!-- /wp:gatherpress/modal-manager -->';
}

/**
 * Block markup for the "past" RSVP state.
 */
function get_rsvp_past_markup(): string {
	return '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"tagName":"button","width":100,"className":"gatherpress--is-disabled"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 gatherpress--is-disabled"><button class="wp-block-button__link wp-element-button">Past Event</button></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->';
}
