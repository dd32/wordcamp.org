<?php
/**
 * Implements Events application class
 *
 * @package WordCamp Post Type
 */

namespace WordPress_Community\Applications;

use function WordPress_Community\Applications\Events\render_events_application_form;

require_once dirname( __DIR__ ) . '/wcpt-wordcamp/class-wordcamp-application.php';
require_once dirname( __DIR__ ) . '/views/applications/events/shortcode-application.php';

use Event_Admin;
use WP_Error, WP_Post;
/**
 * Class Events_Application
 *
 * @package WordPress_Events\Applications
 */
class Events_Application extends WordCamp_Application {
	public $post;

	const SHORTCODE_SLUG = 'events-organizer-application';

	/**
	 * Return publicly displayed name of the event
	 *
	 * @return string
	 */
	public static function get_event_label() {
		return __( 'WordPress event', 'wordcamporg' );
	}

	/**
	 * Enqueue scripts and stylesheets
	 */
	public function enqueue_asset() {
		global $post;

		wp_register_style(
			'events-application',
			plugins_url( 'css/applications/events.css', __DIR__ ),
			array( 'wp-community-applications', 'wordcamp-application' ),
			1,
		);

		if ( isset( $post->post_content ) && has_shortcode( $post->post_content, self::SHORTCODE_SLUG ) ) {
			wp_enqueue_style( 'events-application' );
		}
	}

	/**
	 * Render application form
	 *
	 * @param array $countries
	 *
	 * @return null|void
	 */
	public function render_application_form( $countries, $prefilled_fields ) {
		render_events_application_form( $countries, $prefilled_fields );
	}

	/**
	 * Validate the submitted application data
	 *
	 * @param array $unsafe_data
	 *
	 * @return array|\WP_Error
	 */
	public function validate_data( $unsafe_data ) {
		$safe_data   = array();
		$unsafe_data = shortcode_atts( $this->get_default_application_values(), $unsafe_data );

		$required_fields = array(
			'q_first_name',
			'q_last_name',
			'q_email',
			'q_wporg_username',
			'q_event_location',
		);

		foreach ( $unsafe_data as $key => $value ) {
			if ( is_array( $value ) ) {
				$safe_data[ $key ] = array_map( 'sanitize_text_field', $value );
			} else {
				$safe_data[ $key ] = sanitize_text_field( $value );
			}
		}

		foreach ( $required_fields as $field ) {
			if ( empty( $safe_data[ $field ] ) ) {
				return new \WP_Error( 'required_fields', "Please click on your browser's Back button, and fill in all of the required fields." );
			}
		}

		return $safe_data;
	}

	/**
	 * Get the default values for all application fields
	 *
	 * @return array
	 */
	public function get_default_application_values() {
		$values = array(
			'q_first_name'                => '',
			'q_last_name'                 => '',
			'q_email'                     => '',
			'q_wporg_username'            => '',
			'q_slack_username'            => '',
			'q_add1'                      => '',
			'q_add2'                      => '',
			'q_city'                      => '',
			'q_state'                     => '',
			'q_country'                   => '',
			'q_zip'                       => '',
			'q_active_meetup'             => '',
			'q_meetup_url'                => '',
			'q_camps_been_to'             => '',
			'q_role_in_meetup'            => '',
			'q_where_find_online'         => '',
			'q_event_location'            => '',
			'q_event_date'                => '',
			'q_in_person_online'          => '',
			'q_describe_events'           => '',
			'q_describe_goals'            => '',
			'q_describe_event'            => '',
			'q_describe_event_other'      => '',
			'q_how_many_attendees'        => '',
			'q_co_organizer_contact_info' => '',
			'q_event_url'                 => '',
			'q_venues_considering'        => '',
			'q_estimated_cost'            => '',
			'q_raise_money'               => '',
			'q_anything_else'             => '',
		);

		return $values;
	}

	/**
	 * Create a Events post from an application
	 *
	 * @param array $data
	 *
	 * @return int|WP_Error
	 */
	public function create_post( $data ) {
		$wordcamp_user_id = get_user_by( 'email', 'support@wordcamp.org' )->ID;

		// Create the post.
		$user     = wcorg_get_user_by_canonical_names( $data['q_wporg_username'] );
		$statuses = \WordCamp_Loader::get_post_statuses();

		$post = array(
			'post_type'   => self::get_event_type(),
			'post_title'  => esc_html( $data['q_event_location'] ),
			'post_status' => self::get_default_status(),
			'post_author' => is_a( $user, 'WP_User' ) ? $user->ID : $wordcamp_user_id, // Set `wordcamp` as author if supplied username is not valid.
		);

		$post_id = wp_insert_post( $post, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Populate the meta fields.
		add_post_meta( $post_id, '_application_data', $data );

		$organizer_address = <<<ADDRESS
{$data['q_add1']}
{$data['q_add2']}
{$data['q_city']}, {$data['q_state']}, {$data['q_country']}
{$data['q_zip']}
ADDRESS;

		add_post_meta( $post_id, 'Organizer Name', $data['q_first_name'] . ' ' . $data['q_last_name'] );
		add_post_meta( $post_id, 'Email Address', $data['q_email'] );
		add_post_meta( $post_id, 'City', $data['q_event_location'] );
		add_post_meta( $post_id, 'Mailing Address', $organizer_address );
		add_post_meta( $post_id, 'WordPress.org Username', $data['q_wporg_username'] );
		add_post_meta( $post_id, 'Slack', $data['q_slack_username'] );
		add_post_meta( $post_id, 'Date Applied', time() );
		add_post_meta( $post_id, 'Location', $data['q_event_location'] );

		$status_log_id = add_post_meta(
			$post_id,
			'_status_change',
			array(
				'timestamp' => time(),
				'user_id'   => $wordcamp_user_id,
				'message'   => sprintf( '%s &rarr; %s', 'Application', $statuses[ self::get_default_status() ] ),
			)
		);

		// See Event_admin::log_status_changes().
		if ( $status_log_id ) {
			add_post_meta( $post_id, "_status_change_log_{$post['post_type']} $status_log_id", time() );
		}

		$this->post = get_post( $post_id );

		return $post_id;
	}

	/**
	 * Get lead organizer email if set.
	 *
	 * @return null|string
	 */
	public function get_organizer_email() {
		if ( isset( $this->post ) && isset( $this->post->ID ) ) {
			return get_post_meta( $this->post->ID, 'Email Address', true );
		}
	}

	/**
	 * Get meetup location if set
	 *
	 * @return null|string
	 */
	public function get_event_location() {
		if ( isset( $this->post ) && isset( $this->post->ID ) ) {
			return get_post_meta( $this->post->ID, 'City', true );
		}
	}
}
