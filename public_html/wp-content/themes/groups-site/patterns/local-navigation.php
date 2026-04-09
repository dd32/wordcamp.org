<?php
/**
 * Title: Local Navigation
 * Slug: groups-site/local-navigation
 * Inserter: no
 *
 * Sticky breadcrumb-style site-title + page navigation bar for inner pages.
 * Must be placed OUTSIDE the header template-part in each template so the
 * sticky positioning works against the viewport, not the header wrapper.
 *
 * @package WordCamp\Groups\Site
 */

namespace WordCamp\Groups\Site\Patterns\LocalNavigation;

defined( 'ABSPATH' ) || exit;

?>

<!-- wp:wporg/local-navigation-bar {"backgroundColor":"white","style":{"elements":{"link":{"color":{"text":"var:preset|color|charcoal-1"},":hover":{"color":{"text":"var:preset|color|charcoal-1"}}}}},"textColor":"charcoal-1","fontSize":"small"} -->

	<!-- wp:site-title {"level":0,"fontSize":"small"} /-->

	<!-- wp:navigation {"icon":"menu","overlayBackgroundColor":"white","overlayTextColor":"charcoal-1","layout":{"type":"flex","orientation":"horizontal"},"fontSize":"small"} -->
		<!-- wp:page-list /-->
	<!-- /wp:navigation -->

<!-- /wp:wporg/local-navigation-bar -->
