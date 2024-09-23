<?php

/**
 * This file is the plugin's settings ui and admin configurations
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Integrations\Elementor\Integration as ElementorIntegration;
use RavMesser\Integrations\ElementorPro\Integration as ElementorProIntegration;
use RavMesser\Integrations\ContactForm7\Integration as ContactForm7Integration;
use RavMesser\Integrations\PojoForms\Integration as PojoFormsIntegration;
use RavMesser\Plugin\API;
use RavMesser\Plugin\OptionsManager;

class Settings {

	public static function actionAdminMenu() {
		$admin_menu = array(
			'page_title' => self::config( 'page_title' ),
			'menu_title' => self::config( 'menu_title' ),
			'capability' => 'manage_options',
			'menu_slug'  => self::config( 'menu_slug' ),
			'callable'   => array( 'RavMesser\Plugin\SettingsPage', 'render' ),
			'icon_url'   => 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( RAV_MESSER_PLUGIN_DIR . '/assets/images/favicon.svg' ) ),
		);

		add_menu_page(
			$admin_menu['page_title'],
			$admin_menu['menu_title'],
			$admin_menu['capability'],
			$admin_menu['menu_slug'],
			$admin_menu['callable'],
			$admin_menu['icon_url']
		);
	}

	public static function actionLinks( $links ) {
		$create_form_link = sprintf(
			'<a href="%s" style="font-weight:bold">%s</a>',
			'plugins.php?page=' . self::config( 'menu_slug' ) . '#plugin_config-cf7',
			esc_html__( 'יצירת טופס', 'responder' )
		);
		array_unshift( $links, $create_form_link );

		$settings_link = sprintf(
			'<a href="%s" style="font-weight:bold">%s</a>',
			'plugins.php?page=' . self::config( 'menu_slug' ) . '#plugin_config-2',
			esc_html__( 'הגדרות', 'responder' )
		);
		array_unshift( $links, $settings_link );

		return $links;
	}

	public static function config( $key ) {
		$configuration = array(
			'menu_slug'  => RAV_MESSER_MENU_SLUG,
			'menu_title' => esc_html__( 'רב מסר - תוסף טפסים', 'responder' ),
			'page_title' => esc_html__( 'רב מסר - תוסף טפסים', 'responder' ),
		);

		return array_key_exists( $key, $configuration ) ? $configuration[ $key ] : '';
	}

	public static function disableAutoUpdates( $html, $plugin_file, $plugin_data ) {
		if ( 'responder/responder.php' === $plugin_file ) {
			$html = esc_html__( 'עדכונים אוטומטיים אינם זמינים עבור תוסף זה.', 'responder' );
		}

		return $html;
	}

	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'actionAdminMenu' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'registerIntegrations' ) );

		add_filter( 'plugin_action_links_' . RAV_MESSER_PLUGIN_BASENAME, array( __CLASS__, 'actionLinks' ) );
		add_filter( 'admin_init', array( __CLASS__, 'registerGroupOptions' ) );
		/*add_filter( 'plugin_auto_update_setting_html', array( __CLASS__, 'disableAutoUpdates' ), 10, 3 );*/
	}

	public static function registerGroupOptions() {
		OptionsManager::createGroupOptions(
			RAV_MESSER_OPTIONS_GROUP,
			array(
				'Responder_Plugin_EnterUsername',
				'Responder_Plugin_EnterPassword',
				'responder_live_user_token',
				'responder_live_auth_token',
				'responder_live_enabled',
				'responder_settings_elementor',
				'responder_settings_advanced',
			)
		);
	}

	public static function registerIntegrations() {
		if ( empty( API::getConnectedSystemsNames() ) ) {
			return false;
		}

		if ( ElementorIntegration::isPluginActive() ) {
			ElementorIntegration::register();
		}

		if ( ElementorProIntegration::isPluginActive() ) {
			ElementorProIntegration::register();
		}

		if ( ContactForm7Integration::isPluginActive() ) {
			ContactForm7Integration::register();
		}

		if ( PojoFormsIntegration::isPluginActive() ) {
			PojoFormsIntegration::register();
		}
	}
}
