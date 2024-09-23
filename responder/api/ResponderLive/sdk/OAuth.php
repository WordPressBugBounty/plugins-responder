<?php namespace RavMesser\API\ResponderLive\sdk;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Exception;

class OAuth {

	private static $auth_token                 = '';
	private $auth_token_retries_count          = 1;
	private static $auth_token_retries_counter = 3;

	private static $client_id     = 0;
	private static $client_secret = '';

	private static $origin     = 4;
	private static $url_base   = 'https://graph.responder.live/v2/';
	private static $user_agent = 'WP Responder ' . RAV_MESSER_VERSION;
	private static $user_token = '';

	public function __construct( $client_id = 0, $client_secret = '', $user_token = '', $auth_token = '' ) {
		self::$client_id     = $client_id;
		self::$client_secret = $client_secret;
		self::$user_token    = $user_token;
		self::$auth_token    = $auth_token;

		if ( empty( self::$auth_token ) ) {
			$this->generateAuthToken();
		}
	}

	public function getAuthToken() {
		return self::$auth_token;
	}

	public function httpRequest( $path = '', $method = 'GET', $parameters = array() ) {
		$response = '';

		try {
			$connection = curl_init();

			$options = array(
				CURLOPT_URL            => self::$url_base . trim( $path, '/' ),
				CURLOPT_CUSTOMREQUEST  => strtoupper( $method ),
				CURLOPT_USERAGENT      => self::$user_agent,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_CONNECTTIMEOUT => 30,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_RETURNTRANSFER => true,

				CURLOPT_HTTPHEADER     => array(
					'Accept-Charset: UTF-8',
					'Accept-Encoding: UTF-8',
					'Accept: application/json',
					'Content-Type: application/json; charset=utf-8',
				),
			);

			if ( ! empty( self::$auth_token ) ) {
				array_push( $options[ CURLOPT_HTTPHEADER ], 'Authorization: Bearer ' . utf8_encode( self::$auth_token ) );
			}

			switch ( $options[ CURLOPT_CUSTOMREQUEST ] ) {
				case 'GET':
					if ( ! empty( $parameters ) ) {
						$options[ CURLOPT_URL ] = $options[ CURLOPT_URL ] . '?' . http_build_query( $parameters );
					}
					break;
				default:
					if ( ! empty( $parameters ) ) {
						$parameters['origin']          = self::$origin;
						$options[ CURLOPT_POSTFIELDS ] = wp_json_encode( $parameters );
					}
					break;
			}

			curl_setopt_array( $connection, $options );
			$response      = curl_exec( $connection );
			$response_code = curl_getinfo( $connection, CURLINFO_RESPONSE_CODE );

			curl_close( $connection );

			if ( $response_code !== 200 ) {
				throw new Exception( $response );
			}
		} catch ( Exception $e ) {
			if ( $this->auth_token_retries_count <= self::$auth_token_retries_counter ) {
				$this->auth_token_retries_count++;

				if ( $path !== '/oauth/token' ) {
					$this->generateAuthToken();
				}

				return $this->httpRequest( $path, $method, $parameters );
			}

			return $e->getMessage();
		}

		return $response;
	}

	private function generateAuthToken() {
		$data = array(
			'grant_type'    => 'client_credentials',
			'scope'         => '*',
			'client_id'     => self::$client_id,
			'client_secret' => self::$client_secret,
			'user_token'    => self::$user_token,
		);

		$response = $this->httpRequest( '/oauth/token', 'POST', $data );
		$response = json_decode( $response );

		if ( isset( $response->token ) ) {
			self::$auth_token = $response->token;
		}
	}
}
