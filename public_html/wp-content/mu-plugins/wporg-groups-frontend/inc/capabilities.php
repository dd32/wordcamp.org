<?php
/**
 * Capability helpers for the groups frontend mu-plugin.
 *
 * Centralises the "can this user manage events on this group?" check so the
 * block, the routing layer, and the form handler all agree.
 *
 * @package WordCamp\Groups\Frontend
 */

namespace WordCamp\Groups\Frontend\Capabilities;

defined( 'WPINC' ) || die();

/**
 * Whether the current user is allowed to create / edit events on this group.
 *
 * For the first iteration this maps to the existing `edit_others_posts`
 * capability (Editors and Admins). A future PR can introduce a dedicated
 * `gatherpress_organizer` cap and have this helper check for it.
 */
function current_user_can_manage_events(): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	/**
	 * Filters the capability check used by the front-end event management UI.
	 *
	 * @param bool $allowed Whether the current user can manage events.
	 */
	return (bool) apply_filters(
		'wporg_groups_frontend_user_can_manage_events',
		current_user_can( 'edit_others_posts' )
	);
}
