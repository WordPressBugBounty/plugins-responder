<?php namespace RavMesser\API\ResponderLive;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\Helpers as PluginHelpers;

class FieldsFormatter {

	const FIELDS_MAP = array(
		1 => 'text',
		2 => 'textarea',
		3 => 'date',
		4 => 'number',
		5 => 'phone',
		6 => 'choice',
		7 => 'multichoice',
		8 => 'bool',
	);
	private $fields  = array();

	public function __construct( $fields = array(), $fields_values = array() ) {
		// PR1-I17: an unexpected API answer must not stop the whole list.
		$fields        = is_array( $fields ) ? $fields : array();
		$fields_values = is_array( $fields_values ) ? $fields_values : array();

		if ( ! empty( $fields['personal_fields'] ) && is_array( $fields['personal_fields'] ) ) {
			$personal_fields = self::formatFields(
				$fields['personal_fields'],
				$fields_values
			);

			$this->fields = array_merge( $this->fields, $personal_fields );
		}

		if ( ! empty( $fields['all_lists_personal_fields'] ) && is_array( $fields['all_lists_personal_fields'] ) ) {
			$all_lists_personal_fields = self::formatFields(
				$fields['all_lists_personal_fields'],
				$fields_values
			);

			$this->fields = array_merge( $this->fields, $all_lists_personal_fields );
		}
	}

	public function fieldsCount() {
		return count( $this->fields );
	}

	public function getFields() {
		return $this->fields;
	}

	public function getNameValueFields() {
		$fields            = $this->getFields();
		$name_value_fields = array();

		foreach ( $fields as $field ) {
			$name_value_fields[ $field['name'] ] = $field['formatted_value'];
		}

		return $name_value_fields;
	}

	/**
	 * PR1-I26: values by field id - two fields of a list may have the same name (e.g. "utm_source"),
	 * and a name-keyed array keeps only the last one.
	 */
	public function getIdValueFields() {
		$id_value_fields = array();

		foreach ( $this->getFields() as $field ) {
			if ( is_array( $field ) && isset( $field['id'] ) ) {
				$id_value_fields[ (string) $field['id'] ] = PluginHelpers::getVal( $field, 'formatted_value', '' );
			}
		}

		return $id_value_fields;
	}

	/**
	 * PR1-I17: a field type the plugin does not know yet (the API added type 9) is handled as text, not a PHP warning.
	 */
	public static function getTypeById( $type_id = 1 ) {
		$type_id = (int) $type_id;

		return isset( self::FIELDS_MAP[ $type_id ] ) ? self::FIELDS_MAP[ $type_id ] : 'text';
	}

	private static function formatChoiceField( $field = array(), $field_value = '' ) {
		$field['formatted_value'] = '';

		if ( isset( $field_value ) && ! empty( $field_value ) ) {
			foreach ( (array) PluginHelpers::getVal( $field, 'options', array() ) as $option ) {
				if ( is_array( $option ) && isset( $option['id'], $option['name'] ) && (string) $field_value === (string) $option['id'] ) {
					$field['formatted_value'] = $option['name'];
				}
			}
		} else {
			$field['formatted_value'] = '';
		}

		return $field;
	}

	/**
	 * PR1-I17: a date comes as array( day, month, year ). With no value, the field's own default date is used
	 * (the old code read the empty value there - PHP fatal error). Any other value is shown as it came.
	 */
	private static function formatDateField( $field = array(), $field_value = '' ) {
		$field['formatted_value'] = '';

		if ( self::isFullDate( $field_value ) ) {
			$field['formatted_value'] = self::dateToText( $field_value );
		} elseif ( empty( $field_value ) && self::isFullDate( $field ) ) {
			$field['formatted_value'] = self::dateToText( $field );
		} elseif ( is_scalar( $field_value ) ) {
			$field['formatted_value'] = (string) $field_value;
		}

		return $field;
	}

	private static function isFullDate( $date ) {
		return is_array( $date )
			&& (int) PluginHelpers::getVal( $date, 'day', 0 ) > 0
			&& (int) PluginHelpers::getVal( $date, 'month', 0 ) > 0
			&& (int) PluginHelpers::getVal( $date, 'year', 0 ) > 0;
	}

	private static function dateToText( $date ) {
		return (int) $date['day'] . '-' . (int) $date['month'] . '-' . (int) $date['year'];
	}

	private static function formatField( $field = array(), $field_value = '' ) {
		if ( isset( $field_value ) ) {
			$field['formatted_value'] = $field_value;
		} else {
			$field['formatted_value'] = PluginHelpers::getVal( $field, 'default', '' );
		}

		// A value of a type the plugin does not know may be an array - show it as text.
		if ( is_array( $field['formatted_value'] ) ) {
			$field['formatted_value'] = implode( ',', array_map( 'strval', array_filter( $field['formatted_value'], 'is_scalar' ) ) );
		}

		return $field;
	}

	private static function formatFields( $fields = array(), $fields_values = array() ) {
		$new_fields = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['id'] ) ) {
				continue;
			}

			$field_value = '';

			if ( isset( $fields_values[ $field['id'] ] ) && ! empty( $fields_values[ $field['id'] ] ) ) {
				$field_value = $fields_values[ $field['id'] ];
			}

			$new_fields[ $field['id'] ] = self::formatFieldValue( $field, $field_value );
		}

		return $new_fields;
	}

	private static function formatFieldValue( $field = array(), $field_value = '' ) {
		$field_type = self::getTypeById( PluginHelpers::getVal( $field, 'type_id', 1 ) );
		$field      = array_merge( array( 'name' => '' ), $field );

		switch ( $field_type ) {
			case 'text':
			case 'textarea':
			case 'number':
			case 'bool':
				return self::formatField( $field, $field_value );
			break;
			case 'date':
				return self::formatDateField( $field, $field_value );
			break;
			case 'phone':
				return self::formatPhoneField( $field, $field_value );
			break;
			case 'choice':
				return self::formatChoiceField( $field, $field_value );
			break;
			case 'multichoice':
				return self::formatMultiChoiceField( $field, $field_value );
			break;
			default:
				return self::formatField( $field, $field_value );
		}
	}

	private static function formatMultiChoiceField( $field = array(), $field_value = array() ) {
		$field['formatted_value'] = array();

		if ( isset( $field_value ) && ! empty( $field_value ) ) {
			foreach ( (array) PluginHelpers::getVal( $field, 'options', array() ) as $option ) {
				if ( is_array( $option ) && isset( $option['id'], $option['name'] ) && in_array( $option['id'], (array) $field_value ) ) {
					array_push( $field['formatted_value'], $option['name'] );
				}
			}
		} else {
			$field['formatted_value'] = array();
		}

		$field['formatted_value'] = implode( ',', $field['formatted_value'] );

		return $field;
	}

	private static function formatPhoneField( $field = array(), $field_value = '' ) {
		if ( isset( $field_value ) && ! empty( $field_value ) && is_scalar( $field_value ) ) {
			$field['formatted_value'] = $field_value;
		} else {
			$field['formatted_value'] = PluginHelpers::getVal( $field, 'phone', '' );
		}

		return $field;
	}
}
