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

	public static function isResponderLiveEnabled() {
		$responder_live_enabled = PluginOptionsManager::run()->getOption( 'responder_live_enabled' );

		return $responder_live_enabled === 'true';
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
