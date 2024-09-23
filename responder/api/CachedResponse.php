<?php

/**
 * This file is the plugin's cached responses storage
 *
 * @package Responder
 */

namespace RavMesser\API;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

class CachedResponse {

	private static $cached_responses = array();

	public static function getCached( $path, $method = 'get', $post = array() ) {
		$url = self::generateUrl( $path, $method, $post );

		if ( self::isUrlCached( $url ) ) {
			return self::$cached_responses[ $url ];
		}

		return array();
	}

	public static function isCached( $path, $method = 'get', $post = array() ) {
		$url = self::generateUrl( $path, $method, $post );

		if ( self::isUrlCached( $url ) ) {
			return true;
		}

		return false;
	}

	public static function setCached( $path, $method = 'get', $post = array(), $response = array() ) {
		$url = self::generateUrl( $path, $method, $post );

		self::$cached_responses[ $url ] = $response;

		return $response;
	}

	private static function generateUrl( $path, $method = 'get', $post = array() ) {
		return strtoupper( $method ) . ' ' . $path . '?' . http_build_query( $post );
	}

	private static function isUrlCached( $url ) {
		return isset( self::$cached_responses[ $url ] );
	}
}
