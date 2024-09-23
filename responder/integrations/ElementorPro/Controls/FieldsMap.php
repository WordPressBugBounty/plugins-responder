<?php namespace RavMesser\Integrations\ElementorPro\Controls;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Base_Data_Control as ElementorBaseDataControl;
use RavMesser\Plugin\AJAX as PluginAJAX;
use RavMesser\Integrations\ElementorPro\FieldsHelper;

class FieldsMap extends ElementorBaseDataControl {

	public function content_template() {
		$control_uid = $this->get_control_uid();

		include_once RAV_MESSER_TEMPLATES_DIR . '/integrations/elementor_pro/controls/fields_map.tpl.php';
	}

	public function enqueue() {
		wp_enqueue_script(
			'rmp-elementor-pro-fields-map-control',
			RAV_MESSER_PLUGIN_URL . '/assets/js/integrations/elementor-pro/controls/fields-map.js',
			array( 'jquery' ),
			RAV_MESSER_VERSION
		);
	}

	public function get_default_value() {
		// Why? for backward compatibility, the last
		// value was a JSON stringified value.
		// fields item look like this:
		// responder_field_{$personal_field_id}: $form_field_id
		return '{"list_id":"","fields":{}}';
	}

	public function get_type() {
		return 'responder_fields_map';
	}

	protected function get_default_settings() {
		return array(
			'adminUrl'             => PluginAJAX::getUrl(),
			'_nonuce'              => PluginAJAX::createNonce(),
			'systemChoiceSelector' => '[data-setting="responder_system_choice"]',
			'select2options'       => array(
				'dir'                     => is_rtl() ? 'rtl' : 'ltr',
				'minimumResultsForSearch' => 5,
			),
			'allowedFieldTypes'    => FieldsHelper::$ALLOWED_FIELD_TYPES,
			'fieldIdPrefix'        => FieldsHelper::$FIELD_ID_PREFIX,
			'fieldTypesMap'        => FieldsHelper::getFieldTypesMap(),
		);
	}
}
