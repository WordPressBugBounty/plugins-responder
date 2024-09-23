<?php defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

class ResponderOAuth {

	/* Set connect timeout. */
	public $connecttimeout = 30;
	/* Respons format. */
	public $format = 'json';
	/* Set up the API root URL. */
	public $host = 'http://api.responder.co.il/v1.0/';
	/* Contains the last HTTP status code returned. */
	public $http_code;
	/* Contains the last HTTP headers returned. */
	public $http_info;
	/* Verify SSL Cert. */
	public $ssl_verifypeer = false;
	/* Set timeout default. */
	public $timeout = 30;
	/* Contains the last API call. */
	public $url;
	/* Set the useragnet. */
	public $useragent = 'ResponderOAuth v0.1-beta';

	public function __construct( $consumer_key, $consumer_secret, $oauth_token = null, $oauth_token_secret = null ) {
		$this->signature = new ResponderOAuthSignatureMethod_HMAC_SHA1();
		$this->consumer  = new ResponderOAuthConsumer( $consumer_key, $consumer_secret );
		if ( ! empty( $oauth_token ) && ! empty( $oauth_token_secret ) ) {
			$this->token = new ResponderOAuthToken( $oauth_token, $oauth_token_secret );
		} else {
			$this->token = null;
		}
	}

	/**
	 * Get the header info to store. {for cURL}
	 */
	public function getHeader( $ch, $header ) {
		$i = strpos( $header, ':' );
		if ( ! empty( $i ) ) {
			$key                       = str_replace( '-', '_', strtolower( substr( $header, 0, $i ) ) );
			$value                     = trim( substr( $header, $i + 2 ) );
			$this->http_header[ $key ] = $value;
		}
		return strlen( $header );
	}

	public function http( $url, $method, $data, $headers ) {
		$this->http_info = array();
		$ci              = curl_init();

		if ( ! empty( $data ) && ( $method == 'POST' || $method == 'PUT' ) ) {
			$headers[] = 'Content-Length: ' . strlen( $data );
			$headers[] = 'Expect:';
		}

		/* Curl settings */
		curl_setopt( $ci, CURLOPT_USERAGENT, $this->useragent );
		curl_setopt( $ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout );
		curl_setopt( $ci, CURLOPT_TIMEOUT, $this->timeout );
		curl_setopt( $ci, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ci, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer );
		curl_setopt( $ci, CURLOPT_HEADERFUNCTION, array( $this, 'getHeader' ) );
		curl_setopt( $ci, CURLOPT_HEADER, false );

		switch ( $method ) {
			case 'POST':
				curl_setopt( $ci, CURLOPT_POST, true );
				if ( ! empty( $data ) ) {
					curl_setopt( $ci, CURLOPT_POSTFIELDS, $data );
				}
				break;
			case 'PUT':
				curl_setopt( $ci, CURLOPT_CUSTOMREQUEST, 'PUT' );
				if ( ! empty( $data ) ) {
					curl_setopt( $ci, CURLOPT_POSTFIELDS, $data );
				}
				break;
			case 'DELETE':
				curl_setopt( $ci, CURLOPT_CUSTOMREQUEST, 'DELETE' );
				if ( ! empty( $data ) ) {
					$url = "{$url}?{$data}";
				}
				break;
		}

		curl_setopt( $ci, CURLOPT_URL, $url );
		$response        = curl_exec( $ci );
		$this->http_code = curl_getinfo( $ci, CURLINFO_HTTP_CODE );
		$this->http_info = array_merge( $this->http_info, curl_getinfo( $ci ) );
		$this->url       = $url;
		curl_close( $ci );
		return $response;
	}

	public function http_request( $url, $method = 'GET', $parameters = array() ) {
		$method = strtoupper( $method );
		if ( strrpos( $url, 'https://' ) !== 0 && strrpos( $url, 'http://' ) !== 0 ) {
			$url = "{$this->host}{$url}";
		}
		$request = ResponderOAuthRequest::from_consumer_and_token( $this->consumer, $this->token, $method, $url, $parameters );
		$request->sign_request( $this->signature, $this->consumer, $this->token );
		switch ( $method ) {
			case 'GET':
				return $this->http( $request->to_url(), 'GET', null, array( $request->to_header() ) );
			default:
				return $this->http( $request->get_normalized_http_url(), $method, $request->to_postdata(), array( $request->to_header() ) );
		}
	}
}
