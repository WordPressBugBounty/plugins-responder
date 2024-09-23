<?php namespace RavMesser\Integrations\ContactForm7\Custom;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\Helpers as PluginHelpers;

class PhoneValidation {

	const PHONE_REGEX = '/^(?:050|051|052|053|054|055|057|058|02|03|04|08|09)\d{7}$/';

	public static function validate( $result, $tag ) {
		$name  = $tag->name;
		$value = '';

		if ( isset( $_POST[ $name ] ) ) {
			$value = sanitize_text_field( $_POST[ $name ] );
		}

		if ( ! empty( $value ) && ! preg_match( self::PHONE_REGEX, $value ) ) {
			$result->invalidate( $tag, esc_html__( 'יש להזין מספר טלפון ישראלי תקין', 'responder' ) );
		}

		return $result;
	}
}
