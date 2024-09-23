<?php namespace RavMesser\API\ResponderLive;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

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
		if ( ! empty( $fields['personal_fields'] ) ) {
			$personal_fields = self::formatFields(
				$fields['personal_fields'],
				$fields_values
			);

			$this->fields = array_merge( $this->fields, $personal_fields );
		}

		if ( ! empty( $fields['all_lists_personal_fields'] ) ) {
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

	public static function getTypeById( $type_id = 1 ) {
		return self::FIELDS_MAP[ $type_id ];
	}

	private static function formatChoiceField( $field = array(), $field_value ) {
		if ( isset( $field_value ) && ! empty( $field_value ) ) {
			foreach ( $field['options'] as $option ) {
				if ( $field_value === $option['id'] ) {
					$field['formatted_value'] = $option['name'];
				}
			}
		} else {
			$field['formatted_value'] = '';
		}

		return $field;
	}

	private static function formatDateField( $field = array(), $field_value ) {
		if ( isset( $field_value ) && ! empty( $field_value ) && $field_value['day'] > 0 && $field_value['month'] > 0 && $field_value['year'] > 0 ) {
			$field['formatted_value'] = "{$field_value['day']}-{$field_value['month']}-{$field_value['year']}";
		} elseif ( $field['day'] > 0 && $field['month'] > 0 && $field['year'] > 0 ) {
			$field['formatted_value'] = "{$field_value['day']}-{$field_value['month']}-{$field_value['year']}";
		} else {
			$field['formatted_value'] = '';
		}

		return $field;
	}

	private static function formatField( $field = array(), $field_value ) {
		if ( isset( $field_value ) ) {
			$field['formatted_value'] = $field_value;
		} else {
			$field['formatted_value'] = $field['default'];
		}

		return $field;
	}

	private static function formatFields( $fields = array(), $fields_values = array() ) {
		$new_fields = array();

		foreach ( $fields as $field ) {
			$field_value = '';

			if ( isset( $fields_values[ $field['id'] ] ) && ! empty( $fields_values[ $field['id'] ] ) ) {
				$field_value = $fields_values[ $field['id'] ];
			}

			$new_fields[ $field['id'] ] = self::formatFieldValue( $field, $field_value );
		}

		return $new_fields;
	}

	private static function formatFieldValue( $field = array(), $field_value ) {
		$field_type = self::FIELDS_MAP[ $field['type_id'] ];

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
		}
	}

	private static function formatMultiChoiceField( $field = array(), $field_value ) {
		$field['formatted_value'] = array();

		if ( isset( $field_value ) && ! empty( $field_value ) ) {
			foreach ( $field['options'] as $option ) {
				if ( in_array( $option['id'], $field_value ) ) {
					array_push( $field['formatted_value'], $option['name'] );
				}
			}
		} else {
			$field['formatted_value'] = array();
		}

		$field['formatted_value'] = implode( ',', $field['formatted_value'] );

		return $field;
	}

	private static function formatPhoneField( $field = array(), $field_value ) {
		if ( isset( $field_value ) && ! empty( $field_value ) ) {
			$field['formatted_value'] = $field_value;
		} else {
			$field['formatted_value'] = $field['phone'];
		}

		return $field;
	}
}
