<?php namespace RavMesser\Integrations\ContactForm7;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\Enqueue as PluginEnqueue;
use RavMesser\Integrations\ContactForm7\DataManager as ContactForm7DataManager;
use RavMesser\Integrations\ContactForm7\FormHelper as ContactForm7FormHelper;
use RavMesser\Plugin\Helpers as PluginHelpers;
use WPCF7_Submission;

class Integration {

	public static function afterSavePanel( $form ) {
		$current_action  = wpcf7_current_action();
		$current_form_id = $form->id();

		switch ( $current_action ) {
			case 'save':
				$form_data = PluginHelpers::getPostGetVariable( 'responder', PluginHelpers::SANITIZE_ARRAY );

				if ( ! empty( $form_data ) && $form_data['save_changes'] === 'true' ) {
					unset( $form_data['save_changes'] );
					ContactForm7DataManager::saveFormSettingsFromPanel( $current_form_id, $form_data );
				}
				break;
			case 'copy':
				$old_form_id = empty( $_POST['post_ID'] )
				? absint( $_REQUEST['post'] )
				: absint( $_POST['post_ID'] );

				if ( ! empty( $current_form_id ) && ! empty( $old_form_id ) ) {
					ContactForm7DataManager::copyFormSettings( $current_form_id, $old_form_id );
				}
				break;
		}
	}

	public static function editorPanelTemplate( $args ) {
		include RAV_MESSER_PLUGIN_DIR . '/templates/integrations/contact_form_7/panel.tpl.php';
	}

	public static function feedbackResponseAdditionalParams( $response, $result ) {
		$form_settings = ContactForm7DataManager::getFormSettings( $result['contact_form_id'] );

		$response['responder_url_redirect']     = PluginHelpers::getVal( $form_settings, 'url_redirect' );
		$response['responder_url_open_new_tab'] = PluginHelpers::getVal( $form_settings, 'url_open_new_tab' );
		$response['responder_pass_params']      = PluginHelpers::getVal( $form_settings, 'pass_params' );

		return $response;
	}

	public static function isPluginActive() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'contact-form-7/wp-contact-form-7.php' );
	}

	public static function noAjaxFallback( $contact_form ) {
		if ( wp_doing_ajax() ) {
			return;
		}

		$form_id       = $contact_form->id();
		$form_data     = WPCF7_Submission::get_instance()->get_posted_data();
		$form_settings = ContactForm7DataManager::getFormSettings( $form_id );

		$form_data = array(
			'responder_url_redirect'     => PluginHelpers::getVal( $form_settings, 'url_redirect' ),
			'responder_url_open_new_tab' => PluginHelpers::getVal( $form_settings, 'url_open_new_tab' ),
			'responder_pass_params'      => PluginHelpers::getVal( $form_settings, 'pass_params' ),
			'inputs'                     => $form_data,
		);

		self::registerFrontendEnqueue();
		wp_localize_script( 'rmp-cf7-form', 'rmpCF7MailSentData', $form_data );
	}

	public static function register() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'registerAdminEnqueue' ) );
		add_action( 'wpcf7_after_save', array( __CLASS__, 'afterSavePanel' ) );
		add_action( 'wpcf7_before_send_mail', array( __CLASS__, 'sendFormDataToAPI' ), 20, 3 );
		add_action( 'wpcf7_mail_sent', array( __CLASS__, 'noAjaxFallback' ), 20, 1 );
		add_action( 'wpcf7_enqueue_scripts', array( __CLASS__, 'registerFrontendEnqueue' ) );
		add_action( 'wpcf7_init', array( 'RavMesser\Integrations\ContactForm7\Custom\ResHiddenTag', 'register' ), 10, 0 );
		add_filter( 'wpcf7_validate_tel', array( 'RavMesser\Integrations\ContactForm7\Custom\PhoneValidation', 'validate' ), 20, 2 );
		add_filter( 'wpcf7_validate_tel*', array( 'RavMesser\Integrations\ContactForm7\Custom\PhoneValidation', 'validate' ), 20, 2 );
		add_filter( 'wpcf7_editor_panels', array( __CLASS__, 'registerEditorPanel' ) );

		if ( has_filter( 'wpcf7_feedback_response' ) ) {
			$wpcf7_filter_backward_compatibility = 'wpcf7_feedback_response';
		} else {
			$wpcf7_filter_backward_compatibility = 'wpcf7_ajax_json_echo';
		}
		add_filter( $wpcf7_filter_backward_compatibility, array( __CLASS__, 'feedbackResponseAdditionalParams' ), 20, 2 );
	}

	public static function registerAdminEnqueue() {
		global $plugin_page;

		if ( ! isset( $plugin_page ) || ! in_array( $plugin_page, array( 'wpcf7', 'wpcf7-new' ) ) ) {
			return;
		}

		PluginEnqueue::ajaxEnqueue();
		PluginEnqueue::vendorSelect2();

		wp_enqueue_style(
			'rmp-cf7-panel',
			PluginEnqueue::$ASSETS_URL . '/css/integrations/contact-form-7/panel.css',
			array(),
			RAV_MESSER_VERSION
		);

		wp_enqueue_script(
			'rmp-cf7-panel',
			PluginEnqueue::$ASSETS_URL . '/js/integrations/contact-form-7/panel.js',
			array( 'rmp-ajax-js', 'rmp-select2-js', 'wp-util' ),
			RAV_MESSER_VERSION
		);
	}

	public static function registerEditorPanel( $panels ) {
		$responder_panel = array(
			'Responder-Extension' => array(
				'title'    => esc_html__( 'רב מסר', 'responder' ),
				'callback' => array( __CLASS__, 'editorPanelTemplate' ),
			),
		);

		return array_merge( $panels, $responder_panel );
	}

	public static function registerFrontendEnqueue() {
		if ( ! wp_script_is( 'rmp-cf7-form', 'enqueued' ) ) {
			wp_enqueue_script(
				'rmp-cf7-form',
				PluginEnqueue::$ASSETS_URL . '/js/integrations/contact-form-7/form.js',
				array( 'jquery' ),
				RAV_MESSER_VERSION
			);
		}
	}

	public static function sendFormDataToAPI( $contact_form, $data, $submission ) {
		$form_id   = $contact_form->id();
		$form_data = ContactForm7FormHelper::normalizeCF7SubmittedData(
			$contact_form->scan_form_tags(),
			$submission->get_posted_data()
		);

		ContactForm7DataManager::createSubscriber( $form_id, $form_data );
	}

	public static function tabEnqueue() {
		wp_enqueue_script(
			'rmp-cf7-tab',
			PluginEnqueue::$ASSETS_URL . '/js/integrations/contact-form-7/tab.js',
			array( 'rmp-ajax-js', 'rmp-select2-js' ),
			RAV_MESSER_VERSION
		);
	}
}
