<?php

/**
 * This file is the plugin's options manager file.
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

class OptionsManager {

	private static $instance  = null;
	private static $meta_data = array();

	private function __construct() {
		self::$meta_data = $this->initOptions(
			array(
				'Responder_Plugin_EnterUsername' => '',
				'Responder_Plugin_EnterPassword' => '',
				'Responder_Plugin__version'      => RAV_MESSER_VERSION,
				'Responder_Plugin__installed'    => 0,
				'responder_live_user_token'      => '',
				'responder_live_auth_token'      => '',
				'responder_live_enabled'         => false,
				'responder_settings_elementor'   => array(),
				'responder_settings_advanced'    => array( 'enable_debug' => 'false' ),
			)
		);
	}

	public function addOption( $optionName, $value ) {
		$value = sanitize_option( $optionName, $value );

		if ( add_option( $optionName, $value ) ) {
			self::$meta_data[ $optionName ] = $value;
		}

		return $this;
	}

	public static function createGroupOptions( $group, $options ) {
		foreach ( $options as $option ) {
			register_setting( $group, $option );
		}
	}

	public function deleteAllOptions() {
		$options = self::$meta_data;

		if ( ! empty( $options ) ) {
			foreach ( $options as $option ) {
				$this->deleteOption( $option );
			}
		}

		return $this;
	}

	public function deleteOption( $optionName ) {
		delete_option( $optionName );
		return $this;
	}

	public function getOption( $optionName, $default = '' ) {
		return get_option( $optionName, $default );
	}

	public function getOptions( array $optionNames ) {
		$options = array();

		foreach ( $optionNames as $optionName ) {
			$options[ $optionName ] = $this->getOption( $optionName );
		}

		return $options;
	}

	public static function run() {
		if ( ! self::$instance ) {
			self::$instance = new OptionsManager();
		}

		return self::$instance;
	}

	public function updateOption( $optionName, $value ) {
		if ( update_option( $optionName, $value ) ) {
			self::$meta_data[ $optionName ] = $value;
		}

		return $this;
	}

	public function updateOptions( $options = array() ) {
		foreach ( self::$meta_data as $optionName => $option ) {
			if ( isset( $options[ $optionName ] ) ) {
				$this->updateOption( $optionName, $options[ $optionName ] );
			}
		}

		return $this;
	}

	private function initOptions( $defaultOptions ) {
		$options = array();

		foreach ( $defaultOptions as $key => $value ) {
			$option = $this->getOption( $key );

			if ( $option ) {
				$options[ $key ] = $option;
			} else {
				$this->addOption( $key, $value );
				$options[ $key ] = $value;
			}
		}

		return $options;
	}
}
