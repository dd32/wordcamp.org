<?php
/**
 * Title: Upcoming events (cards)
 * Slug: groups-site/upcoming-events-cards
 * Categories: groups-site
 * Inserter: no
 *
 * Renders a compact card grid of the next 3 upcoming events on the
 * current site. Used on the front page just below the hero. Both this
 * pattern and the archive's `archive-upcoming-events-cards` pattern
 * share their card markup via the `Groups_Site\Event_Cards` helper —
 * see `inc/event-cards.php`.
 *
 * @package Groups_Site
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\GatherPress\Core\Event_Query' ) || ! class_exists( '\GatherPress\Core\Event' ) ) {
	return;
}

$groups_site_query = \GatherPress\Core\Event_Query::get_instance()->get_upcoming_events( 3 );

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
		'classes'       => 'groups-site-event-cards--compact',
		'excerpt_words' => 22,
	)
);

wp_reset_postdata();
