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

	const NONCE = 'responder_ajax_actions';

	public static function createCF7Form() {
		$form_data = PluginHelpers::getPostGetVariable( 'form_data', PluginHelpers::SANITIZE_ARRAY );

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

		if ( empty( $tag_name ) || ! self::verifyNonce() ) {
			return null;
		}

		$tag = PluginAPI::run( 'responder_live' )->createSubscribersTag( $tag_name );

		self::response( $tag );
	}

	public static function getListsBySystemName() {
		$system_name = PluginHelpers::getPostGetVariable( 'system_name', PluginHelpers::SANITIZE_TEXT_FIELD );

		if ( empty( $system_name ) || ! self::verifyNonce() ) {
			self::response( array(), 400 );
		}

		$lists = PluginAPI::run( $system_name )->getLists();

		self::response( $lists );
	}

	public static function getPersonalFieldsByListId() {
		$list_id     = PluginHelpers::getPostGetVariable( 'list_id', PluginHelpers::SANITIZE_ID );
		$system_name = PluginHelpers::getPostGetVariable( 'system_name', PluginHelpers::SANITIZE_TEXT_FIELD );

		if ( empty( $list_id ) || empty( $system_name ) || ! self::verifyNonce() ) {
			self::response( array(), 400 );
		}

		$personal_fields = PluginAPI::run( $system_name )->getPersonalFieldsByListId( $list_id );

		self::response( $personal_fields );
	}

	public static function getSubscribersSheetByListId() {
		$list_id     = PluginHelpers::getPostGetVariable( 'list_id', PluginHelpers::SANITIZE_ID );
		$system_name = PluginHelpers::getPostGetVariable( 'system_name', PluginHelpers::SANITIZE_TEXT_FIELD );

		if ( empty( $list_id ) || empty( $system_name ) || ! self::verifyNonce() ) {
			return self::response( array(), 400 );
		}

		$subscribers_sheet = PluginAPI::run( $system_name )->getSubscribersSheetByListId( $list_id );

		self::response( $subscribers_sheet );
	}

	public static function getSubscribersTags() {
		if ( ! self::verifyNonce() ) {
			self::response( array(), 400 );
		}

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

		if ( ! self::verifyNonce() ) {
			self::response( array(), 400 );
		}

		ElementorDataManager::saveFormsSettings( $forms_data );

		self::response( array( 'status' => true ) );
	}

	public static function submitElementorForm() {
		$fields  = PluginHelpers::getPostGetVariable( 'fields', PluginHelpers::SANITIZE_ARRAY );
		$form_id = (string) PluginHelpers::getPostGetVariable( 'form_id', PluginHelpers::SANITIZE_KEY );

		if ( empty( $fields ) || empty( $form_id ) ) {
			self::response( array(), 400 );
		}

		ElementorDataManager::createSubscriber( $fields, $form_id );

		self::response( array( 'status' => true ) );
	}

	public static function verifyNonce() {
		$nonce = PluginHelpers::getPostGetVariable( '_nonce', PluginHelpers::SANITIZE_TEXT_FIELD );

		return (bool) wp_verify_nonce( $nonce, self::NONCE );
	}
}
