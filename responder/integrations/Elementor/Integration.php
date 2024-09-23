<?php namespace RavMesser\Integrations\Elementor;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Plugin as Elementor;
use RavMesser\Plugin\Enqueue as PluginEnqueue;
use RavMesser\Integrations\Version as IntegrationVersion;
use RavMesser\Integrations\Elementor\Controls\CustomHeading as ElementorCustomHeadingControl;
use RavMesser\Integrations\Elementor\Controls\HiddenField as ElementorHiddenFieldControl;
use RavMesser\Integrations\Elementor\Controls\FieldType as ElementorFieldTypeControl;
use RavMesser\Integrations\Elementor\DataManager as ElementorDataManager;
use RavMesser\Integrations\Elementor\FormWidget as ElementorFormWidget;

class Integration {

	public static function isPluginActive() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'elementor/elementor.php' );
	}

	public static function register() {
		add_action( 'elementor/backend/after_register_scripts', array( __CLASS__, 'registerWidgetEditorEnqueue' ) );
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'registerWidgetCategory' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( __CLASS__, 'registerWidgetFrontendEnqueue' ) );

		if ( IntegrationVersion::isGreaterOrEqualTo( 'elementor', '3.5.0' ) ) {
			add_action( 'elementor/controls/register', array( __CLASS__, 'registerControls' ) );
			add_action( 'elementor/widgets/register', array( __CLASS__, 'registerWidgets' ) );
		} else {
			add_action( 'elementor/controls/controls_registered', array( __CLASS__, 'registerControls' ) );
			add_action( 'elementor/widgets/widgets_registered', array( __CLASS__, 'registerWidgets' ) );
		}
	}

	public static function registerControls() {
		self::register_control( 'responder_custom_heading', new ElementorCustomHeadingControl() );
		self::register_control( 'responder_hidden_field', new ElementorHiddenFieldControl() );
		self::register_control( 'responder_field_type', new ElementorFieldTypeControl() );
	}

	public static function registerWidgetCategory( $elements_manager ) {
		$elements_manager->add_category(
			'responder',
			array(
				'title' => esc_html__( 'רב מסר', 'responder' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	public static function registerWidgetEditorEnqueue() {
		wp_enqueue_style(
			'rmp-elementor-form-widget-css',
			PluginEnqueue::$ASSETS_URL . '/css/integrations/elementor/form-widget.css',
			array(),
			RAV_MESSER_VERSION
		);
	}

	public static function registerWidgetFrontendEnqueue() {
		wp_enqueue_style(
			'rmp-elementor-form-widget-css',
			PluginEnqueue::$ASSETS_URL . '/css/integrations/elementor/form-widget.css',
			array(),
			RAV_MESSER_VERSION
		);

		wp_enqueue_script(
			'rmp-elementor-form-widget-js',
			PluginEnqueue::$ASSETS_URL . '/js/integrations/elementor/form-widget.js',
			array( 'jquery', 'rmp-ajax-js' ),
			RAV_MESSER_VERSION
		);
	}

	public static function registerWidgets() {
		$forms_settings = ElementorDataManager::getFormsSettings();

		if ( ! empty( $forms_settings ) ) {
			foreach ( $forms_settings as $form_settings ) {
				self::register_widget( ElementorFormWidget::generateForm( $form_settings['generated_id'] ) );
			}
		}
	}

	public static function tabEnqueue() {
		wp_enqueue_script(
			'rmp-elementor-tab',
			PluginEnqueue::$ASSETS_URL . '/js/integrations/elementor/tab.js',
			array( 'rmp-ajax-js', 'rmp-select2-js' ),
			RAV_MESSER_VERSION
		);
	}

	private static function register_control( $name, $control ) {
		if ( IntegrationVersion::isGreaterOrEqualTo( 'elementor', '3.5.0' ) ) {
			Elementor::$instance->controls_manager->register( $control );
		} else {
			Elementor::$instance->controls_manager->register_control( $name, $control );
		}
	}

	private static function register_widget( $widget_form ) {
		if ( IntegrationVersion::isGreaterOrEqualTo( 'elementor', '3.5.0' ) ) {
			Elementor::instance()->widgets_manager->register( $widget_form );
		} else {
			Elementor::instance()->widgets_manager->register_widget_type( $widget_form );
		}
	}
}
