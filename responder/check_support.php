<?php

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

function RMP_haveCurlSupport() {
	return function_exists( 'curl_init' );
}

function RMP_phpVersionCheck() {
	return version_compare( PHP_VERSION, RAV_MESSER_MIN_PHP_VER, 'ge' );
}

function RMP_metRequirementsErrors() {
	$error_messages = array();

	if ( ! RMP_phpVersionCheck() ) {
		$error_messages[] = sprintf(
		/* translators: %1$s and %1$s are open and close <strong> tag indicators and %3$s is a php version */
			esc_html__( 'תוסף %1$sרב מסר%2$s זקוק לגרסת PHP %3$s מינימום או גרסה עדכנית יותר בשביל לפעול.', 'responder' ),
			'<strong>',
			'</strong>',
			RAV_MESSER_MIN_PHP_VER
		);
	}

	if ( ! RMP_haveCurlSupport() ) {
		$error_messages[] = sprintf(
		/* translators: %1$s and %1$s are open and close <strong> tag inticators */
			esc_html__( 'תוסף %1$sרב מסר%2$s זקוק ל-cURL PHP Extension להיות מותקן בשביל לפעול.', 'responder' ),
			'<strong>',
			'</strong>'
		);
	}

	return implode( '<br />', $error_messages );
}

function RMP_metRequirements() {
	return RMP_phpVersionCheck() && RMP_haveCurlSupport();
}

function RMP_checkSupport() {
	if ( ! RMP_metRequirements() && is_plugin_active( RAV_MESSER_PLUGIN_BASENAME ) ) {
		deactivate_plugins( RAV_MESSER_PLUGIN_BASENAME, true );

		wp_die(
			wp_kses(
				RMP_metRequirementsErrors(),
				array(
					'strong' => array(),
					'br'     => array(),
				)
			),
			'',
			array( 'back_link' => true )
		);
	}
}

add_action( 'admin_init', 'RMP_checkSupport' );
