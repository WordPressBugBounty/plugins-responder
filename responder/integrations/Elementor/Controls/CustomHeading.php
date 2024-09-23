<?php namespace RavMesser\Integrations\Elementor\Controls;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Base_UI_Control as ElementorBaseUIControl;

class CustomHeading extends ElementorBaseUIControl {

	public function content_template() {
		include_once RAV_MESSER_TEMPLATES_DIR . '/integrations/elementor/controls/custom_heading.tpl.php';
	}

	public function get_type() {
		return 'responder_custom_heading';
	}

	protected function get_default_settings() {
		return array(
			'label_block' => true,
			'direction'   => is_rtl() ? 'rtl' : 'ltr',
		);
	}
}
