<?php

/**
 * This file is the plugin's admin notifier.
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

class AdminNotices {

	public static function authEmptyError( $system_name = '' ) {
		$class = 'notice-error';

		switch ( $system_name ) {
			case 'responder_live':
				$message = esc_html__( 'כדי להשתמש בתוסף יש להעתיק את הטוקן של תוסף הטפסים של וורדפרס ממערכת רב מסר . הטוקן נמצא בהגדרות המערכת בלשונית חיבורים חיצוניים.', 'responder' );
				break;
			case 'responder':
			default:
				$message = esc_html__( 'כדי להשתמש בתוסף יש למלא את שדות מפתח וסוד', 'responder' );
				break;
		}

		self::echoGenericNotice( $class, $message );
	}

	public static function authError( $message = '', $add_error = '' ) {
		$class = 'notice-error';

		if ( empty( $message ) ) {
			$message = esc_html__( 'שגיאה! הטוקן או הקוד הסודי אינם תקינים, או אין גישה לאינטרנט.', 'responder' );
		}

		if ( ! empty( $add_error ) ) {
			$message .= ' ' . sprintf(
			/* translators: %s is an error message */
				esc_html__( 'שגיאה בפועל: %s', 'responder' ),
				$add_error
			);
		}

		self::echoGenericNotice( $class, $message );
	}

	public static function authSuccess() {
		$class   = 'notice-success is-dismissible';
		$message = esc_html__( 'חשבון רב מסר שלך מחובר בהצלחה!', 'responder' );

		self::echoGenericNotice( $class, $message );
	}

	private static function echoGenericNotice( $class = '', $message = '' ) {
		$class_name = 'notice';

		if ( ! empty( $class ) ) {
			$class_name .= ' ' . $class;
		}

		if ( ! empty( $message ) ) {
			echo '<div class="' . esc_attr( $class_name ) . '">';
			echo '<p>' . esc_html( $message ) . '</p>';
			echo '</div>';
		}
	}

	private static function genericNotice( $class = '', $message = '' ) {
		add_action(
			'admin_notices',
			function () use ( &$class, &$message ) {
				AdminNotices::echoGenericNotice( $class, $message );
			}
		);
	}
}
