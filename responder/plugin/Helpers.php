<?php

/**
 * This file is the plugin's helpers file
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Exception;

class Helpers {

	const SANITIZE_ARRAY      = 'sanitize_array';
	const SANITIZE_ID         = 'sanitize_id';        // positive number or empty
	const SANITIZE_KEY        = 'sanitize_key';
	const SANITIZE_NOTHING    = 'sanitize_nothing';
	const SANITIZE_TEXT_FIELD = 'sanitize_text_field';

	// Israeli phone after normalizePhone(): mobile 050-059, 07X (072-079 etc.) – 10 digits;
	// landline 02/03/04/08/09 – 9 digits (PR1-I8).
	const ISRAELI_PHONE_REGEX = '/^0(?:5\d|7\d|[23489])\d{7}$/';

	/**
	 * URL parameters that WordPress reserves as query vars. A page opened with one of them
	 * (e.g. ?name=...) is a 404, so on the same site the plugin uses the alias instead (PR1-I26).
	 */
	const RESERVED_URI_PARAM_ALIASES = array(
		'name' => 'full_name',
	);

	public static function arrayToAssoc( $array, $field = null ) {
		$assoc_array = array();

		foreach ( $array as $item ) {
			if ( empty( $field ) ) {
				$assoc_array[ $item ] = $item;
			} else {
				$assoc_array[ $item[ $field ] ] = $item;
			}
		}

		return $assoc_array;
	}

	public static function assocToArray( $assoc ) {
		$array = array();

		foreach ( $assoc as $item ) {
			$array[] = $item;
		}

		return $array;
	}

	public static function dateStringToTimestamp( $date = '' ) {
		$normalized_date = preg_replace( '/\//', '-', $date );

		if ( self::isStringValidDate( $normalized_date ) ) {
			return strtotime( $normalized_date );
		}

		return false;
	}

	public static function findObjectByKeyValue( $array, $key, $value ) {
		foreach ( $array as $object ) {
			if ( isset( $object[ $key ] ) && $object[ $key ] === $value ) {
				return $object;
			}
		}

		return array();
	}

	public static function getPostGetVariable( $param, $sanitize_type = self::SANITIZE_TEXT_FIELD ) {
		if ( isset( $_POST[ $param ] ) ) {
			return self::sanitizeVar( $_POST[ $param ], $sanitize_type );
		} elseif ( isset( $_GET[ $param ] ) ) {
			return self::sanitizeVar( $_GET[ $param ], $sanitize_type );
		} elseif ( $sanitize_type === self::SANITIZE_ARRAY ) {
			return array();
		} else {
			return '';
		}
	}

	public static function getVal( $array, $key, $default = '' ) {
		if ( isset( $array[ $key ] ) ) {
			return $array[ $key ];
		}

		return $default;
	}

	public static function getValue( $variable, $default = '' ) {
		if ( ! isset( $variable ) || empty( $variable ) ) {
			return $default;
		}

		return $variable;
	}

	public static function ifExistsAndEqual( $array, $key, $equal_to ) {
		$variable = self::getVal( $array, $key );
		$equal_to = self::getValue( $equal_to );

		if ( $equal_to === $variable ) {
			return true;
		}

		return false;
	}

	public static function ifExistsAndNotEmpty( $array, $key ) {
		$variable = self::getVal( $array, $key );

		if ( ! empty( $variable ) ) {
			return true;
		}

		return false;
	}

	public static function ifExistsAndNotEmptyArray( $array, ...$keys ) {
		foreach ( $keys as $key ) {
			if ( ! self::ifExistsAndNotEmpty( $array, $key ) ) {
				return false;
			}
		}

		return true;
	}

	public static function isStringValidDate( $string = '' ) {
		$pattern = strtr(
			'/^(%DM|%Y)%s%DM%s(%DM|%Y)$/',       // regex template
			array(
				'%s'  => '[^\w\d\r\n\s:]',         // separator
				'%DM' => '(0?[1-9]|[12]\d|30|31)', // day or month
				'%Y'  => '(\d{2}|\d{4})',          // year
			)
		);

		return (bool) preg_match( $pattern, $string );
	}

	/**
	 * Israeli phone validity, checked on the normalized number (PR1-I8).
	 */
	public static function isValidIsraeliPhone( $value = '' ) {
		// Only digits and the usual separators are allowed in what the visitor typed ("054abc1234567" is invalid).
		if ( preg_match( '/[^\d\s\-+().]/u', (string) $value ) ) {
			return false;
		}

		return (bool) preg_match( self::ISRAELI_PHONE_REGEX, self::normalizePhone( $value ) );
	}

	/**
	 * Keeps digits only and turns an international Israeli prefix into a local one:
	 * "054-123 4567" → "0541234567", "+972541234567" / "00972541234567" / "+972-054-1234567" → "0541234567".
	 * Other numbers are only stripped of non-digits (PR1-I8).
	 */
	public static function normalizePhone( $value = '' ) {
		if ( is_array( $value ) ) {
			$value = implode( '', $value );
		}

		$digits = preg_replace( '/[^\d]/', '', (string) $value );

		if ( strpos( $digits, '00972' ) === 0 ) {
			$digits = substr( $digits, 2 );
		}

		if ( strpos( $digits, '972' ) === 0 && strlen( $digits ) >= 11 ) {
			$digits = substr( $digits, 3 );

			if ( strpos( $digits, '0' ) !== 0 ) {
				$digits = '0' . $digits;
			}
		}

		return $digits;
	}

	public static function jsonDecode( $content ) {
		$array = @json_decode( $content, true );

		if ( empty( $array ) ) {
			$array = array();
		}

		return $array;
	}

	public static function sanitizeVar( $value, $type ) {
		switch ( $type ) {
			case self::SANITIZE_ID:
				if ( empty( $value ) ) {
					return '';
				}

				$value = (int) $value;
				$value = abs( $value );

				if ( $value == 0 ) {
					return '';
				}
				break;

			case self::SANITIZE_KEY:
				$value = sanitize_key( $value );
				break;

			case self::SANITIZE_TEXT_FIELD:
				$value = sanitize_text_field( $value );
				break;

			case self::SANITIZE_ARRAY:
				$value = self::sanitizeVarArray( $value );
				break;

			default:
				throw new Exception( 'Wrong sanitize type: ' . $type );
			break;
		}

		return $value;
	}

	public static function sanitizeVarArray( $var_array = array() ) {
		foreach ( $var_array as $key => $value ) {
			if ( is_array( $value ) ) {
				$var_array[ $key ] = self::sanitizeVarArray( $value );
			} elseif ( is_int( $value ) ) {
				$var_array[ $key ] = absint( $value );
			} elseif ( wp_http_validate_url( $value ) ) {
				$var_array[ $key ] = sanitize_url( $value );
			} else {
				$var_array[ $key ] = sanitize_text_field( $value );
			}
		}

		return $var_array;
	}

	/**
	 * Returns the URL parameter to use on this WordPress site: the alias for a reserved
	 * WordPress query var (name -> full_name), otherwise the parameter itself (PR1-I26).
	 */
	public static function safeUriParam( $param = '' ) {
		$param = (string) $param;

		return isset( self::RESERVED_URI_PARAM_ALIASES[ $param ] ) ? self::RESERVED_URI_PARAM_ALIASES[ $param ] : $param;
	}

	/**
	 * Reads a field value from the URL: the safe alias first (full_name), then the original
	 * parameter (name) for backward compatibility (PR1-I26).
	 */
	public static function getUriParamValue( $query = array(), $param = '' ) {
		$safe_param = self::safeUriParam( $param );

		if ( $safe_param !== $param && isset( $query[ $safe_param ] ) ) {
			return $query[ $safe_param ];
		}

		return self::getVal( $query, $param, '' );
	}

	public static function urlToHandle( $url = '' ) {
		// Replace all weird characters with dashes
		$url = preg_replace( '/[^\w\-' . '~_\.' . ']+/u', '-', $url );

		// Only allow one dash separator at a time
		$url = preg_replace( '/--+/u', '-', $url );

		// Make string lowercase
		if ( function_exists( 'mb_strtolower' ) ) {
			$url = mb_strtolower( $url, 'UTF-8' );
		} else {
			$url = strtolower( $url );
		}

		return $url;
	}

	/**
	 * Identification sent to the Responder APIs (HTTP User-Agent): plugin, WordPress and PHP version.
	 * No site or personal data. Lets us see in the API access logs which WordPress / PHP versions
	 * real client sites use (PR1-I24), e.g. "WP Responder 4.4.5; WordPress 7.1.3; PHP 8.3.35".
	 */
	public static function apiUserAgent( $prefix = 'WP Responder' ) {
		$wp_version = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : '';

		$agent = $prefix . ' ' . RAV_MESSER_VERSION;

		if ( ! empty( $wp_version ) ) {
			$agent .= '; WordPress ' . $wp_version;
		}

		return $agent . '; PHP ' . PHP_VERSION;
	}
}
