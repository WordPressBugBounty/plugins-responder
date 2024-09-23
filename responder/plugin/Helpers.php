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
}
