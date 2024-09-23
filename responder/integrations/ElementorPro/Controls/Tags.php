<?php namespace RavMesser\Integrations\ElementorPro\Controls;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Base_Data_Control as BaseDataControl;
use RavMesser\Plugin\AJAX as PluginAJAX;
use RavMesser\Plugin\Enqueue as PluginEnqueue;

class Tags extends BaseDataControl {

	public function content_template() {
		$control_uid = $this->get_control_uid();

		include_once RAV_MESSER_TEMPLATES_DIR . '/integrations/elementor_pro/controls/tags.tpl.php';
	}

	public function enqueue() {
		PluginEnqueue::vendorSelect2();

		wp_enqueue_style(
			'rmp-tagify-css',
			PluginEnqueue::$ASSETS_URL . '/vendors/tagify/tagify.min.css',
			array(),
			'4.6.0'
		);

		wp_enqueue_script(
			'rmp-tagify-js',
			PluginEnqueue::$ASSETS_URL . '/vendors/tagify/tagify.min.js',
			array(),
			'4.6.0'
		);

		wp_enqueue_style(
			'rmp-elementor-pro-tags-control-css',
			RAV_MESSER_PLUGIN_URL . '/assets/css/integrations/elementor-pro/controls/tags.css',
			array( 'rmp-tagify-css' ),
			RAV_MESSER_VERSION
		);

		wp_enqueue_script(
			'rmp-elementor-pro-tags-control-js',
			RAV_MESSER_PLUGIN_URL . '/assets/js/integrations/elementor-pro/controls/tags.js',
			array( 'jquery', 'rmp-tagify-js' ),
			RAV_MESSER_VERSION
		);
	}

	public function get_default_value() {
		return array();
	}

	public function get_type() {
		return 'responder_live_tags';
	}

	protected function get_default_settings() {
		return array(
			'adminUrl' => PluginAJAX::getUrl(),
			'_nonuce'  => PluginAJAX::createNonce(),
		);
	}
}
