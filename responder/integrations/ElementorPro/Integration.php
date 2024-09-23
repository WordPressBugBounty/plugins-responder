<?php namespace RavMesser\Integrations\ElementorPro;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Plugin as Elementor;
use ElementorPro\Plugin as ElementorPro;
use RavMesser\Integrations\Version as IntegrationVersion;
use RavMesser\Integrations\ElementorPro\Controls\FieldsMap as FieldsMapControl;
use RavMesser\Integrations\ElementorPro\Controls\SystemChoice as SystemChoiceControl;
use RavMesser\Integrations\ElementorPro\Controls\Tags as TagsControl;
use RavMesser\Integrations\ElementorPro\ActionAfterSubmit;

class Integration {

	public static function isPluginActive() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'elementor-pro/elementor-pro.php' );
	}

	public static function register() {
		add_action( 'elementor_pro/init', array( __CLASS__, 'registerActionAfterSubmit' ) );

		if ( IntegrationVersion::isGreaterOrEqualTo( 'elementor', '3.5.0' ) ) {
			add_action( 'elementor/controls/register', array( __CLASS__, 'registerControls' ) );
		} else {
			add_action( 'elementor/controls/controls_registered', array( __CLASS__, 'registerControls' ) );
		}
	}

	public static function registerActionAfterSubmit() {
		$action_after_submit = new ActionAfterSubmit();
		$forms_module        = ElementorPro::instance()->modules_manager->get_modules( 'forms' );

		if ( IntegrationVersion::isGreaterOrEqualTo( 'elementor_pro', '3.5.0' ) ) {
			$forms_module->actions_registrar->register(
				$action_after_submit,
				$action_after_submit->get_name()
			);
		} else {
			$forms_module->add_form_action(
				$action_after_submit->get_name(),
				$action_after_submit
			);
		}
	}

	public static function registerControls() {
		self::register_control( 'responder_system_choice', new SystemChoiceControl() );
		self::register_control( 'responder_fields_map', new FieldsMapControl() );
		self::register_control( 'responder_live_tags', new TagsControl() );
	}

	private static function register_control( $name, $control ) {
		if ( IntegrationVersion::isGreaterOrEqualTo( 'elementor', '3.5.0' ) ) {
			Elementor::$instance->controls_manager->register( $control );
		} else {
			Elementor::$instance->controls_manager->register_control( $name, $control );
		}
	}
}
