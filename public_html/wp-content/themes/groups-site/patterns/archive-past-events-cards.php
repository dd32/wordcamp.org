<?php
/**
 * Title: Archive — past events (expanded cards)
 * Slug: groups-site/archive-past-events-cards
 * Categories: groups-site
 * Inserter: no
 *
 * Renders the "Past" section of the events archive page as an expanded
 * card grid, visually muted to differentiate from upcoming events.
 *
 * @package Groups_Site
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\GatherPress\Core\Event_Query' ) || ! class_exists( '\GatherPress\Core\Event' ) ) {
	return;
}

$groups_site_query = \GatherPress\Core\Event_Query::get_instance()->get_past_events( 50 );

if ( ! $groups_site_query->have_posts() ) {
	?>
	<!-- wp:paragraph {"textColor":"charcoal-4"} -->
	<p class="has-charcoal-4-color has-text-color">No past events to show.</p>
	<!-- /wp:paragraph -->
	<?php
	wp_reset_postdata();
	return;
}

\Groups_Site\Event_Cards\render_event_cards(
	$groups_site_query,
	array(
		'classes'       => 'groups-site-event-cards--expanded',
		'excerpt_words' => 50,
		'is_past'       => true,
	)
);

wp_reset_postdata();
