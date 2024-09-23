<?php

/**
 * This file is the plugin's assets enqueue file
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\AJAX as PluginAJAX;
use RavMesser\Plugin\SettingsPage as PluginSettingsPage;
use RavMesser\Integrations\Elementor\Integration as ElementorIntegration;
use RavMesser\Integrations\ContactForm7\Integration as ContactForm7Integration;
use RavMesser\Integrations\PojoForms\Integration as PojoFormsIntegration;

class Enqueue {

	public static $ASSETS_URL = RAV_MESSER_PLUGIN_URL . '/assets';

	public static function adminEnqueue() {
		if ( PluginSettingsPage::isCurrentPage() ) {
			self::adminVendors();
			self::adminStyles();
			self::adminScripts();
		}
	}

	public static function ajaxEnqueue() {
		wp_enqueue_script(
			'rmp-ajax-js',
			self::$ASSETS_URL . '/js/ajax.js',
			array( 'jquery', 'underscore' ),
			RAV_MESSER_VERSION
		);

		wp_localize_script(
			'rmp-ajax-js',
			'RMP_AJAX_LOCALS',
			array(
				'ajaxUrl'   => PluginAJAX::getUrl(),
				'_nonce'    => PluginAJAX::createNonce(),
				'direction' => is_rtl() ? 'rtl' : 'ltr',
			)
		);
	}

	public static function frontEnqueue() {
		self::ajaxEnqueue();
	}

	public static function register() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'adminEnqueue' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontEnqueue' ) );
	}

	public static function vendorSelect2() {
		wp_enqueue_style(
			'rmp-select2-css',
			self::$ASSETS_URL . '/vendors/select2/select2.min.css',
			array(),
			'4.0.13'
		);

		wp_enqueue_script(
			'rmp-select2-js',
			self::$ASSETS_URL . '/vendors/select2/select2.full.min.js',
			array(),
			'4.0.13'
		);
	}

	private static function adminScripts() {
		wp_enqueue_script(
			'rmp-admin-js',
			self::$ASSETS_URL . '/js/admin.js',
			array( 'jquery', 'underscore', 'jquery-ui-tabs', 'rmp-dataTables-js' ),
			RAV_MESSER_VERSION
		);

		self::ajaxEnqueue();
		ElementorIntegration::tabEnqueue();
		ContactForm7Integration::tabEnqueue();
		PojoFormsIntegration::tabEnqueue();
	}

	private static function adminStyles() {
		wp_enqueue_style(
			'rmp-admin-css',
			self::$ASSETS_URL . '/css/admin.css',
			array(),
			RAV_MESSER_VERSION
		);

		wp_enqueue_style(
			'rmp-styles',
			self::$ASSETS_URL . '/css/styles.css',
			array(),
			RAV_MESSER_VERSION
		);
	}

	private static function adminVendors() {
		wp_enqueue_style(
			'rmp-font-awesome',
			self::$ASSETS_URL . '/vendors/font-awesome/font-awesome.min.css',
			array(),
			'4.7.0'
		);

		wp_enqueue_style(
			'rmp-jquery-ui',
			self::$ASSETS_URL . '/vendors/jquery-ui.min.css',
			array(),
			'1.12.0'
		);

		wp_enqueue_style(
			'rmp-dataTables-css',
			self::$ASSETS_URL . '/vendors/DataTables/datatables.min.css',
			array(),
			'1.10.22'
		);
		wp_enqueue_script(
			'rmp-dataTables-js',
			self::$ASSETS_URL . '/vendors/DataTables/datatables.min.js',
			array(),
			'1.10.22'
		);

		self::vendorSelect2();
	}
}
