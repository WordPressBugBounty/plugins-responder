<?php namespace RavMesser\Integrations\Elementor\Controls;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Base_Data_Control as ElementorBaseDataControl;

class HiddenField extends ElementorBaseDataControl {

	public function content_template() {
		$control_uid = $this->get_control_uid();

		include RAV_MESSER_TEMPLATES_DIR . '/integrations/elementor/controls/hidden_field.tpl.php';
	}

	public function enqueue() {
		wp_enqueue_script(
			'rmp-elementor-pro-hidden-field-control',
			RAV_MESSER_PLUGIN_URL . '/assets/js/integrations/elementor/controls/hidden-field.js',
			array( 'jquery' ),
			RAV_MESSER_VERSION
		);
	}

	public function get_type() {
		return 'responder_hidden_field';
	}

	protected function get_default_settings() {
		return array(
			'input_type' => 'text',
		);
	}
}
