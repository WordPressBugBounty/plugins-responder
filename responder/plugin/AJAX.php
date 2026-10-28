<?php

/**
 * This file is the plugin's ajax calls manager.
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\API as PluginAPI;
use RavMesser\Integrations\Elementor\DataManager as ElementorDataManager;
use RavMesser\Integrations\ContactForm7\DataManager as ContactForm7DataManager;
use RavMesser\Integrations\PojoForms\DataManager as PojoFormsDataManager;
use RavMesser\Plugin\Helpers as PluginHelpers;

class AJAX {

	const NONCE              = 'responder_ajax_actions';
	const INVALID_NONCE_CODE = 'rmp_invalid_nonce';
	const FORM_NOT_FOUND_CODE = 'rmp_form_not_found';
	const SUBMISSION_FAILED_CODE = 'rmp_submission_failed';
	const SYSTEM_NOT_CONNECTED_CODE = 'rmp_system_not_connected';

	/**
	 * PR1-I28: a request for a system that is not connected (no key/secret or Token saved, or an unknown name)
	 * ends here with a JSON error instead of a PHP fatal error on the missing connection. HTTP 200, like
	 * rmp_list_not_loaded, so proxies such as Cloudflare do not replace the message with their own page.
	 */
	private static function requireConnectedSystem( $system_name ) {
		if ( in_array( $system_name, PluginAPI::getConnectedSystemsNames(), true ) ) {
			return;
		}

		// Plain text (not HTML-escaped): the screens insert it with .text(), so quotes must stay quotes.
		$display_name = 'responder' === $system_name ? __( 'רב מסר הישנה', 'responder' ) : __( 'רב מסר', 'responder' );

		wp_send_json_error(
			array(
				'code'    => self::SYSTEM_NOT_CONNECTED_CODE,
				'system'  => sanitize_key( (string) $system_name ),
				/* translators: %s: system name (רב מסר / רב מסר הישנה) */
				'message' => sprintf( __( 'המערכת %s לא מחוברת לתוסף, ולכן אי אפשר לטעון ממנה רשימות ושדות. חברו אותה בלשונית "חיבור לרב מסר", או בחרו מערכת אחרת.', 'responder' ), $display_name ),
			)
		);
	}

	public static function createCF7Form() {
		$form_data = PluginHelpers::getPostGetVariable( 'form_data', PluginHelpers::SANITIZE_ARRAY );

		if ( ! current_user_can( 'manage_options' ) ) {
			self::response( array( 'message' => 'Forbidden' ), 403 );
		}

		if ( empty( $form_data ) || ! self::verifyNonce() ) {
			self::response( array(), 400 );
		}

		$url = ContactForm7DataManager::createFormAndSaveSettings( $form_data );

		if ( empty( $url ) ) {
			self::response( array(), 400 );
		}

		self::response( array( $url ) );
	}

	public static function createNonce( $nonce = self::NONCE ) {
		return wp_create_nonce( $nonce );
	}

	public static function createPojoForm() {
		$form_data = PluginHelpers::getPostGetVariable( 'form_data', PluginHelpers::SANITIZE_ARRAY );

		if ( ! current_user_can( 'manage_options' ) ) {
			self::response( array( 'message' => 'Forbidden' ), 403 );
		}

		if ( empty( $form_data ) || ! self::verifyNonce() ) {
			self::response( array(), 400 );
		}

		$url = PojoFormsDataManager::createPojoForm( $form_data );

		if ( empty( $url ) ) {
			self::response( array(), 400 );
		}

		self::response( array( $url ) );
	}

	public static function createSubscribersTag() {
		$tag_name = PluginHelpers::getPostGetVariable( 'tag_name', PluginHelpers::SANITIZE_TEXT_FIELD );

		if ( ! current_user_can( 'manage_options' ) ) {
			self::response( array( 'message' => 'Forbidden' ), 403 );
		}

		if ( empty( $tag_name ) || ! self::verifyNonce() ) {
			return null;
		}

		self::requireConnectedSystem( 'responder_live' );

		$tag = PluginAPI::run( 'responder_live' )->createSubscribersTag( $tag_name );

		self::response( $tag );
	}

	public static function getListsBySystemName() {
		$system_name = PluginHelpers::getPostGetVariable( 'system_name', PluginHelpers::SANITIZE_TEXT_FIELD );

		if ( ! current_user_can( 'manage_options' ) ) {
			self::response( array( 'message' => 'Forbidden' ), 403 );
		}

		if ( empty( $system_name ) || ! self::verifyNonce() ) {
			self::response( array(), 400 );
		}

		self::requireConnectedSystem( $system_name );

		$lists = PluginAPI::run( $system_name )->getLists();

		self::response( $lists );
	}

	public static function getPersonalFieldsByListId() {
		$list_id     = PluginHelpers::getPostGetVariable( 'list_id', PluginHelpers::SANITIZE_ID );
		$system_name = PluginHelpers::getPostGetVariable( 'system_name', PluginHelpers::SANITIZE_TEXT_FIELD );

		if ( ! current_user_can( 'manage_options' ) ) {
			self::response( array( 'message' => 'Forbidden' ), 403 );
		}

		if ( empty( $list_id ) || empty( $system_name ) || ! self::verifyNonce() ) {
			self::response( array(), 400 );
		}

		self::requireConnectedSystem( $system_name );

		$personal_fields = PluginAPI::run( $system_name )->getPersonalFieldsByListId( $list_id );

		self::response( $personal_fields );
	}

	public static function getSubscribersSheetByListId() {
		$list_id     = PluginHelpers::getPostGetVariable( 'list_id', PluginHelpers::SANITIZE_ID );
		$system_name = PluginHelpers::getPostGetVariable( 'system_name', PluginHelpers::SANITIZE_TEXT_FIELD );

		if ( ! current_user_can( 'manage_options' ) ) {
			self::response( array( 'message' => 'Forbidden' ), 403 );
		}

		if ( empty( $list_id ) || empty( $system_name ) || ! self::verifyNonce() ) {
			return self::response( array(), 400 );
		}

		self::requireConnectedSystem( $system_name );

		$system = PluginAPI::run( $system_name );

		// PR1-I17: רב מסר lists are loaded a page at a time (offset + size), so large lists load too.
		if ( PluginHelpers::getPostGetVariable( 'paged', PluginHelpers::SANITIZE_ID ) && method_exists( $system, 'getSubscribersSheetPageByListId' ) ) {
			$subscribers_sheet = $system->getSubscribersSheetPageByListId(
				$list_id,
				absint( PluginHelpers::getPostGetVariable( 'offset', PluginHelpers::SANITIZE_TEXT_FIELD ) ),
				absint( PluginHelpers::getPostGetVariable( 'size', PluginHelpers::SANITIZE_TEXT_FIELD ) ),
				(string) PluginHelpers::getPostGetVariable( 'search', PluginHelpers::SANITIZE_TEXT_FIELD )
			);
		} else {
			$subscribers_sheet = $system->getSubscribersSheetByListId( $list_id );
		}

		// PR1-I17: the list could not be loaded (no answer from רב מסר in time, or an API error).
		// Sent as a WordPress JSON error with HTTP 200: Cloudflare (and other proxies) replace a
		// 502/504 from the site with their own HTML page, and the message would be lost.
		if ( false === $subscribers_sheet ) {
			wp_send_json_error(
				array(
					'code'    => 'rmp_list_not_loaded',
					'message' => esc_html__( 'לא הצלחנו לטעון את הרשימה מרב מסר. ייתכן שהיא גדולה מדי להצגה כאן – אפשר לצפות בה במערכת רב מסר, או לנסות שוב בעוד כמה דקות.', 'responder' ),
				)
			);
		}

		self::response( $subscribers_sheet );
	}

	public static function getSubscribersTags() {

		if ( ! current_user_can( 'manage_options' ) ) {
			self::response( array( 'message' => 'Forbidden' ), 403 );
		}

		if ( ! self::verifyNonce() ) {
			self::response( array(), 400 );
		}

		self::requireConnectedSystem( 'responder_live' );

		$subscribers_tags = PluginAPI::run( 'responder_live' )->getSubscribersTags();

		self::response( $subscribers_tags );
	}

	public static function getUrl() {
		return get_admin_url( null, 'admin-ajax.php?page=' . RAV_MESSER_MENU_SLUG );
	}

	public static function register() {
		add_action( 'wp_ajax_RMP_getSubscribersSheetByListId', array( __CLASS__, 'getSubscribersSheetByListId' ) );
		add_action( 'wp_ajax_RMP_getListsBySystemName', array( __CLASS__, 'getListsBySystemName' ) );
		add_action( 'wp_ajax_RMP_getPersonalFieldsByListId', array( __CLASS__, 'getPersonalFieldsByListId' ) );
		add_action( 'wp_ajax_RMP_createSubscribersTag', array( __CLASS__, 'createSubscribersTag' ) );
		add_action( 'wp_ajax_RMP_getSubscribersTags', array( __CLASS__, 'getSubscribersTags' ) );
		add_action( 'wp_ajax_RMP_saveElementorFormsSettings', array( __CLASS__, 'saveElementorFormsSettings' ) );
		add_action( 'wp_ajax_RMP_submitElementorForm', array( __CLASS__, 'submitElementorForm' ) );
		add_action( 'wp_ajax_nopriv_RMP_submitElementorForm', array( __CLASS__, 'submitElementorForm' ) );
		add_action( 'wp_ajax_RMP_getFormNonce', array( __CLASS__, 'getFormNonce' ) );
		add_action( 'wp_ajax_nopriv_RMP_getFormNonce', array( __CLASS__, 'getFormNonce' ) );
		add_action( 'wp_ajax_RMP_createCF7Form', array( __CLASS__, 'createCF7Form' ) );
		add_action( 'wp_ajax_RMP_createPojoForm', array( __CLASS__, 'createPojoForm' ) );
	}

	public static function response( $data = array(), $code = 200 ) {
		// Don't let IE cache this request
		header( 'Pragma: no-cache' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: Thu, 01 Jan 1970 00:00:00 GMT' );

		if ( $code !== 200 ) {
			wp_send_json_error( $data, $code );
		}

		wp_send_json( $data, $code );
	}

	public static function saveElementorFormsSettings() {
		$forms_data = PluginHelpers::getPostGetVariable( 'forms_data', PluginHelpers::SANITIZE_ARRAY );

		if ( ! current_user_can( 'manage_options' ) ) {
			self::response( array( 'message' => 'Forbidden' ), 403 );
		}

		if ( ! self::verifyNonce() ) {
			self::response( array(), 400 );
		}

		// PR1-I32: every form needs a list - a form without one would show the visitor "success" and send nothing.
		$forms_without_list = array();
		foreach ( (array) $forms_data as $form_data ) {
			if ( is_array( $form_data ) && empty( $form_data['list_id'] ) ) {
				$forms_without_list[] = trim( (string) PluginHelpers::getVal( $form_data, 'title', '' ) );
			}
		}

		if ( ! empty( $forms_without_list ) ) {
			self::response(
				array(
					'code'  => 'rmp_no_list',
					'forms' => $forms_without_list,
				),
				400
			);
		}

		ElementorDataManager::saveFormsSettings( $forms_data );

		// PR1-I32: every form has a list now - the "no list" notice is no longer true.
		SubmissionFailures::clearNoList();

		self::response( array( 'status' => true ) );
	}

	/**
	 * A fresh nonce for the Elementor widget (PR1-I14): a page served from cache can hold an expired one,
	 * so the widget asks for a new one and sends the form again once.
	 */
	public static function getFormNonce() {
		self::response( array( '_nonce' => self::createNonce() ) );
	}

	public static function submitElementorForm() {
		// PR1-I14: only submits sent by the widget on the page (with the page's nonce) are accepted.
		if ( ! self::verifyNonce() ) {
			self::response(
				array(
					'code'    => self::INVALID_NONCE_CODE,
					'message' => SubmissionFailures::visitorMessage(),
				),
				403
			);
		}

		// WordPress adds slashes to request data; remove them here, once (PR1-I11), so a typed \ or " arrives as typed.
		$fields  = wp_unslash( PluginHelpers::getPostGetVariable( 'fields', PluginHelpers::SANITIZE_ARRAY ) );
		$form_id = (string) PluginHelpers::getPostGetVariable( 'form_id', PluginHelpers::SANITIZE_KEY );

		if ( empty( $fields ) || empty( $form_id ) ) {
			self::response( array(), 400 );
		}

		// PR1-I15: the form was deleted from "טפסים לאלמנטור" (or a cached page sends an old ID) - nothing to send it to.
		// The lead is kept in Responder Debug and the visitor is told the form is no longer active.
		if ( empty( ElementorDataManager::getFormSettings( $form_id ) ) ) {
			do_action( 'RMP_form_not_found', $form_id, $fields, array( 'integration' => 'Elementor widget', 'form' => '(' . $form_id . ')' ) );

			self::response(
				array(
					'code'    => self::FORM_NOT_FOUND_CODE,
					'message' => SubmissionFailures::formNotFoundMessage(),
				),
				404
			);
		}

		// PR1-I10: the lead did not reach רב מסר - the widget shows the error message.
		// HTTP 422, not 502: Cloudflare (and other proxies) replace a 5xx from the site with their own HTML page,
		// so the plugin's JSON answer was lost (same reason as the PR1-I17 list error).
		if ( false === ElementorDataManager::createSubscriber( $fields, $form_id ) ) {
			self::response(
				array(
					'code'    => self::SUBMISSION_FAILED_CODE,
					'message' => SubmissionFailures::visitorMessage(),
				),
				422
			);
		}

		self::response( array( 'status' => true ) );
	}

	public static function verifyNonce() {
		$nonce = PluginHelpers::getPostGetVariable( '_nonce', PluginHelpers::SANITIZE_TEXT_FIELD );

		return (bool) wp_verify_nonce( $nonce, self::NONCE );
	}
}
