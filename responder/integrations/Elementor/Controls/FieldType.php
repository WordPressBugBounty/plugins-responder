<?php namespace RavMesser\Integrations\Elementor\Controls;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Base_Data_Control as ElementorBaseDataControl;

class FieldType extends ElementorBaseDataControl {

	public function content_template() {
		$control_uid = $this->get_control_uid();

		include RAV_MESSER_TEMPLATES_DIR . '/integrations/elementor/controls/field_type.tpl.php';
	}

	public function get_type() {
		return 'responder_field_type';
	}

	protected function get_default_settings() {
		return array(
			'chosen_system' => 'responder',
			'default'       => 'text',
			'options'       => array(
				'text'        => esc_html__( 'שורת טקסט', 'responder' ),
				'textarea'    => esc_html__( 'טקסט ארוך', 'responder' ),
				'date'        => esc_html__( 'תאריך', 'responder' ),
				'number'      => esc_html__( 'מספר', 'responder' ),
				'phone'       => esc_html__( 'טלפון', 'responder' ),
				'email'       => esc_html__( 'כתובת מייל', 'responder' ),
				'choice'      => esc_html__( 'בחירה מרשימה נפתחת', 'responder' ),
				'multichoice' => esc_html__( 'בחירת כמה אפשרויות', 'responder' ),
				'bool'        => esc_html__( 'בוליאני(כן/לא)', 'responder' ),
			),
			'text_options'  => array(
				'text'     => esc_html__( 'שורת טקסט', 'responder' ),
				'textarea' => esc_html__( 'טקסט ארוך', 'responder' ),
			),
		);
	}
}
