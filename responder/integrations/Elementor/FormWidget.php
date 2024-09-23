<?php namespace RavMesser\Integrations\Elementor;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Widget_Base as ElementorWidgetBase;
use RavMesser\Integrations\Elementor\DataManager as ElementorDataManager;
use RavMesser\Integrations\Elementor\Controls\ContentControls as FormWidgetContentControls;
use RavMesser\Integrations\Elementor\Controls\StyleControls as FormWidgetStyleControls;
use RavMesser\Plugin\Helpers as PluginHelpers;

class FormWidget extends ElementorWidgetBase {

	use FormWidgetContentControls;
	use FormWidgetStyleControls;

	const CLASSNAME_PREFIX = 'responder_form_';

	private $form_settings      = array();
	private $is_form_horizontal = false;
	private $personal_fields    = array();

	public function __construct( $data = array(), $args = null ) {
		$this->before_construct();
		parent::__construct( $data, $args );
	}

	public static function generateForm( $generated_id = '' ) {
		$form_widget_classname = sanitize_key( self::CLASSNAME_PREFIX . $generated_id );
		$form_widget_class     = "class {$form_widget_classname} extends RavMesser\Integrations\Elementor\FormWidget {}";

		eval( $form_widget_class );

		return new $form_widget_classname();
	}

	public function get_categories() {
		return array( 'responder' );
	}

	public function get_icon() {
		return 'eicon-document-file';
	}

	public function get_name() {
		return 'responder_form_' . $this->form_settings['generated_id'];
	}

	public function get_script_depends() {
		return array( 'rmp-elementor-form-widget-js' );
	}

	public function get_style_depends() {
		return array( 'rmp-form-widget-elementor-css' );
	}

	public function get_title() {
		return stripcslashes( $this->form_settings['title'] );
	}

	protected function content_template() {
		include RAV_MESSER_PLUGIN_DIR . '/templates/integrations/elementor/editor_form.tpl.php';
	}

	protected function register_controls() {
		$this->addContentControls();
		$this->addStyleControls();
	}

	protected function render() {
		include RAV_MESSER_PLUGIN_DIR . '/templates/integrations/elementor/frontend_form.tpl.php';
	}

	private function before_construct() {
		$form_id = str_replace( self::CLASSNAME_PREFIX, '', get_class( $this ) );

		$this->form_settings      = ElementorDataManager::getFormSettings( $form_id );
		$this->is_form_horizontal = PluginHelpers::ifExistsAndEqual( $this->form_settings, 'form_defaults_type', 'hor' );
		$this->personal_fields    = PluginHelpers::getVal( $this->form_settings, 'list_custom_fields', array() );
	}
}
