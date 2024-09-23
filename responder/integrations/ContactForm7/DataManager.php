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

		if ( empty( $form_settings ) ) {
			return false;
		}

		$fields        = ContactForm7FormHelper::formatSubscriberFields( $fields, $form_settings );
		$chosen_system = $form_settings['chosen_system'];

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
				break;
		}

		PluginAPI::run( $chosen_system )->createSubscriber( $subscriber_details );
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
