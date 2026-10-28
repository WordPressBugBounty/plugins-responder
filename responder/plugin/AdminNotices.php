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

	/**
	 * Wrong credentials. The visitor-facing text is one clear Hebrew message per system:
	 * no English API text and never the typed value. The raw API reply goes to the logs
	 * only (Debugger::logConnectionError masks the saved credentials).
	 */
	public static function authError( $system_name = '', $raw_error = '' ) {
		$class = 'notice-error';

		switch ( $system_name ) {
			case 'responder_live':
				$message = esc_html__( 'לא הצלחנו להתחבר לרב מסר. בדקו שהעתקתם את ה-Token המלא מהגדרות ← חיבורים חיצוניים ברב מסר ונסו שוב.', 'responder' );
				break;
			case 'responder':
			default:
				$message = esc_html__( 'לא הצלחנו להתחבר לרב מסר הישנה. בדקו שהעתקתם את המפתח והסוד המלאים ונסו שוב.', 'responder' );

				// PR1-I27: the key is right but the account is closed - checking the key would not help.
				if ( is_scalar( $raw_error ) && false !== stripos( (string) $raw_error, 'Account is disabled' ) ) {
					$message = esc_html__( 'החשבון ברב מסר הישנה לא פעיל, ולכן הטפסים לא שולחים אליו נרשמים. כדי להפעיל אותו מחדש פנו לתמיכה של רב מסר.', 'responder' );
				}
				break;
		}

		if ( ! empty( $raw_error ) && self::isSavingSystem( $system_name ) ) {
			Debugger::logConnectionError( $system_name, $raw_error );
		}

		if ( empty( $raw_error ) ) {
			self::echoGenericNotice( $class, $message );
			return;
		}

		// Closed by default: the admin can open it and copy the API reply for support. Saved credentials are masked.
		$details = is_scalar( $raw_error ) ? (string) $raw_error : wp_json_encode( $raw_error, JSON_UNESCAPED_UNICODE );
		$details = Debugger::maskCredentials( $details );

		echo '<div class="notice ' . esc_attr( $class ) . '">';
		echo '<p>' . esc_html( $message ) . '</p>';
		echo '<details class="rmp-error-details"><summary>' . esc_html__( 'פרטים טכניים (לשליחה לתמיכה)', 'responder' ) . '</summary>';
		echo '<code dir="ltr">' . esc_html( $details ) . '</code>';
		echo '</details>';
		echo '</div>';
	}

	/**
	 * True only on the request that saves this system's connection form, so an error is
	 * logged once per save and not on every visit to the settings page.
	 */
	private static function isSavingSystem( $system_name ) {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return false;
		}

		if ( 'connection_settings' !== ( $_POST['rmp_action'] ?? '' ) ) {
			return false;
		}

		$posted = isset( $_POST['responder'] ) && is_array( $_POST['responder'] ) ? $_POST['responder'] : array();
		$field  = 'responder_live' === $system_name ? 'responder_live_user_token' : 'Responder_Plugin_EnterUsername';

		return array_key_exists( $field, $posted );
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
