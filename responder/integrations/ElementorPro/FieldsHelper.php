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

	/**
	 * @param array      $unmatched Filled (רב מסר only) with the choice answers that match no option of the list
	 *                              field - those fields are not sent (PR1-I13).
	 */
	public static function formatFormFieldsByFieldsMap( $chosen_system = '', $form_fields = array(), $fields_map = array(), &$unmatched = array() ) {
		$fields            = array();
		$ids_regexp        = '/^' . self::$FIELD_ID_PREFIX . '(?<field_id>\w+?)(_(?<option_id>\d+?))?$/';
		$parent_form_field = array();
		$raw_values        = array();
		$matched_options   = array();
		$unmatched         = array();

		ksort( $fields_map['fields'] );

		foreach ( $fields_map['fields'] as $mapped_field_key => $mapped_field_value ) {
			preg_match( $ids_regexp, $mapped_field_key, $matched_ids );

			$field_id  = PluginHelpers::getVal( $matched_ids, 'field_id' );
			$option_id = PluginHelpers::getVal( $matched_ids, 'option_id' );

			if ( ! empty( $field_id ) && ! empty( $option_id ) ) {
				// An option row of a field whose form field is not mapped / not submitted: nothing to match.
				if ( PluginHelpers::getVal( $parent_form_field, 'field_id' ) !== $field_id ) {
					continue;
				}

				// The options matched by hand in "התאמת אפשרויות הבחירה": form option value => list option ID.
				$matched_options[ $field_id ][ trim( (string) $mapped_field_value ) ] = (int) $option_id;

				$parent_raw_value = PluginHelpers::getVal( $parent_form_field, 'raw_value' );

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

				$raw_values[ $field_id ] = $parent_form_field['raw_value'];
			}
		}

		if ( 'responder_live' === $chosen_system ) {
			$fields = self::resolveChoiceOptions( $fields, $raw_values, $matched_options, $fields_map['list_id'], $unmatched );
		}

		return $fields;
	}

	/**
	 * רב מסר choice / multichoice list fields get the option IDs (PR1-I13). For each answer:
	 * 1. the option matched by hand in the panel, 2. else the list option with the same name
	 * (trimmed, not case-sensitive), 3. else an answer that already is a list option ID.
	 * Answers that match nothing are reported in $unmatched; a field left with no option is not sent,
	 * instead of being saved as "no option".
	 * A list field of another type (e.g. a Select mapped to a text field) is sent as before.
	 */
	private static function resolveChoiceOptions( $fields, $raw_values, $matched_options, $list_id, &$unmatched ) {
		$personal_fields = null;

		foreach ( $fields as $field_id => $field ) {
			if ( ! in_array( $field['type'], array( 'choice', 'multichoice' ), true ) ) {
				continue;
			}

			if ( null === $personal_fields ) {
				$personal_fields = self::getPersonalFieldsById( $list_id );
			}

			$personal_field = PluginHelpers::getVal( $personal_fields, (string) $field_id, array() );
			$list_type      = PluginHelpers::getVal( $personal_field, 'type', '' );
			$options        = PluginHelpers::getVal( $personal_field, 'options', array() );

			if ( ! in_array( $list_type, array( 'choice', 'multichoice' ), true ) || empty( $options ) || ! is_array( $options ) ) {
				continue;
			}

			$option_ids = array();
			$lost       = array();

			foreach ( self::answersFromRawValue( PluginHelpers::getVal( $raw_values, $field_id, '' ) ) as $answer ) {
				$option_id = self::findOptionId( $answer, $options, PluginHelpers::getVal( $matched_options, $field_id, array() ) );

				if ( null === $option_id ) {
					$lost[] = $answer;
				} elseif ( ! in_array( $option_id, $option_ids, true ) ) {
					$option_ids[] = $option_id;
				}
			}

			if ( ! empty( $lost ) ) {
				$unmatched[] = array(
					'field_id'   => $field_id,
					'field_name' => PluginHelpers::getVal( $personal_field, 'name', '' ),
					'answers'    => $lost,
					'options'    => implode( ' / ', wp_list_pluck( $options, 'name' ) ),
				);
			}

			if ( empty( $option_ids ) && ! empty( $lost ) ) {
				unset( $fields[ $field_id ] );
				continue;
			}

			$fields[ $field_id ]['type'] = $list_type;

			if ( 'multichoice' === $list_type ) {
				$fields[ $field_id ]['value'] = $option_ids;
			} else {
				$fields[ $field_id ]['value'] = empty( $option_ids ) ? '' : $option_ids[0];
			}
		}

		return $fields;
	}

	/**
	 * The visitor's answers as a list: a Checkbox / multi Select gives an array,
	 * a Select / Radio a string, an unticked Checkbox an empty string.
	 */
	private static function answersFromRawValue( $raw_value ) {
		$answers = is_array( $raw_value ) ? $raw_value : array( $raw_value );
		$answers = array_map( 'trim', array_map( 'strval', array_filter( $answers, 'is_scalar' ) ) );

		return array_values( array_filter( $answers, 'strlen' ) );
	}

	private static function findOptionId( $answer, $options, $matched_options ) {
		if ( isset( $matched_options[ $answer ] ) ) {
			return (int) $matched_options[ $answer ];
		}

		$normalized_answer = self::normalizeOptionName( $answer );

		foreach ( $options as $option ) {
			if ( self::normalizeOptionName( PluginHelpers::getVal( $option, 'name', '' ) ) === $normalized_answer ) {
				return (int) PluginHelpers::getVal( $option, 'id', 0 );
			}
		}

		if ( ctype_digit( $answer ) ) {
			foreach ( $options as $option ) {
				if ( (int) PluginHelpers::getVal( $option, 'id', 0 ) === (int) $answer ) {
					return (int) $answer;
				}
			}
		}

		return null;
	}

	private static function normalizeOptionName( $name ) {
		$name = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' ) ) );

		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $name, 'UTF-8' ) : strtolower( $name );
	}

	private static function getPersonalFieldsById( $list_id ) {
		$personal_fields = array();

		foreach ( PluginAPI::run( 'responder_live' )->getPersonalFieldsByListId( (int) $list_id ) as $personal_field ) {
			$personal_fields[ (string) PluginHelpers::getVal( $personal_field, 'id', '' ) ] = $personal_field;
		}

		return $personal_fields;
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

	/**
	 * PR1-I25: the connected system that holds a list. A form saved without "בחירת מערכת" (Elementor does not
	 * save a value equal to the control default) must keep sending to the system of its list, not to
	 * whatever system is the default today (the default changes when a system is connected / disconnected).
	 *
	 * @param int|string $list_id   The list in the form's fields map.
	 * @param string     $preferred The system Elementor gives the form now (checked first).
	 * @return string The system that has the list, or $preferred when no connected system has it.
	 */
	public static function resolveSystemForList( $list_id = 0, $preferred = '' ) {
		$list_id = (string) $list_id;

		if ( '' === $list_id ) {
			return $preferred;
		}

		$connected = PluginAPI::getConnectedSystemsNames();
		$systems   = array_values( array_unique( array_merge( in_array( $preferred, $connected, true ) ? array( $preferred ) : array(), $connected ) ) );

		foreach ( $systems as $system_name ) {
			$api = PluginAPI::run( $system_name );

			if ( ! is_object( $api ) || ! method_exists( $api, 'getLists' ) ) {
				continue;
			}

			foreach ( (array) $api->getLists() as $list ) {
				if ( is_array( $list ) && (string) PluginHelpers::getVal( $list, 'id', '' ) === $list_id ) {
					return $system_name;
				}
			}
		}

		return $preferred;
	}

	/**
	 * PR1-I25: the form's settings exactly as saved (without Elementor's defaults), or null when not found.
	 */
	public static function getSavedFormSettings( $post_id = 0, $form_id = '' ) {
		if ( empty( $post_id ) || '' === (string) $form_id || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return null;
		}

		$document = \Elementor\Plugin::$instance->documents->get( absint( $post_id ) );

		if ( ! $document || ! method_exists( $document, 'get_elements_data' ) ) {
			return null;
		}

		$element = self::findElement( (array) $document->get_elements_data(), (string) $form_id );

		return null === $element ? null : (array) PluginHelpers::getVal( $element, 'settings', array() );
	}

	private static function findElement( $elements, $element_id ) {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( isset( $element['id'] ) && (string) $element['id'] === $element_id ) {
				return $element;
			}

			if ( ! empty( $element['elements'] ) ) {
				$found = self::findElement( (array) $element['elements'], $element_id );

				if ( null !== $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * PR1-I25: before Elementor saves a page, write "בחירת מערכת" into every form that uses the רב מסר action
	 * but has no saved value (it equalled the default) - the system of the form's list, else the default
	 * the editor showed. Hooked to elementor/document/save/data.
	 */
	public static function addSystemChoiceOnSave( $data, $document = null ) {
		if ( is_array( $data ) && ! empty( $data['elements'] ) && is_array( $data['elements'] ) ) {
			$data['elements'] = self::addSystemChoiceToElements( $data['elements'] );
		}

		return $data;
	}

	private static function addSystemChoiceToElements( $elements ) {
		foreach ( $elements as $index => $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
			$actions  = isset( $settings['submit_actions'] ) ? (array) $settings['submit_actions'] : array();

			if ( in_array( 'responder', $actions, true ) && ( ! isset( $settings['responder_system_choice'] ) || '' === $settings['responder_system_choice'] ) ) {
				$connected  = PluginAPI::getConnectedSystemsNames();
				$default    = isset( $connected[0] ) ? $connected[0] : '';
				$fields_map = PluginHelpers::jsonDecode( isset( $settings['responder_fields_map'] ) ? $settings['responder_fields_map'] : '' );
				$system     = self::resolveSystemForList( PluginHelpers::getVal( $fields_map, 'list_id', '' ), $default );

				if ( '' !== $system ) {
					$elements[ $index ]['settings']['responder_system_choice'] = $system;
				}
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$elements[ $index ]['elements'] = self::addSystemChoiceToElements( $element['elements'] );
			}
		}

		return $elements;
	}
}
