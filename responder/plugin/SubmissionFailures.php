<?php

/**
 * Form submissions that did not reach רב מסר / רב מסר הישנה (PR1-I10).
 *
 * When a submission fails (wrong / expired Token, wrong key, disabled account, no reply):
 * - the visitor gets an error message (each integration shows visitorMessage()),
 * - the lead is saved in the Responder Debug log (Debugger::onSubmissionFailed),
 * - the site admin sees a notice in wp-admin and gets one email a day at most.
 * The notice is removed by the next successful submission to the same system, or by "סגירה".
 *
 * PR1-I15: leads sent to an Elementor widget form that no longer exists get their own notice / email
 * (key FORM_NOT_FOUND_KEY) - only "סגירה" removes it, since a submission to another form does not fix that page.
 *
 * PR1-I32: leads sent from a form with no list get their own notice / email too (key NO_LIST_KEY), which says to
 * choose a list in the form - the connection is fine. A successful submission from another form does not remove it;
 * "סגירה" does, and so does saving "טפסים לאלמנטור" (the save is refused while a form has no list).
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\Helpers as PluginHelpers;

class SubmissionFailures {

	const OPTION_NAME    = 'rmp_submission_failures';
	const DISMISS_ACTION = 'rmp_dismiss_submission_failure';
	const NONCE_NAME     = 'rmp_nonce';
	const EMAIL_INTERVAL = DAY_IN_SECONDS;
	const MAX_ERROR_LEN  = 500;
	const FORM_NOT_FOUND_KEY = 'form_not_found';
	const NO_LIST_KEY        = 'no_list';
	const NO_LIST_ERROR      = 'no_list';
	const MAX_NO_LIST_FORMS  = 10;

	public static function register() {
		add_action( 'RMP_submission_failed', array( __CLASS__, 'onFailed' ), 20, 4 );
		add_action( 'RMP_submission_succeeded', array( __CLASS__, 'onSucceeded' ), 10, 1 );
		add_action( 'RMP_form_not_found', array( __CLASS__, 'onFormNotFound' ), 20, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'adminNotice' ) );
		add_action( 'admin_init', array( __CLASS__, 'handleDismiss' ) );
	}

	/**
	 * The text the visitor sees when the submission did not reach רב מסר
	 * (same text as the Elementor widget's existing "server_error" message).
	 */
	public static function visitorMessage() {
		return esc_html__( 'התקבלה שגיאה בזמן השליחה של הטופס, נסו שוב עוד כמה דקות', 'responder' );
	}

	/**
	 * The text the visitor sees when the form no longer exists (PR1-I15) - trying again will not help.
	 */
	public static function formNotFoundMessage() {
		return esc_html__( 'הטופס הזה אינו פעיל יותר ולכן הפרטים לא נשלחו.', 'responder' );
	}

	public static function systemLabel( $system_name = '' ) {
		return 'responder' === $system_name ? 'רב מסר הישנה' : 'רב מסר';
	}

	/**
	 * Short label of the form, e.g. "Contact Form 7 #912".
	 */
	public static function formLabel( $context = array() ) {
		$label = trim( PluginHelpers::getVal( $context, 'integration', '' ) . ' ' . PluginHelpers::getVal( $context, 'form', '' ) );

		return '' === $label ? '-' : $label;
	}

	public static function errorToText( $error ) {
		$text = is_scalar( $error ) ? (string) $error : wp_json_encode( $error, JSON_UNESCAPED_UNICODE );
		$text = Debugger::maskCredentials( $text );

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, self::MAX_ERROR_LEN );
		}

		return substr( $text, 0, self::MAX_ERROR_LEN );
	}

	public static function onFailed( $system_name, $error, $subscriber_details = array(), $context = array() ) {
		// PR1-I32: a form with no list is a form problem, not a connection problem - kept apart.
		if ( self::isNoListError( $error ) ) {
			self::onNoList( $system_name, $error, $context );
			return;
		}

		$failures = self::getAll();
		$now      = time();
		$failure  = isset( $failures[ $system_name ] ) ? $failures[ $system_name ] : array(
			'count'      => 0,
			'first_time' => $now,
			'last_email' => 0,
		);

		$failure['count']      = (int) $failure['count'] + 1;
		$failure['last_time']  = $now;
		$failure['last_error'] = self::errorToText( $error );
		$failure['last_form']  = self::formLabel( $context );

		if ( $now - (int) $failure['last_email'] >= self::EMAIL_INTERVAL ) {
			if ( self::sendEmail( $system_name, $failure ) ) {
				$failure['last_email'] = $now;
			}
		}

		$failures[ $system_name ] = $failure;
		update_option( self::OPTION_NAME, $failures, false );
	}

	public static function isNoListError( $error ) {
		return is_string( $error ) && 0 === strpos( $error, self::NO_LIST_ERROR );
	}

	/**
	 * PR1-I32: a lead sent from a form with no list - own notice / daily email with the forms to fix.
	 */
	public static function onNoList( $system_name, $error, $context = array() ) {
		$failures = self::getAll();
		$now      = time();
		$failure  = isset( $failures[ self::NO_LIST_KEY ] ) ? $failures[ self::NO_LIST_KEY ] : array(
			'count'      => 0,
			'first_time' => $now,
			'last_email' => 0,
			'forms'      => array(),
		);

		$form     = self::formLabel( $context );
		$forms    = isset( $failure['forms'] ) && is_array( $failure['forms'] ) ? $failure['forms'] : array();

		if ( ! in_array( $form, $forms, true ) ) {
			$forms[] = $form;
		}

		$failure['count']       = (int) $failure['count'] + 1;
		$failure['last_time']   = $now;
		$failure['last_error']  = self::errorToText( $error );
		$failure['last_form']   = $form;
		$failure['last_system'] = (string) $system_name;
		$failure['forms']       = array_slice( $forms, -self::MAX_NO_LIST_FORMS );

		if ( $now - (int) $failure['last_email'] >= self::EMAIL_INTERVAL ) {
			if ( self::sendNoListEmail( $failure ) ) {
				$failure['last_email'] = $now;
			}
		}

		$failures[ self::NO_LIST_KEY ] = $failure;
		update_option( self::OPTION_NAME, $failures, false );
	}

	/**
	 * PR1-I32: "טפסים לאלמנטור" was saved - the save is refused while a form has no list, so every form has one now.
	 */
	public static function clearNoList() {
		self::onSucceeded( self::NO_LIST_KEY );
	}

	/**
	 * A lead sent to a form that is no longer in "טפסים לאלמנטור" (PR1-I15) - same notice / daily email as a failed submission,
	 * with its own text: the fix is on the page (widget / cache), not in the connection.
	 */
	public static function onFormNotFound( $form_id = '', $fields = array(), $context = array() ) {
		$failures = self::getAll();
		$now      = time();
		$failure  = isset( $failures[ self::FORM_NOT_FOUND_KEY ] ) ? $failures[ self::FORM_NOT_FOUND_KEY ] : array(
			'count'      => 0,
			'first_time' => $now,
			'last_email' => 0,
		);

		$failure['count']        = (int) $failure['count'] + 1;
		$failure['last_time']    = $now;
		$failure['last_form']    = self::formLabel( $context );
		$failure['last_form_id'] = (string) $form_id;
		$failure['last_page']    = sanitize_url( PluginHelpers::getVal( $_SERVER, 'HTTP_REFERER', '' ) );
		$failure['last_error']   = 'Form not found: ' . $form_id;

		if ( $now - (int) $failure['last_email'] >= self::EMAIL_INTERVAL ) {
			if ( self::sendFormNotFoundEmail( $failure ) ) {
				$failure['last_email'] = $now;
			}
		}

		$failures[ self::FORM_NOT_FOUND_KEY ] = $failure;
		update_option( self::OPTION_NAME, $failures, false );
	}

	public static function onSucceeded( $system_name ) {
		$failures = self::getAll();

		if ( isset( $failures[ $system_name ] ) ) {
			unset( $failures[ $system_name ] );
			update_option( self::OPTION_NAME, $failures, false );
		}
	}

	public static function getAll() {
		$failures = get_option( self::OPTION_NAME, array() );

		return is_array( $failures ) ? $failures : array();
	}

	public static function hasFailures() {
		$failures = self::getAll();

		return ! empty( $failures );
	}

	public static function adminNotice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( self::getAll() as $system_name => $failure ) {
			if ( self::FORM_NOT_FOUND_KEY === $system_name ) {
				self::formNotFoundNotice( $failure );
				continue;
			}

			if ( self::NO_LIST_KEY === $system_name ) {
				self::noListNotice( $failure );
				continue;
			}

			$label       = self::systemLabel( $system_name );
			$settings    = admin_url( 'admin.php?page=' . RAV_MESSER_MENU_SLUG );
			$log         = admin_url( 'edit.php?post_type=' . Debugger::POST_TYPE );
			// Own nonce name, so a page that already has "_wpnonce" in its URL (e.g. update.php) keeps it.
			$dismiss_url = wp_nonce_url(
				add_query_arg( self::DISMISS_ACTION, rawurlencode( $system_name ) ),
				self::DISMISS_ACTION . '_' . $system_name,
				self::NONCE_NAME
			);

			$text = sprintf(
				/* translators: 1: system name, 2: number of submissions, 3: first failure time, 4: last failure time, 5: form */
				esc_html__( 'שליחת פניות מהטפסים ל%1$s נכשלה. פניות שלא נשמרו: %2$d, מאז %3$s. האחרונה: %4$s, %5$s. המבקרים קיבלו הודעת שגיאה, ופרטי הפניות נשמרו ביומן Responder Debug.', 'responder' ),
				$label,
				(int) $failure['count'],
				self::formatTime( $failure['first_time'] ),
				self::formatTime( $failure['last_time'] ),
				$failure['last_form']
			);

			echo '<div class="notice notice-error rmp-submission-failure">';
			echo '<p><strong>' . esc_html__( 'רב מסר:', 'responder' ) . '</strong> ' . esc_html( $text ) . '</p>';

			if ( ! empty( $failure['last_email'] ) ) {
				echo '<p>' . esc_html(
					sprintf(
						/* translators: 1: email address, 2: time */
						__( 'התראה נשלחה במייל אל %1$s ב-%2$s (פעם אחת ביום לכל היותר).', 'responder' ),
						implode( ', ', self::emailRecipients() ),
						self::formatTime( $failure['last_email'] )
					)
				) . '</p>';
			}
			echo '<p>';
			echo '<a class="button button-primary" href="' . esc_url( $settings ) . '">' . esc_html__( 'בדיקת החיבור', 'responder' ) . '</a> ';
			echo '<a class="button" href="' . esc_url( $log ) . '">' . esc_html__( 'יומן הפניות', 'responder' ) . '</a> ';
			echo '<a class="button-link" href="' . esc_url( $dismiss_url ) . '">' . esc_html__( 'סגירה', 'responder' ) . '</a>';
			echo '</p>';
			echo '<details class="rmp-error-details"><summary>' . esc_html__( 'פרטים טכניים (לשליחה לתמיכה)', 'responder' ) . '</summary>';
			echo '<code dir="ltr">' . esc_html( $failure['last_error'] ) . '</code>';
			echo '</details>';
			echo '</div>';
		}
	}

	/**
	 * PR1-I32: wp-admin notice for leads sent from a form with no list - the fix is in the form, not in the connection.
	 */
	private static function noListNotice( $failure ) {
		$log         = admin_url( 'edit.php?post_type=' . Debugger::POST_TYPE );
		$forms_url   = SettingsPage::getUrl( '#plugin_config-elementor' );
		$dismiss_url = wp_nonce_url(
			add_query_arg( self::DISMISS_ACTION, self::NO_LIST_KEY ),
			self::DISMISS_ACTION . '_' . self::NO_LIST_KEY,
			self::NONCE_NAME
		);
		$forms       = isset( $failure['forms'] ) && is_array( $failure['forms'] ) ? $failure['forms'] : array( $failure['last_form'] );

		$text = sprintf(
			/* translators: 1: forms, 2: number of submissions, 3: first time, 4: last time */
			esc_html__( 'לא נבחרה רשימה ברב מסר בטופס: %1$s. הפניות מהטופס לא נשלחו לרב מסר (החיבור תקין). פניות: %2$d, מאז %3$s. האחרונה: %4$s. המבקרים קיבלו הודעת שגיאה, ופרטי הפניות נשמרו ביומן Responder Debug. יש לבחור רשימה בטופס ב"טפסים לאלמנטור" ולשמור.', 'responder' ),
			implode( ', ', $forms ),
			(int) $failure['count'],
			self::formatTime( $failure['first_time'] ),
			self::formatTime( $failure['last_time'] )
		);

		echo '<div class="notice notice-error rmp-submission-failure rmp-no-list-failure">';
		echo '<p><strong>' . esc_html__( 'רב מסר:', 'responder' ) . '</strong> ' . esc_html( $text ) . '</p>';

		if ( ! empty( $failure['last_email'] ) ) {
			echo '<p>' . esc_html(
				sprintf(
					/* translators: 1: email address, 2: time */
					__( 'התראה נשלחה במייל אל %1$s ב-%2$s (פעם אחת ביום לכל היותר).', 'responder' ),
					implode( ', ', self::emailRecipients() ),
					self::formatTime( $failure['last_email'] )
				)
			) . '</p>';
		}

		echo '<p>';
		echo '<a class="button button-primary" href="' . esc_url( $forms_url ) . '">' . esc_html__( 'טפסים לאלמנטור', 'responder' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( $log ) . '">' . esc_html__( 'יומן הפניות', 'responder' ) . '</a> ';
		echo '<a class="button-link" href="' . esc_url( $dismiss_url ) . '">' . esc_html__( 'סגירה', 'responder' ) . '</a>';
		echo '</p>';
		echo '<details class="rmp-error-details"><summary>' . esc_html__( 'פרטים טכניים (לשליחה לתמיכה)', 'responder' ) . '</summary>';
		echo '<code dir="ltr">' . esc_html( $failure['last_error'] ) . '</code>';
		echo '</details>';
		echo '</div>';
	}

	/**
	 * PR1-I15: wp-admin notice for leads sent to a form that no longer exists.
	 */
	private static function formNotFoundNotice( $failure ) {
		$log         = admin_url( 'edit.php?post_type=' . Debugger::POST_TYPE );
		$forms       = admin_url( 'admin.php?page=' . RAV_MESSER_MENU_SLUG );
		$dismiss_url = wp_nonce_url(
			add_query_arg( self::DISMISS_ACTION, self::FORM_NOT_FOUND_KEY ),
			self::DISMISS_ACTION . '_' . self::FORM_NOT_FOUND_KEY,
			self::NONCE_NAME
		);
		$page        = PluginHelpers::getVal( $failure, 'last_page', '' );

		$text = sprintf(
			/* translators: 1: number of submissions, 2: first time, 3: last time, 4: form ID */
			esc_html__( 'פניות נשלחו מטופס של אלמנטור שכבר לא קיים ב"טפסים לאלמנטור" (נמחק, או שהעמוד מוצג מהמטמון עם טופס ישן), ולכן לא נשלחו לרב מסר. פניות: %1$d, מאז %2$s. האחרונה: %3$s, טופס %4$s. המבקרים קיבלו הודעה שהטופס אינו פעיל, ופרטי הפניות נשמרו ביומן Responder Debug.', 'responder' ),
			(int) $failure['count'],
			self::formatTime( $failure['first_time'] ),
			self::formatTime( $failure['last_time'] ),
			PluginHelpers::getVal( $failure, 'last_form_id', '-' )
		);

		echo '<div class="notice notice-error rmp-submission-failure rmp-form-not-found">';
		echo '<p><strong>' . esc_html__( 'רב מסר:', 'responder' ) . '</strong> ' . esc_html( $text ) . '</p>';

		if ( ! empty( $page ) ) {
			echo '<p>' . esc_html__( 'העמוד:', 'responder' ) . ' <a href="' . esc_url( $page ) . '" dir="ltr" target="_blank" rel="noopener">' . esc_html( $page ) . '</a> – '
				. esc_html__( 'יש להסיר ממנו את הווידג\'ט או לבחור בו טופס קיים, ולנקות את מטמון העמוד.', 'responder' ) . '</p>';
		}

		if ( ! empty( $failure['last_email'] ) ) {
			echo '<p>' . esc_html(
				sprintf(
					/* translators: 1: email address, 2: time */
					__( 'התראה נשלחה במייל אל %1$s ב-%2$s (פעם אחת ביום לכל היותר).', 'responder' ),
					implode( ', ', self::emailRecipients() ),
					self::formatTime( $failure['last_email'] )
				)
			) . '</p>';
		}

		echo '<p>';
		echo '<a class="button button-primary" href="' . esc_url( $log ) . '">' . esc_html__( 'יומן הפניות', 'responder' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( $forms ) . '">' . esc_html__( 'טפסים לאלמנטור', 'responder' ) . '</a> ';
		echo '<a class="button-link" href="' . esc_url( $dismiss_url ) . '">' . esc_html__( 'סגירה', 'responder' ) . '</a>';
		echo '</p>';
		echo '</div>';
	}

	public static function handleDismiss() {
		if ( empty( $_GET[ self::DISMISS_ACTION ] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$system_name = sanitize_key( wp_unslash( $_GET[ self::DISMISS_ACTION ] ) );

		check_admin_referer( self::DISMISS_ACTION . '_' . $system_name, self::NONCE_NAME );
		self::onSucceeded( $system_name );

		// Do not go back to one-time action pages (plugin upload / update): they would run again or show an error.
		global $pagenow;
		$redirect = in_array( $pagenow, array( 'update.php', 'update-core.php' ), true )
			? admin_url( 'plugins.php' )
			: remove_query_arg( array( self::DISMISS_ACTION, self::NONCE_NAME ) );

		wp_safe_redirect( $redirect );
		exit;
	}

	private static function formatTime( $timestamp ) {
		return wp_date( get_option( 'date_format' ) . ' H:i', (int) $timestamp );
	}

	/**
	 * Who gets the alert: the site address (Settings -> General) and every administrator user,
	 * without duplicates - on many sites the site address is an old / hosting address nobody reads.
	 */
	public static function emailRecipients() {
		$recipients = array( get_option( 'admin_email' ) );
		$admins     = get_users(
			array(
				'role'   => 'administrator',
				'fields' => array( 'user_email' ),
				'number' => 10,
			)
		);

		foreach ( $admins as $admin ) {
			$recipients[] = $admin->user_email;
		}

		$recipients = array_map( 'sanitize_email', array_map( 'strtolower', array_filter( (array) $recipients ) ) );

		return array_values( array_unique( array_filter( $recipients, 'is_email' ) ) );
	}

	/**
	 * One email to the site address and the administrators. No lead details in the email - they are in the log.
	 */
	private static function sendEmail( $system_name, $failure ) {
		$to = self::emailRecipients();

		if ( empty( $to ) || ! function_exists( 'wp_mail' ) ) {
			return false;
		}

		$label   = self::systemLabel( $system_name );
		$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject = sprintf(
			/* translators: 1: site name, 2: system name */
			__( '[%1$s] פניות מהטפסים לא נשמרו ב%2$s', 'responder' ),
			$site,
			$label
		);

		$settings_url = admin_url( 'admin.php?page=' . RAV_MESSER_MENU_SLUG );
		$log_url      = admin_url( 'edit.php?post_type=' . Debugger::POST_TYPE );
		$p            = '<p style="margin:0 0 12px 0;">';

		// HTML with dir="rtl" so mail apps show the Hebrew right-to-left (plain text came out reversed).
		$body  = '<div dir="rtl" style="direction:rtl;text-align:right;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#1d2327;">';
		$body .= $p . esc_html__( 'שלום,', 'responder' ) . '</p>';
		$body .= $p . esc_html(
			sprintf(
				/* translators: %s: system name */
				__( 'לא הצליחה שליחת פנייה מטופס באתר אל %s:', 'responder' ),
				$label
			)
		) . ' <a href="' . esc_url( home_url() ) . '" dir="ltr">' . esc_html( home_url() ) . '</a></p>';
		$body .= '<table dir="rtl" cellpadding="4" cellspacing="0" style="border-collapse:collapse;margin:0 0 12px 0;">';
		$body .= '<tr><td style="font-weight:bold;">' . esc_html__( 'זמן:', 'responder' ) . '</td><td>' . esc_html( self::formatTime( $failure['last_time'] ) ) . '</td></tr>';
		$body .= '<tr><td style="font-weight:bold;">' . esc_html__( 'טופס:', 'responder' ) . '</td><td><span dir="ltr">' . esc_html( $failure['last_form'] ) . '</span></td></tr>';
		$body .= '<tr><td style="font-weight:bold;vertical-align:top;">' . esc_html__( 'השגיאה:', 'responder' ) . '</td><td><code dir="ltr" style="direction:ltr;unicode-bidi:embed;">' . esc_html( $failure['last_error'] ) . '</code></td></tr>';
		$body .= '</table>';
		$body .= $p . esc_html__( 'המבקר קיבל הודעה שהשליחה נכשלה, ופרטי הפנייה נשמרו ביומן Responder Debug באתר.', 'responder' ) . '</p>';
		$body .= $p . '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'בדיקת החיבור בלשונית "חיבור לרב מסר"', 'responder' ) . '</a>'
			. ' &nbsp;|&nbsp; <a href="' . esc_url( $log_url ) . '">' . esc_html__( 'יומן הפניות', 'responder' ) . '</a></p>';
		$body .= '<p style="margin:16px 0 0 0;color:#646970;font-size:12px;">' . esc_html__( 'הודעה זו נשלחת פעם אחת ביום לכל היותר, כל עוד השליחה נכשלת.', 'responder' ) . '<br>'
			. esc_html__( 'תוסף רב מסר לוורדפרס', 'responder' ) . '</p>';
		$body .= '</div>';

		return (bool) wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/**
	 * PR1-I15: one email a day at most while leads keep reaching a form that no longer exists. No lead details - they are in the log.
	 */
	private static function sendFormNotFoundEmail( $failure ) {
		$to = self::emailRecipients();

		if ( empty( $to ) || ! function_exists( 'wp_mail' ) ) {
			return false;
		}

		$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] פניות נשלחו מטופס שכבר לא קיים ולא נשמרו ברב מסר', 'responder' ),
			$site
		);

		$log_url = admin_url( 'edit.php?post_type=' . Debugger::POST_TYPE );
		$page    = PluginHelpers::getVal( $failure, 'last_page', '' );
		$p       = '<p style="margin:0 0 12px 0;">';

		$body  = '<div dir="rtl" style="direction:rtl;text-align:right;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#1d2327;">';
		$body .= $p . esc_html__( 'שלום,', 'responder' ) . '</p>';
		$body .= $p . esc_html__( 'פנייה נשלחה מטופס של אלמנטור שכבר לא קיים ב"טפסים לאלמנטור" באתר, ולכן לא נשלחה לרב מסר:', 'responder' )
			. ' <a href="' . esc_url( home_url() ) . '" dir="ltr">' . esc_html( home_url() ) . '</a></p>';
		$body .= '<table dir="rtl" cellpadding="4" cellspacing="0" style="border-collapse:collapse;margin:0 0 12px 0;">';
		$body .= '<tr><td style="font-weight:bold;">' . esc_html__( 'זמן:', 'responder' ) . '</td><td>' . esc_html( self::formatTime( $failure['last_time'] ) ) . '</td></tr>';
		$body .= '<tr><td style="font-weight:bold;">' . esc_html__( 'טופס:', 'responder' ) . '</td><td><span dir="ltr">' . esc_html( PluginHelpers::getVal( $failure, 'last_form_id', '-' ) ) . '</span></td></tr>';

		if ( ! empty( $page ) ) {
			$body .= '<tr><td style="font-weight:bold;">' . esc_html__( 'העמוד:', 'responder' ) . '</td><td><a href="' . esc_url( $page ) . '" dir="ltr">' . esc_html( $page ) . '</a></td></tr>';
		}

		$body .= '</table>';
		$body .= $p . esc_html__( 'כנראה שהטופס נמחק, או שהעמוד מוצג מהמטמון עם טופס ישן. יש להסיר את הווידג\'ט מהעמוד או לבחור בו טופס קיים, ולנקות את מטמון העמוד.', 'responder' ) . '</p>';
		$body .= $p . esc_html__( 'המבקר קיבל הודעה שהטופס אינו פעיל, ופרטי הפנייה נשמרו ביומן Responder Debug באתר.', 'responder' ) . '</p>';
		$body .= $p . '<a href="' . esc_url( $log_url ) . '">' . esc_html__( 'יומן הפניות', 'responder' ) . '</a></p>';
		$body .= '<p style="margin:16px 0 0 0;color:#646970;font-size:12px;">' . esc_html__( 'הודעה זו נשלחת פעם אחת ביום לכל היותר, עד שההתראה נסגרת ב-wp-admin.', 'responder' ) . '<br>'
			. esc_html__( 'תוסף רב מסר לוורדפרס', 'responder' ) . '</p>';
		$body .= '</div>';

		return (bool) wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/**
	 * PR1-I32: one email a day at most while leads keep coming from a form with no list. No lead details - they are in the log.
	 */
	private static function sendNoListEmail( $failure ) {
		$to = self::emailRecipients();

		if ( empty( $to ) || ! function_exists( 'wp_mail' ) ) {
			return false;
		}

		$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] לא נבחרה רשימה בטופס – פניות לא נשמרו ברב מסר', 'responder' ),
			$site
		);

		$forms_url = SettingsPage::getUrl( '#plugin_config-elementor' );
		$log_url   = admin_url( 'edit.php?post_type=' . Debugger::POST_TYPE );
		$p         = '<p style="margin:0 0 12px 0;">';

		$body  = '<div dir="rtl" style="direction:rtl;text-align:right;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#1d2327;">';
		$body .= $p . esc_html__( 'שלום,', 'responder' ) . '</p>';
		$body .= $p . esc_html__( 'פנייה נשלחה מטופס באתר שלא נבחרה בו רשימה ברב מסר, ולכן לא נשלחה לרב מסר:', 'responder' )
			. ' <a href="' . esc_url( home_url() ) . '" dir="ltr">' . esc_html( home_url() ) . '</a></p>';
		$body .= '<table dir="rtl" cellpadding="4" cellspacing="0" style="border-collapse:collapse;margin:0 0 12px 0;">';
		$body .= '<tr><td style="font-weight:bold;">' . esc_html__( 'זמן:', 'responder' ) . '</td><td>' . esc_html( self::formatTime( $failure['last_time'] ) ) . '</td></tr>';
		$body .= '<tr><td style="font-weight:bold;">' . esc_html__( 'טופס:', 'responder' ) . '</td><td><span dir="ltr">' . esc_html( $failure['last_form'] ) . '</span></td></tr>';
		$body .= '</table>';
		$body .= $p . esc_html__( 'החיבור לרב מסר תקין. יש לבחור רשימה בטופס ב"טפסים לאלמנטור" ולשמור.', 'responder' ) . '</p>';
		$body .= $p . esc_html__( 'המבקר קיבל הודעה שהשליחה נכשלה, ופרטי הפנייה נשמרו ביומן Responder Debug באתר.', 'responder' ) . '</p>';
		$body .= $p . '<a href="' . esc_url( $forms_url ) . '">' . esc_html__( 'טפסים לאלמנטור', 'responder' ) . '</a>'
			. ' &nbsp;|&nbsp; <a href="' . esc_url( $log_url ) . '">' . esc_html__( 'יומן הפניות', 'responder' ) . '</a></p>';
		$body .= '<p style="margin:16px 0 0 0;color:#646970;font-size:12px;">' . esc_html__( 'הודעה זו נשלחת פעם אחת ביום לכל היותר, עד שהטפסים נשמרים עם רשימה או שההתראה נסגרת ב-wp-admin.', 'responder' ) . '<br>'
			. esc_html__( 'תוסף רב מסר לוורדפרס', 'responder' ) . '</p>';
		$body .= '</div>';

		return (bool) wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
}
