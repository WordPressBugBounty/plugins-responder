<?php namespace RavMesser\Integrations\PojoForms;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\API as PluginAPI;
use RavMesser\Integrations\PojoForms\FormHelper as PojoFormHelper;
use RavMesser\Plugin\Helpers as PluginHelpers;

class DataManager {

	public static function createPojoForm( $form_data ) {
		$form_name       = PluginHelpers::getVal( $form_data, 'form_name' );
		$list_id         = PluginHelpers::getVal( $form_data, 'list_id', 0 );
		$personal_fields = PluginAPI::run( 'responder' )->getPersonalFieldsByListId( (int) $list_id );
		$form_helper     = new PojoFormHelper( $form_data, $personal_fields );

		// Create new Pojo Form
		$form_id = wp_insert_post(
			array(
				'post_title'  => $form_name,
				'post_type'   => 'pojo_forms',
				'post_status' => 'publish',
			)
		);

		if ( $form_id ) {
			$metaboxes = $form_helper->getMetaboxes();

			foreach ( $metaboxes as $metabox_name => $metabox_value ) {
				update_post_meta( $form_id, $metabox_name, $metabox_value );
			}

			return get_admin_url( null, 'post.php?post=' . $form_id . '&action=edit' );
		}

		return '';
	}

	public static function createSubscriber( $post_id, $form_values ) {
		$list_id    = get_post_meta( $post_id, 'form_id', true );
		$fields_map = get_post_meta( $post_id, 'responder_pojo_responder_values', true );

		if ( ! empty( $list_id ) ) {
			$personal_fields = PluginAPI::run( 'responder' )->getPersonalFieldsByListId( (int) $list_id );
			$on_existing     = PluginHelpers::getVal( $fields_map, 'responder_onexisting', 'update' );
			$fields          = PojoFormHelper::formatSubscriberFields( $form_values, $fields_map, $personal_fields );

			$subscriber_details = array(
				'list_id'    => $list_id,
				'fields'     => $fields,
				'onexisting' => $on_existing,
			);

			PluginAPI::run( 'responder' )->createSubscriber( $subscriber_details );
		}
	}

	public static function updateMetaboxes( $post_id, $metaboxes ) {
		$fields_map = array(
			'responder_onexisting' => $metaboxes['responder_onexisting'],
		);

		foreach ( $metaboxes as $key => $value ) {
			if ( strpos( $key, 'responder_field_' ) !== false ) {
				$fields_map[ $key ] = $value;
			}
		}

		update_post_meta( $post_id, 'form_id', $metaboxes['responder_form_id'] );
		update_post_meta( $post_id, 'responder_pojo_responder_values', $fields_map );
	}
}
