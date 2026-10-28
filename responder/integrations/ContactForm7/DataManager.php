<?php namespace RavMesser\Integrations\ContactForm7;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\OptionsManager as PluginOptionsManager;
use RavMesser\Plugin\API as PluginAPI;
use RavMesser\Integrations\ContactForm7\FormHelper as ContactForm7FormHelper;
use RavMesser\Plugin\Helpers as PluginHelpers;

class DataManager {

	const OPTION_PREFIX = 'cf7_res_';

	public static function copyFormSettings( $current_form_id = 0, $old_form_id = 0 ) {
		if ( ! empty( $current_form_id ) && ! empty( $old_form_id ) ) {
			$old_form_settings = PluginOptionsManager::run()->getOption( self::OPTION_PREFIX . $old_form_id );

			PluginOptionsManager::run()->updateOption(
				self::OPTION_PREFIX . $current_form_id,
				$old_form_settings
			);
		}
	}

	public static function createFormAndSaveSettings( $form_data = array() ) {
		$personal_fields = PluginAPI::run( $form_data['chosen_system'] )->getPersonalFieldsByListId( (int) $form_data['list'] );
		$form_helper     = new ContactForm7FormHelper( $form_data, $personal_fields );

		$cf7_form = wpcf7_save_contact_form(
			array(
				'title'    => $form_data['form_name'],
				'form'     => $form_helper->getFormTemplate(),
				'messages' => $form_helper::getMessages(),
				'mail'     => $form_helper->getMail(),
			)
		);

		if ( (int) $cf7_form->id() > 0 ) {
			self::saveFormSettings( $cf7_form->id(), $form_helper->formatFormSettings() );
			return get_admin_url( null, 'admin.php?page=wpcf7&post=' . $cf7_form->id() . '&action=edit' );
		}

		return '';
	}

	public static function createSubscriber( $form_id = '', $fields = array() ) {
		$form_settings = self::getFormSettings( $form_id );

		// Not connected to רב מסר (no settings, or saved without a list): nothing to send, the form works as a plain CF7 form.
		if ( empty( $form_settings ) || empty( PluginHelpers::getVal( $form_settings, 'list' ) ) ) {
			return true;
		}

		$chosen_system = $form_settings['chosen_system'];

		// PR1-I20: the old system has one name field - first + last name are sent as the name when "שם מלא" is empty.
		if ( 'responder' === $chosen_system ) {
			list( $fields, $form_settings ) = self::addNameFromFirstAndLast( $fields, $form_settings );
		}

		$fields = ContactForm7FormHelper::formatSubscriberFields( $fields, $form_settings );

		$subscriber_details = array(
			'list_id' => $form_settings['list'],
			'fields'  => $fields,
		);

		switch ( $chosen_system ) {
			case 'responder':
				$subscriber_details['onexisting'] = PluginHelpers::getVal( $form_settings, 'onexisting' );
				break;
			case 'responder_live':
				$subscriber_details['tags']                = PluginHelpers::getVal( $form_settings, 'tags', array() );
				$subscriber_details['onexisting_rejoin']   = PluginHelpers::getVal( $form_settings, 'onexisting_rejoin' );
				$subscriber_details['onexisting_joindate'] = PluginHelpers::getVal( $form_settings, 'onexisting_joindate' );
				$subscriber_details['onexisting_sms']      = PluginHelpers::getVal( $form_settings, 'onexisting_sms' );
				break;
		}

		// PR1-I10: false when the lead did not reach רב מסר (the visitor then gets an error).
		return PluginAPI::run( $chosen_system )->submitSubscriber(
			$subscriber_details,
			array(
				'integration' => 'Contact Form 7',
				'form'        => '#' . $form_id,
			)
		);
	}

	/**
	 * PR1-I20: רב מסר הישנה has only NAME. When "שם מלא" is not mapped or was left empty, and "שם פרטי" /
	 * "שם משפחה" are mapped and filled, "<first> <last>" is sent as the name.
	 *
	 * @param array $fields        The submitted CF7 fields (field name => value).
	 * @param array $form_settings The form's רב מסר settings.
	 * @return array array( $fields, $form_settings ) - with the combined name when it applies.
	 */
	public static function addNameFromFirstAndLast( $fields = array(), $form_settings = array() ) {
		$field_value = function ( $setting ) use ( $fields, $form_settings ) {
			$field_name = trim( (string) PluginHelpers::getVal( $form_settings, $setting, '' ), '[] ' );
			$value      = '' !== $field_name && isset( $fields[ $field_name ] ) ? $fields[ $field_name ] : '';
			$value      = is_array( $value ) ? implode( ' ', $value ) : $value;

			return is_scalar( $value ) ? trim( (string) $value ) : '';
		};

		if ( '' !== $field_value( 'name' ) ) {
			return array( $fields, $form_settings );
		}

		$full_name = trim( $field_value( 'first' ) . ' ' . $field_value( 'last' ) );

		if ( '' === $full_name ) {
			return array( $fields, $form_settings );
		}

		$fields['rmp-full-name-from-first-last'] = $full_name;
		$form_settings['name']                   = '[rmp-full-name-from-first-last]';

		return array( $fields, $form_settings );
	}

	/**
	 * True when the form has a רב מסר list saved in its panel. Reads the stored option only –
	 * no API call, no write (used by the front-end phone check, PR1-I8).
	 */
	public static function isFormConnected( $form_id = '' ) {
		if ( empty( $form_id ) ) {
			return false;
		}

		$form_settings = PluginOptionsManager::run()->getOption( self::OPTION_PREFIX . $form_id, array() );

		if ( empty( $form_settings ) ) {
			return false;
		}

		$form_settings = PluginHelpers::jsonDecode( $form_settings );

		return ! empty( PluginHelpers::getVal( $form_settings, 'list' ) );
	}

	public static function getFormSettings( $form_id = '' ) {
		if ( empty( $form_id ) ) {
			return array();
		}

		$form_settings      = PluginOptionsManager::run()->getOption( self::OPTION_PREFIX . $form_id, array() );
		$save_form_settings = false;

		if ( ! empty( $form_settings ) ) {
			$form_settings   = PluginHelpers::jsonDecode( $form_settings );
			$chosen_system   = PluginHelpers::getVal( $form_settings, 'chosen_system' );
			$personal_fields = PluginHelpers::getVal( $form_settings, 'personal_fields', array() );

			if ( empty( $chosen_system ) ) {
				$chosen_system      = $form_settings['chosen_system'] = 'responder';
				$save_form_settings = true;
			}

			if ( empty( $personal_fields ) ) {
				$form_settings['personal_fields'] = PluginAPI::run( $chosen_system )->getPersonalFieldsByListId( (int) $form_settings['list'] );
				$save_form_settings               = true;
			}

			if ( $save_form_settings ) {
				self::saveFormSettings( $form_id, $form_settings );
			}
		}

		return $form_settings;
	}

	public static function saveFormSettings( $form_id = 0, $form_settings = array() ) {
		$form_settings_id = self::OPTION_PREFIX . $form_id;
		$form_settings    = wp_json_encode( $form_settings );

		PluginOptionsManager::run()->updateOption( $form_settings_id, $form_settings );
	}

	public static function saveFormSettingsFromPanel( $form_id = 0, $form_settings = array() ) {
		$form_settings = ContactForm7FormHelper::filterFormSettingsBeforePanelSave( $form_settings );
		self::saveFormSettings( $form_id, $form_settings );
	}
}
