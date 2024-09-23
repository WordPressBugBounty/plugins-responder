<?php namespace RavMesser\API\Responder;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );


use RavMesser\Plugin\Helpers as PluginHelpers;

class FieldsParser {

	const INVALID_PHONE_NUMER_REGEX = '/[^\d]/';
	const MAIN_FIELDS_IDS           = array( 'NAME', 'EMAIL', 'PHONE' );

	private $fields     = array();
	private $onexisting = '';

	public function __construct( $subscriber_details ) {
		$this->setDefaultFields( $subscriber_details );
		$this->setMainFields( $subscriber_details['fields'] );
		$this->setPersonalFields( $subscriber_details['fields'] );

		$this->onexisting = $subscriber_details['onexisting'];
	}

	public function getFields() {
		return $this->fields;
	}

	public function prepareResubscriptionFields( $personal_fields = array() ) {
		$this->fields['DAY'] = 0;

		$this->resetMainFields();
		$this->resetPersonalFields( $personal_fields );
	}

	public function resetMainFields() {
		foreach ( self::MAIN_FIELDS_IDS as $field_id ) {
			$field_key = strtoupper( $field_id );

			if ( ! isset( $this->fields[ $field_key ] ) || empty( $this->fields[ $field_key ] ) ) {
				$this->fields[ $field_key ] = null;
			}
		}
	}

	public function resetPersonalFields( $personal_fields = array() ) {
		foreach ( $personal_fields as $personal_field ) {
			$personal_field_id = strtoupper( $personal_field['id'] );

			if (
			! in_array( $personal_field_id, self::MAIN_FIELDS_IDS ) &&
			! isset( $this->fields['PERSONAL_FIELDS'][ $personal_field_id ] )
			) {
				$this->fields['PERSONAL_FIELDS'][ $personal_field_id ] = '';
			}
		}
	}

	public function shouldResubscibe() {
		return $this->onexisting === 'resubscribe';
	}

	private static function parseDateField( $value = '' ) {
		$timestamp = PluginHelpers::dateStringToTimestamp( $value );

		if ( (bool) $timestamp ) {
			return date( 'Y-m-d', $timestamp );
		}

		return '';
	}

	private function parseFieldValue( $field_type, $field_value ) {
		switch ( $field_type ) {
			case 'phone':
				return self::parsePhoneField( $field_value );

			case 'date':
				return self::parseDateField( $field_value );

			case 'text':
			default:
				return self::parseTextField( $field_value );
		}
	}

	private static function parsePhoneField( $value = '' ) {
		return preg_replace( self::INVALID_PHONE_NUMER_REGEX, '', $value );
	}

	private static function parseTextField( $value = '' ) {
		if ( is_array( $value ) ) {
			$value = implode( ',', $value );
		}

		$value = stripslashes( $value );

		return $value;
	}

	private function setDefaultFields( $subscriber_details = array() ) {
		$this->fields['ACCOUNT_STATUS'] = 1;
		$this->fields['NOTIFY']         = 2;
		$this->fields['PHONE_IGNORE']   = true;
		$this->fields['STATUS']         = 1;

		if ( isset( $subscriber_details['identifier'] ) ) {
			$this->fields['IDENTIFIER'] = $subscriber_details['identifier'];
		}
	}

	private function setMainFields( $fields = array() ) {
		foreach ( self::MAIN_FIELDS_IDS as $field_id ) {
			$field_key = strtolower( $field_id );

			if ( isset( $fields[ $field_key ] ) && ! empty( $fields[ $field_key ] ) ) {
				$parsed_value = $this->parseFieldValue(
					$fields[ $field_key ]['type'],
					$fields[ $field_key ]['value']
				);

				if ( ! empty( $parsed_value ) ) {
					  $this->fields[ $field_id ] = $parsed_value;
				}
			}
		}
	}

	private function setPersonalFields( $fields = array() ) {
		foreach ( $fields as $field_id => $field ) {
			$field_key = strtoupper( $field_id );

			if ( ! in_array( $field_key, self::MAIN_FIELDS_IDS ) ) {
				$parsed_value = $this->parseFieldValue(
					$field['type'],
					$field['value']
				);

				if ( ! empty( $parsed_value ) || $parsed_value === '0' ) {
					  $this->fields['PERSONAL_FIELDS'][ $field_key ] = $parsed_value;
				}
			}
		}
	}
}
