<?php namespace RavMesser\Integrations\ContactForm7\Custom;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\Helpers as PluginHelpers;

class ResHiddenTag {

	public static function callback( $tag ) {
		if ( empty( $tag->name ) ) {
			return '';
		}

		$atts = array();

		$class         = wpcf7_form_controls_class( $tag->type );
		$atts['class'] = $tag->get_class_option( $class );
		$atts['id']    = $tag->get_id_option();

		$value              = (string) reset( $tag->values );
		$value              = $tag->get_default_option( $value );
		$atts['data-value'] = $value;

		if ( self::isGetValueOptionExist( $tag ) ) {
			$value = PluginHelpers::getPostGetVariable( $value, PluginHelpers::SANITIZE_TEXT_FIELD );
		}

		$atts['name']  = $tag->name;
		$atts['type']  = 'hidden';
		$atts['value'] = $value;

		$atts = wpcf7_format_atts( $atts );
		$html = sprintf( '<input %s />', $atts );

		return wp_kses(
			$html,
			array(
				'input' => array(
					'name'       => array(),
					'type'       => array(),
					'id'         => array(),
					'class'      => array(),
					'data-value' => array(),
					'value'      => array(),
				),
			)
		);
	}

	public static function register() {
		if ( ! function_exists( 'wpcf7_add_form_tag' ) ) {
			return;
		}

		wpcf7_add_form_tag(
			'res_hidden',
			array( __CLASS__, 'callback' ),
			array(
				'name-attr'      => true,
				'display-hidden' => true,
			)
		);
	}

	private static function isGetValueOptionExist( $tag ) {
		$tag     = (array) $tag;
		$options = PluginHelpers::getVal( $tag, 'options', array() );

		foreach ( $options as $option ) {
			if ( strpos( $option, 'getvalue' ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
