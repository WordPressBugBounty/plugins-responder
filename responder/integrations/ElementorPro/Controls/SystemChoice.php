<?php namespace RavMesser\Integrations\ElementorPro\Controls;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\API as PluginAPI;
use Elementor\Base_Data_Control as ElementorBaseDataControl;

class SystemChoice extends ElementorBaseDataControl {

	private $connected_systems       = array();
	private $connected_systems_count = 0;

	public function __construct() {
		$this->connected_systems       = PluginAPI::getConnectedSystemsNames();
		$this->connected_systems_count = count( $this->connected_systems );

		parent::__construct();
	}

	public function content_template() {
		$control_uid = $this->get_control_uid();

		include_once RAV_MESSER_TEMPLATES_DIR . '/integrations/elementor_pro/controls/system_choice.tpl.php';
	}

	public function enqueue() {
		wp_enqueue_script(
			'rmp-elementor-pro-system-choice-control',
			RAV_MESSER_PLUGIN_URL . '/assets/js/integrations/elementor-pro/controls/system-choice.js',
			array( 'jquery' ),
			RAV_MESSER_VERSION
		);
	}

	public function get_default_value() {
		if ( isset( $this->connected_systems[0] ) and ! empty( $this->connected_systems[0] ) ) {
			return $this->connected_systems[0];
		}

		return '';
	}

	public function get_type() {
		return 'responder_system_choice';
	}

	protected function get_default_settings() {
		return array(
			'connectedSystemsCount' => $this->connected_systems_count,
			'options'               => array(
				// Display order and names only. The default (get_default_value) stays the first
				// connected system: Elementor does not save a value equal to the default, so
				// changing it would switch existing forms (PR1-I5, PR1-I25).
				'responder_live' => esc_html__( 'רב מסר', 'responder' ),
				'responder'      => esc_html__( 'רב מסר הישנה', 'responder' ),
			),
		);
	}
}
