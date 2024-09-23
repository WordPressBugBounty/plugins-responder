<?php namespace RavMesser\Integrations\ElementorPro;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\API as PluginAPI;
use RavMesser\Plugin\Helpers as PluginHelpers;

class FieldsHelper {

	public static $ALLOWED_FIELD_TYPES = array(
		'text',
		'email',
		'textarea',
		'url',
		'tel',
		'radio',
		'select',
		'checkbox',
		'acceptance',
		'number',
		'date',
		'time',
		'hidden',
	);
	public static $FIELD_ID_PREFIX     = 'responder_field_';

	public static function convertFormFieldType( $chosen_system = '', $form_field = array() ) {
		$field_types_map = self::getFieldTypesMap( $chosen_system );
		$form_field_type = $form_field['type'];

		if ( is_array( $form_field['raw_value'] ) ) {
			$form_field_type = 'multiple_select';
		} elseif ( $chosen_system == 'responder' && $form_field['type'] == 'hidden' ) {
			$form_field_type = (bool) PluginHelpers::dateStringToTimestamp( $form_field['raw_value'] ) ? 'date' : 'text';
		} elseif ( $chosen_system == 'responder_live' && $form_field['type'] == 'hidden' ) {
			$form_field_type = self::getFieldTypeFromPersonalFields( $form_field['list_id'], $form_field['field_id'] );
		}

		foreach ( $field_types_map as $field_type => $field_type_map ) {
			if ( in_array( $form_field_type, $field_type_map ) ) {
				$form_field_type = $field_type;
			}
		}

		return $form_field_type;
	}

	public static function formatFormFieldsByFieldsMap( $chosen_system = '', $form_fields = array(), $fields_map = array() ) {
		$fields            = array();
		$ids_regexp        = '/^' . self::$FIELD_ID_PREFIX . '(?<field_id>\w+?)(_(?<option_id>\d+?))?$/';
		$parent_form_field = array();

		ksort( $fields_map['fields'] );

		foreach ( $fields_map['fields'] as $mapped_field_key => $mapped_field_value ) {
			preg_match( $ids_regexp, $mapped_field_key, $matched_ids );

			$field_id  = PluginHelpers::getVal( $matched_ids, 'field_id' );
			$option_id = PluginHelpers::getVal( $matched_ids, 'option_id' );

			if ( ! empty( $field_id ) && ! empty( $option_id ) ) {
				$parent_raw_value = $parent_form_field['raw_value'];

				if ( $mapped_field_value === $parent_raw_value ) {
					$fields[ $field_id ]['value'] = (int) $option_id;
				}

				if ( is_array( $parent_raw_value ) && in_array( $mapped_field_value, $parent_raw_value ) ) {
					if ( $fields[ $field_id ]['value'] === $parent_raw_value ) {
						$fields[ $field_id ]['value'] = array();
					}

					array_push( $fields[ $field_id ]['value'], (int) $option_id );
				}
			} elseif ( ! empty( $field_id ) && PluginHelpers::ifExistsAndNotEmpty( $form_fields, $mapped_field_value ) ) {
				$parent_form_field             = $form_fields[ $mapped_field_value ];
				$parent_form_field['field_id'] = $field_id;
				$parent_form_field['list_id']  = $fields_map['list_id'];

				if ( $parent_form_field['type'] === 'acceptance' ) {
					$parent_form_field['raw_value'] = $parent_form_field['raw_value'] === 'on';
				}

				$fields[ $field_id ] = array(
					'id'    => $field_id,
					'type'  => self::convertFormFieldType( $chosen_system, $parent_form_field ),
					'value' => $parent_form_field['raw_value'],
				);
			}
		}

		return $fields;
	}

	public static function formatTags( $tags = array() ) {
		$formated_tags = array();

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				array_push( $formated_tags, (int) $tag['id'] );
			}
		}

		return $formated_tags;
	}

	public static function getFieldTypesMap( $chosen_system = '' ) {
		$fields_types_map = array(
			'responder'      => array(
				'date'  => array( 'date', 'hidden' ),
				'email' => array( 'email' ),
				'phone' => array( 'tel' ),
				'text'  => array(
					'acceptance',
					'checkbox',
					'hidden',
					'multiple_select',
					'number',
					'radio',
					'select',
					'text',
					'textarea',
					'time',
					'url',
				),
			),

			'responder_live' => array(
				'bool'        => array( 'acceptance' ),
				'choice'      => array( 'radio', 'select' ),
				'date'        => array( 'date', 'hidden' ),
				'email'       => array( 'email' ),
				'multichoice' => array( 'checkbox', 'multiple_select' ),
				'number'      => array( 'number' ),
				'phone'       => array( 'tel' ),
				'text'        => array(
					'hidden',
					'radio',
					'select',
					'text',
					'time',
					'url',
				),
				'textarea'    => array( 'hidden', 'textarea' ),
			),
		);

		if ( isset( $fields_types_map[ $chosen_system ] ) ) {
			$fields_types_map = $fields_types_map[ $chosen_system ];
		}

		return $fields_types_map;
	}

	public static function validateFieldsMap( $fields_map = array() ) {
		$are_fields_map_valid   = true;
		$must_have_fields_count = 0;

		if ( ! isset( $fields_map['list_id'] ) || empty( $fields_map['list_id'] ) ) {
			$are_fields_map_valid = false;
		}

		if ( ! isset( $fields_map['fields'] ) || empty( $fields_map['fields'] ) ) {
			$are_fields_map_valid = false;
		}

		if (
		isset( $fields_map['fields'][ self::$FIELD_ID_PREFIX . 'email' ] ) &&
		! empty( $fields_map['fields'][ self::$FIELD_ID_PREFIX . 'email' ] )
		) {
			$must_have_fields_count += 1;
		}

		if (
		isset( $fields_map['fields'][ self::$FIELD_ID_PREFIX . 'phone' ] ) &&
		! empty( $fields_map['fields'][ self::$FIELD_ID_PREFIX . 'phone' ] )
		) {
			$must_have_fields_count += 1;
		}

		if ( $must_have_fields_count === 0 ) {
			$are_fields_map_valid = false;
		}

		return $are_fields_map_valid;
	}

	private static function getFieldTypeFromPersonalFields( $list_id, $field_id ) {
		$field_type = 'text';

		$personal_fields = PluginAPI::run( 'responder_live' )->getPersonalFieldsByListId( (int) $list_id );
		foreach ( $personal_fields as $personal_field ) {
			if ( PluginHelpers::ifExistsAndEqual( $personal_field, 'id', (int) $field_id ) ) {
				$field_type = $personal_field['type'];
				break;
			}
		}

		return $field_type;
	}
}
