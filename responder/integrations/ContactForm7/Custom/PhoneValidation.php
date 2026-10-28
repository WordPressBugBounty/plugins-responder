<?php namespace RavMesser\Integrations\ContactForm7\Custom;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\Helpers as PluginHelpers;
use RavMesser\Integrations\ContactForm7\DataManager as ContactForm7DataManager;
use WPCF7_ContactForm;

class PhoneValidation {

	public static function validate( $result, $tag ) {
		// Only forms connected to רב מסר are checked; other CF7 forms keep CF7's own check (PR1-I8).
		$contact_form = class_exists( 'WPCF7_ContactForm' ) ? WPCF7_ContactForm::get_current() : null;

		if ( empty( $contact_form ) || ! ContactForm7DataManager::isFormConnected( $contact_form->id() ) ) {
			return $result;
		}

		$name  = $tag->name;
		$value = '';

		if ( isset( $_POST[ $name ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
		}

		// The number is normalized (digits only, +972 → 0) before it is checked, the same way it is sent.
		if ( ! empty( $value ) && ! PluginHelpers::isValidIsraeliPhone( $value ) ) {
			$result->invalidate( $tag, esc_html__( 'יש להזין מספר טלפון ישראלי תקין', 'responder' ) );
		}

		return $result;
	}
}
