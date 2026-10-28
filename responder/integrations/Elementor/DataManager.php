<?php namespace RavMesser\Integrations\Elementor;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\OptionsManager as PluginOptionsManager;
use RavMesser\Plugin\API as PluginAPI;
use RavMesser\Plugin\Helpers as PluginHelpers;

class DataManager {

	const OPTION_NAME = 'responder_settings_elementor';

	public static function createSubscriber( $fields = array(), $form_id = '' ) {
		$form_settings = self::getFormSettings( $form_id );

		// PR1-I15: no form with this ID - never call the API with an empty system (PHP fatal error).
		if ( empty( $form_settings ) ) {
			return false;
		}

		$fields        = self::formatSubscriberFields( $fields, $form_settings['list_custom_fields'] );
		$chosen_system = $form_settings['chosen_system'];

		$subscriber_details = array(
			'list_id' => $form_settings['list_id'],
			'fields'  => $fields,
		);

		switch ( $chosen_system ) {
			case 'responder':
				$subscriber_details['onexisting'] = PluginHelpers::getVal( $form_settings, 'action_on_existing' );
				break;
			case 'responder_live':
				$subscriber_details['tags']                = PluginHelpers::getVal( $form_settings, 'tags', array() );
				$subscriber_details['onexisting_rejoin']   = PluginHelpers::getVal( $form_settings, 'onexisting_rejoin' );
				$subscriber_details['onexisting_joindate'] = PluginHelpers::getVal( $form_settings, 'onexisting_joindate' );
				$subscriber_details['onexisting_sms']      = PluginHelpers::getVal( $form_settings, 'onexisting_sms' );
				break;
		}

		// PR1-I10: false when the lead did not reach רב מסר (the widget then shows its error message).
		return PluginAPI::run( $chosen_system )->submitSubscriber(
			$subscriber_details,
			array(
				'integration' => 'Elementor widget',
				'form'        => trim( PluginHelpers::getVal( $form_settings, 'title', '' ) . ' (' . $form_id . ')' ),
			)
		);
	}

	public static function getFormSettings( $form_id = '' ) {
		$forms_settings = self::getFormsSettings();
		$settings       = array();

		// PR1-I15: no forms saved yet - nothing to look up.
		if ( ! is_array( $forms_settings ) || '' === (string) $form_id ) {
			return $settings;
		}

		foreach ( $forms_settings as $form_settings ) {
			if ( is_array( $form_settings ) && PluginHelpers::getVal( $form_settings, 'generated_id' ) === $form_id ) {
				$settings = $form_settings;
				break;
			}
		}

		return $settings;
	}

	public static function getFormsSettings() {
		$forms_data = PluginOptionsManager::run()->getOption( self::OPTION_NAME );

		if ( isset( $forms_data['elementor_forms'] ) ) {
			$forms_data      = $forms_data['elementor_forms'];
			$save_forms_data = false;

			// Backward compatibility: change the structure for custom list fields.
			foreach ( $forms_data as $index => $form_data ) {
				$chosen_system      = PluginHelpers::getVal( $form_data, 'chosen_system', '' );
				$list_custom_fields = PluginHelpers::getVal( $form_data, 'list_custom_fields', array() );

				$forms_data[ $index ]['title']                       = stripslashes( $form_data['title'] );
				$forms_data[ $index ]['list_id_unite_selected_text'] = stripslashes( $form_data['list_id_unite_selected_text'] );

				if ( empty( $chosen_system ) ) {
					$chosen_system   = $forms_data[ $index ]['chosen_system'] = 'responder';
					$save_forms_data = true;
				}

				if ( empty( $list_custom_fields ) || empty( PluginHelpers::getVal( $list_custom_fields[0], 'uri_param' ) ) ) {
					$forms_data[ $index ]['list_custom_fields'] = PluginAPI::run( $chosen_system )->getPersonalFieldsByListId( (int) $form_data['list_id'] );
					$save_forms_data                            = true;
				}
			}

			if ( $save_forms_data ) {
				self::saveFormsSettings( $forms_data );
			}
		}

		return $forms_data;
	}

	public static function saveFormsSettings( $forms_data = array() ) {
		foreach ( $forms_data as $index => $form_data ) {
			$chosen_system                              = PluginHelpers::getVal( $form_data, 'chosen_system' );
			$forms_data[ $index ]['list_custom_fields'] = PluginAPI::run( $chosen_system )->getPersonalFieldsByListId( (int) $form_data['list_id'] );
		}

		PluginOptionsManager::run()->updateOption(
			self::OPTION_NAME,
			array(
				'elementor_forms' => $forms_data,
			)
		);
	}

	private static function formatSubscriberFields( $form_fields = array(), $personal_fields = array() ) {
		$personal_fields  = PluginHelpers::arrayToAssoc( $personal_fields, 'id' );
		$formatted_fields = array();

		foreach ( $form_fields as $form_field ) {
			$form_field_id = str_replace( 'field_', '', $form_field['name'] );

			if ( isset( $personal_fields[ $form_field_id ] ) ) {
				$field = $personal_fields[ $form_field_id ];

				switch ( $field['type'] ) {
					case 'multichoice':
						if ( isset( $formatted_fields[ $field['id'] ] ) ) {
							$field = $formatted_fields[ $field['id'] ];
						}

						$field['value'][] = $form_field['value'];
						break;

					case 'bool':
						$field['value'] = $form_field['value'] === 'on';
						break;

					default:
						$field['value'] = $form_field['value'];
						break;
				}

				$formatted_fields[ $field['id'] ] = $field;
			}
		}

		return $formatted_fields;
	}
}
