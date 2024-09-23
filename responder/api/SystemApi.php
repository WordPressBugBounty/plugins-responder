<?php

/**
 * This file is the plugin's System API bridge connecting between responder systems
 *
 * @package Responder
 */

namespace RavMesser\API;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\API\Responder\System as ResponderSystem;
use RavMesser\API\ResponderLive\System as ResponderLiveSystem;

class SystemApi {

	private static $apis = array();

	public static function connect( $system_name = 'responder', $user_credentials = array() ) {
		if ( ! isset( self::$apis[ $system_name ] ) ) {
			self::create( $system_name, $user_credentials );
		}

		return self::$apis[ $system_name ];
	}

	private static function create( $system_name = 'responder', $user_credentials = array() ) {
		switch ( $system_name ) {
			case 'responder':
				self::$apis[ $system_name ] = new ResponderSystem( $user_credentials );
				break;
			case 'responder_live':
				self::$apis[ $system_name ] = new ResponderLiveSystem( $user_credentials );
				break;
		}
	}
}
