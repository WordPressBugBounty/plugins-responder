<?php

/**
 * This file is the plugin's api manager file.
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\API\SystemApi;
use RavMesser\Plugin\OptionsManager as PluginOptionsManager;

class API {

	public static function getConnectedSystemsNames() {
		$systems_names  = array();
		$systems_tokens = array(
			'responder' => array( 'Responder_Plugin_EnterUsername', 'Responder_Plugin_EnterPassword' ),
		);

		if ( self::isResponderLiveEnabled() ) {
			$systems_tokens['responder_live'] = array( 'responder_live_user_token' );
		}

		foreach ( $systems_tokens as $system_name => $token_names ) {
			$tokens = OptionsManager::run()->getOptions( $token_names );

			if ( ! in_array( '', array_values( $tokens ), true ) ) {
				array_push( $systems_names, $system_name );
			}
		}

		return $systems_names;
	}

	/**
	 * Systems in display order: the current system (רב מסר) first, the old one (רב מסר הישנה) second.
	 * Display only - getConnectedSystemsNames() keeps its order, because it decides the default
	 * of forms that never stored a system (see PR1-I5 / PR1-I25).
	 */
	public static function getDisplaySystemsNames() {
		$systems_names = self::getConnectedSystemsNames();
		usort(
			$systems_names,
			function ( $a, $b ) {
				return ( 'responder_live' === $b ) - ( 'responder_live' === $a );
			}
		);

		return $systems_names;
	}

	/**
	 * Pre-selected system for NEW forms: the current system when connected.
	 */
	public static function getPreferredSystemName() {
		$systems_names = self::getDisplaySystemsNames();

		return isset( $systems_names[0] ) ? $systems_names[0] : '';
	}

	/**
	 * Help article for the plugin (no section anchor - KB heading IDs change when the article is edited).
	 */
	public static function getHelpLink() {
		return 'https://support.responder.co.il/portal/he/kb/articles/%D7%AA%D7%95%D7%A1%D7%A3-%D7%94%D7%98%D7%A4%D7%A1%D7%99%D7%9D-%D7%A9%D7%9C-%D7%A8%D7%91-%D7%9E%D7%A1%D7%A8-%D7%9C-wordpress';
	}

	/**
	 * The current system (רב מסר) is always available. The old "responder_live_enabled"
	 * option (set by the removed "חיבור התוסף למערכת החדשה" button) is no longer needed;
	 * it is kept registered so existing sites are not affected.
	 */
	public static function isResponderLiveEnabled() {
		return true;
	}

	public static function notValidSystemsAuth() {
		$responder_not_valid      = ! self::run( 'responder' )->isValid();
		$responder_live_not_valid = self::isResponderLiveEnabled() && ! self::run( 'responder_live' )->isValid();

		return $responder_not_valid || $responder_live_not_valid;
	}

	public static function run( $system_name = 'responder' ) {
		switch ( $system_name ) {
			case 'responder':
				return self::connectToResponder();
			break;

			case 'responder_live':
				return self::connectToResponderLive();
			break;
		}
	}

	private static function connectToResponder() {
		$user_credentials = array(
			'user_token'  => OptionsManager::run()->getOption( 'Responder_Plugin_EnterUsername' ),
			'user_secret' => OptionsManager::run()->getOption( 'Responder_Plugin_EnterPassword' ),
		);

		return SystemApi::connect( 'responder', $user_credentials );
	}

	private static function connectToResponderLive() {
		$user_credentials = array(
			'user_token' => OptionsManager::run()->getOption( 'responder_live_user_token' ),
			'auth_token' => OptionsManager::run()->getOption( 'responder_live_auth_token' ),
		);

		return SystemApi::connect( 'responder_live', $user_credentials );
	}
}
