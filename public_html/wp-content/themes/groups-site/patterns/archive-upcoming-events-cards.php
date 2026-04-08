<?php
/**
 * Title: Archive — upcoming events (expanded cards)
 * Slug: groups-site/archive-upcoming-events-cards
 * Categories: groups-site
 * Inserter: no
 *
 * Renders the "Upcoming" section of the events archive page as an
 * expanded card grid (more padding, longer description preview) showing
 * up to 50 events. Sister pattern to `archive-past-events-cards`.
 *
 * @package Groups_Site
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\GatherPress\Core\Event_Query' ) || ! class_exists( '\GatherPress\Core\Event' ) ) {
	return;
}

$groups_site_query = \GatherPress\Core\Event_Query::get_instance()->get_upcoming_events( 50 );

if ( ! $groups_site_query->have_posts() ) {
	?>
	<!-- wp:paragraph {"textColor":"charcoal-4"} -->
	<p class="has-charcoal-4-color has-text-color">No upcoming events scheduled. Check back soon.</p>
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
	)
);

wp_reset_postdata();
