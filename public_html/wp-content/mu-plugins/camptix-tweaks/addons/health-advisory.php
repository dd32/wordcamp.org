<?php
namespace WordCamp\CampTix_Tweaks;
use CampTix_Addon;

defined( 'WPINC' ) || die();

class Health_Advisory_Field extends CampTix_Addon {
	const SLUG = 'health-advisory';

	/**
	 * Hook into WordPress and Camptix.
	 */
	public function camptix_init() {
		$wordcamp   = get_wordcamp_post();
		$is_virtual = isset( $wordcamp->meta['Virtual event only'][0] ) && '1' === $wordcamp->meta['Virtual event only'][0];

		if ( $is_virtual ) {
			return; // There's no need to display the advice since online events have no physical contact.
		}

		// Registration field.
		add_action( 'camptix_form_attendee_after_registration_information', array( $this, 'render_registration_notice' ), 15 );
	}

	/**
	 * Render the advisory for the registration form during checkout.
	 */
	public function render_registration_notice() {
		?>

		<table class="tix_tickets_table tix-attendee-form">
			<tr class="tix-row-<?php echo esc_attr( self::SLUG ); ?>">
				<td class="tix-required tix-left" colspan="2">
					<p><?php esc_html_e( 'We invite you to help us make WordCamps a welcome and safe experience for everyone. When planning to attend WordCamp, we recommend that you stay at home if you are sick, or have recently come in contact with someone who is ill.', 'wordcamporg' ); ?></p>
					<p><?php esc_html_e( 'If you see another attendee wearing a sticker requesting that people wear a mask near them, please do wear a mask while within 6 feet (2 meters) of them or keep your distance.', 'wordcamporg' ); ?></p>
				</td>
			</tr>
		</table>

		<?php
	}
}

camptix_register_addon( __NAMESPACE__ . '\Health_Advisory_Field' );
