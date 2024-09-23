<?php namespace RavMesser\Integrations\PojoForms;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\Enqueue as PluginEnqueue;
use RavMesser\Integrations\PojoForms\DataManager as PojoFormsDataManager;
use RavMesser\Plugin\Helpers as PluginHelpers;

class Integration {

	public static function addMetabox() {
		add_meta_box(
			'responder_settings',
			esc_html__( 'הגדרות רב מסר', 'responder' ),
			array( __CLASS__, 'addMetaBoxTemplate' ),
			array( 'pojo_forms' ),
			'side'
		);
	}

	public static function addMetaBoxTemplate( $post ) {
		include RAV_MESSER_PLUGIN_DIR . '/templates/integrations/pojo_forms/metabox.tpl.php';
	}

	public static function isPluginActive() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'pojo-forms/pojo-forms.php' );
	}

	public static function onMailSentCreateSubscriber( $form_id, $field_values ) {
		PojoFormsDataManager::createSubscriber( $form_id, $field_values );
	}

	public static function onPostSaveUpdateMetaboxes( $post_ID, $post, $update ) {
		if ( ! $update ) {
			return;
		}

		$metaboxes = array();

		foreach ( $_POST as $key => $value ) {
			$key = sanitize_text_field( $key );

			if ( strpos( $key, 'responder_' ) !== false ) {
				$metaboxes[ $key ] = sanitize_text_field( $value );
			}
		}

		if ( ! empty( $metaboxes ) ) {
			PojoFormsDataManager::updateMetaboxes( $post_ID, $metaboxes );
		}
	}

	public static function register() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'addMetabox' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'registerAdminEnqueue' ) );
		add_action( 'pojo_forms_mail_sent', array( __CLASS__, 'onMailSentCreateSubscriber' ), 200, 2 );
		add_action( 'save_post_pojo_forms', array( __CLASS__, 'onPostSaveUpdateMetaboxes' ), 10, 3 );
	}

	public static function registerAdminEnqueue() {
		$current_screen = get_current_screen();

		if ( $current_screen->base === 'post' && $current_screen->post_type === 'pojo_forms' ) {
			PluginEnqueue::ajaxEnqueue();
			PluginEnqueue::vendorSelect2();

			wp_enqueue_script(
				'rmp-pojo-forms-metabox',
				PluginEnqueue::$ASSETS_URL . '/js/integrations/pojo-forms/metabox.js',
				array( 'rmp-ajax-js', 'rmp-select2-js' ),
				RAV_MESSER_VERSION
			);
		}
	}

	public static function tabEnqueue() {
		wp_enqueue_script(
			'rmp-pojo-forms-tab',
			PluginEnqueue::$ASSETS_URL . '/js/integrations/pojo-forms/tab.js',
			array( 'rmp-ajax-js', 'rmp-select2-js' ),
			RAV_MESSER_VERSION
		);
	}
}
