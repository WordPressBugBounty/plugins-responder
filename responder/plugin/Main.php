<?php

/**
 * This file is the plugin's main file
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\AJAX;
use RavMesser\Plugin\Debugger;
use RavMesser\Plugin\Enqueue;
use RavMesser\Plugin\Settings;
use RavMesser\Plugin\SettingsPage;

class Main {

	public static function loadPluginTextdomain() {
		$locale = get_user_locale();

		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		}

		if ( $locale !== 'he_IL' && ! SettingsPage::isCurrentPage() ) {
			load_textdomain( 'responder', RAV_MESSER_PLUGIN_DIR . '/languages/responder-en_EN.mo' );
		}
	}

	public static function register() {
		add_action( 'init', array( __CLASS__, 'loadPluginTextdomain' ) );

		AJAX::register();
		Enqueue::register();
		Settings::register();
		SettingsPage::register();
		Debugger::register();
	}
}
