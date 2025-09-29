<?php

/**
 * This file is the plugin's settings page render
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\OptionsManager as PluginOptionsManager;
use RavMesser\Plugin\Debugger as PluginDebugger;
use RavMesser\Integrations\Elementor\Integration as ElementorIntegration;
use RavMesser\Integrations\Elementor\DataManager as ElementorDataManager;
use RavMesser\Integrations\ContactForm7\Integration as ContactForm7Integration;
use RavMesser\Integrations\PojoForms\Integration as PojoFormsIntegration;
use RavMesser\Plugin\Helpers as PluginHelpers;

class SettingsPage {

	public static function getElementorFormsSettings() {
		$forms_data = ElementorDataManager::getFormsSettings();

		if ( empty( $forms_data ) ) {
			$forms_data = array();
		}

		return wp_json_encode( $forms_data );
	}

	public static function getUrl( $path = '' ) {
		return get_admin_url( null, 'admin.php?page=' . RAV_MESSER_MENU_SLUG . $path );
	}

	public static function isContactForm7Active() {
		return ContactForm7Integration::isPluginActive();
	}

	public static function isCurrentPage() {
		$plugin_page = PluginHelpers::getPostGetVariable( 'page', PluginHelpers::SANITIZE_TEXT_FIELD );

		if ( $plugin_page === RAV_MESSER_MENU_SLUG ) {
			return true;
		}

		return false;
	}

	public static function isDebuggerActive() {
		return PluginDebugger::isActive();
	}

	public static function isElementorActive() {
		return ElementorIntegration::isPluginActive();
	}

	public static function isPojoFormsActive() {
		return PojoFormsIntegration::isPluginActive();
	}

	public static function register() {
		if ( self::isCurrentPage() ) {
			self::saveSettings();
		}
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				__(
					'אין הרשאה מתאימה לגשת לעמוד הזה',
					'responder'
				)
			);
		}

		include_once RAV_MESSER_TEMPLATES_DIR . '/settings_page.tpl.php';
	}

	private static function saveConnectionSettings( array $settings = array() ) {
		$new_responder_live_user_token = PluginHelpers::getVal( $settings, 'responder_live_user_token' );

		if ( ! empty( $new_responder_live_user_token ) ) {
			$old_responder_live_user_token = PluginOptionsManager::run()->getOption( 'responder_live_user_token' );

			if ( $new_responder_live_user_token !== $old_responder_live_user_token ) {
				$settings['responder_live_auth_token'] = '';
			}
		}

		PluginOptionsManager::run()->updateOptions( $settings );
	}

	private static function saveSettings() {
		$action   = PluginHelpers::getPostGetVariable( 'rmp_action', PluginHelpers::SANITIZE_TEXT_FIELD );
		$settings = PluginHelpers::getPostGetVariable( 'responder', PluginHelpers::SANITIZE_ARRAY );

		switch ( $action ) {
			case 'connection_settings':
				self::saveConnectionSettings( $settings );
				break;
			case 'debugger_settings':
				PluginDebugger::saveSetting( $settings );
				break;
		}
	}
}
