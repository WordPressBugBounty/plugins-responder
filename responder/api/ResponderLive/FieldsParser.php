<?php namespace RavMesser\API\ResponderLive;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\Helpers as PluginHelpers;

class FieldsParser {

	const INVALID_PHONE_NUMER_REGEX = '/[^\d]/';
	const MAIN_FIELDS_IDS           = array( 'first', 'last', 'name', 'email', 'phone' );

	private $fields = array();

	public function __construct( $subscriber_details ) {
		$this->setDefaultFields( $subscriber_details );
		$this->setMainFields( $subscriber_details['fields'] );
		$this->setPersonalFields( $subscriber_details['fields'] );
	}

	public function getFields() {
		return $this->fields;
	}

	public static function parseChoiceField( $value = array() ) {
		$parsed_value = '';

		if ( ! empty( $value ) && is_array( $value ) ) {
			$parsed_value = implode( ', ', $value );
		} elseif ( ! empty( $value ) ) {
			$parsed_value = is_numeric( $value ) ? (int) $value : self::parseTextField( $value );
		}

		return $parsed_value;
	}

	public static function parseDateField( $value = '' ) {
		$timestamp = PluginHelpers::dateStringToTimestamp( $value );

		if ( (bool) $timestamp ) {
			return array(
				'year'  => (int) date( 'Y', $timestamp ),
				'month' => (int) date( 'm', $timestamp ),
				'day'   => (int) date( 'd', $timestamp ),
			);
		}

		return array();
	}

	public static function parseMultichoiceField( $value = array() ) {
		$parsed_value = array();

		foreach ( $value as $number ) {
			$parsed_value[] = (int) $number;
		}

		return $parsed_value;
	}

	private function parseFieldValue( $field_type, $field_value ) {
		switch ( $field_type ) {
			case 'date':
				return self::parseDateField( $field_value );

			case 'choice':
				return self::parseChoiceField( $field_value );

			case 'multichoice':
				return self::parseMultichoiceField( $field_value );

			case 'phone':
				return self::parsePhoneField( $field_value );

			case 'text':
			case 'textarea':
			case 'email':
				return self::parseTextField( $field_value );

			default:
				return $field_value;
		}
	}

	private static function parsePhoneField( $value = '' ) {
		return preg_replace( self::INVALID_PHONE_NUMER_REGEX, '', $value );
	}

	private static function parseTextField( $value = '' ) {
		return stripslashes( $value );
	}

	private function setDefaultFields( $subscriber_details = array() ) {
		if ( PluginHelpers::ifExistsAndNotEmpty( $subscriber_details, 'list_id' ) ) {
			$this->fields['list_ids'] = array(
				(int) $subscriber_details['list_id'],
			);
		}

		if ( PluginHelpers::ifExistsAndNotEmpty( $subscriber_details, 'tags' ) ) {
			$this->fields['tags'] = array();

			foreach ( $subscriber_details['tags'] as $tag ) {
				array_push( $this->fields['tags'], (int) $tag );
			}
		}

		if ( PluginHelpers::ifExistsAndEqual( $subscriber_details, 'onexisting_rejoin', 'rejoin' ) ) {
			$this->fields['unsubscribed'] = false;
			$this->fields['override']     = true;
		}
	}

	private function setMainFields( $fields = array() ) {
		foreach ( self::MAIN_FIELDS_IDS as $field_id ) {
			if ( PluginHelpers::ifExistsAndNotEmpty( $fields, $field_id ) ) {
				$parsed_value = $this->parseFieldValue(
					$fields[ $field_id ]['type'],
					$fields[ $field_id ]['value']
				);

				if ( ! empty( $parsed_value ) ) {
					  $this->fields[ $field_id ] = $parsed_value;
				}
			}
		}
	}

	private function setPersonalFields( $fields = array() ) {
		foreach ( $fields as $field_id => $field ) {
			if ( ! in_array( $field_id, self::MAIN_FIELDS_IDS ) ) {
				$parsed_value = $this->parseFieldValue(
					$field['type'],
					$field['value']
				);

				if ( ! empty( $parsed_value ) || $field['type'] === 'bool' ) {
					  $this->fields['personal_fields'][ $field_id ] = $parsed_value;
				}
			}
		}
	}
}
