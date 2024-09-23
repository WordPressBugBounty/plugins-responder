<?php namespace RavMesser\Integrations\PojoForms;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\Helpers as PluginHelpers;

class FormHelper {

	private $form_fields = array();
	private $metaboxes   = array();

	public function __construct( $form_data, $personal_fields ) {
		$this->setFormFields( $form_data, $personal_fields );

		$this->setDefaultMetaboxes( $form_data );
		$this->setFormFieldsMetaboxes( $this->form_fields );
		$this->setFieldsMapMetabox( $form_data, $this->form_fields, $personal_fields );
	}

	public static function formatSubscriberFields( $form_values, $fields_map, $personal_fields ) {
		$subscriber_fields = array();
		$personal_fields   = PluginHelpers::arrayToAssoc( $personal_fields, 'id' );

		foreach ( $form_values as $form_value ) {
			foreach ( $fields_map as $field_map_key => $field_map_value ) {
				if ( $form_value['title'] === $field_map_value ) {
					$field_id = str_replace( 'responder_field_', '', $field_map_key );

					if ( isset( $personal_fields[ $field_id ] ) ) {
						$subscriber_fields[ $field_id ] = array_merge(
							$personal_fields[ $field_id ],
							array( 'value' => $form_value['value'] )
						);
					}
					break;
				}
			}
		}

		return $subscriber_fields;
	}

	public function getMetaboxes() {
		return $this->metaboxes;
	}

	private static function convertFieldType( $type ) {
		switch ( $type ) {
			case 'email':
				$type = 'email';
				break;
			case 'phone':
				$type = 'tel';
				break;
			default:
				$type = 'text';
				break;
		}

		return $type;
	}

	private function setDefaultMetaboxes( $data ) {
		$this->metaboxes['form_id']                = PluginHelpers::getVal( $data, 'list_id', 0 );
		$this->metaboxes['form_style_button_text'] = PluginHelpers::getVal( $data, 'submit_text', esc_html__( 'Send', 'pojo-forms' ) );
		$this->metaboxes['form_redirect_to']       = PluginHelpers::getVal( $data, 'thank_you_url' );
		$this->metaboxes['form_email_form_name']   = esc_html__( 'טופס משולב רב מסר', 'responder' );
		$this->metaboxes['form_email_subject']     = esc_html__( 'פנייה חדשה מטופס האתר', 'responder' );
		$this->metaboxes['form_email_form']        = 'noreply@' . wp_parse_url( home_url(), PHP_URL_HOST );
		$this->metaboxes['form_email_to']          = get_option( 'admin_email' );
	}

	private function setFieldsMapMetabox( $form_data, $form_fields, $personal_fields ) {
		$form_fields                              = PluginHelpers::arrayToAssoc( $form_fields, 'personal_field_id' );
		$responder_values['responder_onexisting'] = PluginHelpers::getVal( $form_data, 'action_on_existing', 'update' );

		foreach ( $personal_fields as $personal_field ) {
			$responder_value_key                      = "responder_field_{$personal_field['id']}";
			$responder_values[ $responder_value_key ] = '';

			if ( isset( $form_fields[ $personal_field['id'] ] ) ) {
				$responder_values[ $responder_value_key ] = $form_fields[ $personal_field['id'] ]['name'];
			}
		}

		$this->metaboxes['responder_pojo_responder_values'] = $responder_values;
	}

	private function setFormFields( $form_data, $personal_fields ) {
		$form_fields     = array();
		$show_fields     = PluginHelpers::getVal( $form_data, 'show-field', array() );
		$required_fields = PluginHelpers::getVal( $form_data, 'required-field', array() );
		$personal_fields = PluginHelpers::arrayToAssoc( $personal_fields, 'id' );
		$duplicate_names = array();

		foreach ( $show_fields as $index => $field_id ) {
			$field_name = $personal_fields[ $field_id ]['name'];

			$duplicate_name_count           = PluginHelpers::getVal( $duplicate_names, $field_name, -1 );
			$duplicate_names[ $field_name ] = $duplicate_name_count + 1;

			if ( $duplicate_name_count > -1 ) {
				$field_name                     = "{$field_name} {$duplicate_names[$field_name]}";
				$duplicate_names[ $field_name ] = 0;
			}

			$form_field = array(
				'field_id'          => $index,
				'name'              => $field_name,
				'personal_field_id' => $field_id,
				'placeholder'       => $field_name,
				'required'          => in_array( $field_id, $required_fields ) ? 1 : 0,
				'type'              => self::convertFieldType( $personal_fields[ $field_id ]['type'] ),
			);

			array_push( $form_fields, $form_field );
		}

		$this->form_fields = $form_fields;
	}

	private function setFormFieldsMetaboxes( $form_fields ) {
		$this->metaboxes['form_fields'] = count( $form_fields );

		foreach ( $form_fields as $index => $field ) {
			foreach ( $field as $key => $value ) {
				$this->metaboxes[ "form_fields[{$index}][{$key}]" ] = $value;
			}
		}
	}
}
