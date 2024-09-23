<?php namespace RavMesser\Integrations\ContactForm7;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use WPCF7_ContactFormTemplate;
use RavMesser\Plugin\Helpers as PluginHelpers;

class FormHelper {

	const PREFIXED_FIELD_IDS = array( 'name', 'email', 'phone', 'first', 'last' );

	private $form_settings;
	private $formatted_form_fields;

	public function __construct( $form_settings = array(), $personal_fields = array() ) {
		$this->form_settings = $form_settings;

		$filtered_fields = self::filterFormFields( $this->form_settings );

		$this->formatted_form_fields = self::formatFormFields( $filtered_fields, $personal_fields );
	}

	public static function filterFormSettingsBeforePanelSave( $form_settings = array() ) {
		$custom_keys    = array_filter( $form_settings, array( __CLASS__, 'filterCustomKeys' ), ARRAY_FILTER_USE_KEY );
		$custom_values  = array_filter( $form_settings, array( __CLASS__, 'filterCustomValues' ), ARRAY_FILTER_USE_KEY );
		$default_fields = array_filter( $form_settings, array( __CLASS__, 'filterDefaultFields' ), ARRAY_FILTER_USE_KEY );
		$form_fields    = array_filter( $form_settings, array( __CLASS__, 'filterOutCustomFields' ), ARRAY_FILTER_USE_KEY );

		ksort( $custom_keys );

		$custom_field_index  = 1;
		$custom_option_index = 1;
		$brackets_regexp     = '/[\[\]]/';

		foreach ( $custom_keys as $custom_key => $custom_key_value ) {
			$indexes          = self::getCustomFieldIndexes( $custom_key );
			$custom_value_key = str_replace( 'Key', 'Value', $custom_key );
			$custom_value     = PluginHelpers::getVal( $custom_values, $custom_value_key );

			if ( ! empty( $custom_value ) && ! empty( $custom_key_value ) ) {
				$custom_value = preg_replace( $brackets_regexp, '', $custom_value );
				$custom_index = $custom_field_index;

				if ( ! empty( $indexes['option'] ) ) {
					$custom_index = "{$custom_index}_{$custom_option_index}";
				}

				if ( PluginHelpers::ifExistsAndNotEmpty( $form_fields, "CustomKey{$custom_index}" ) ) {
					if ( ! empty( $indexes['option'] ) ) {
						$custom_option_index++;

						$custom_index = "{$custom_field_index}_{$custom_option_index}";
					} else {
						$custom_option_index = 1;
						$custom_field_index++;

						$custom_index = $custom_field_index;
					}
				}

				$form_fields[ "CustomValue{$custom_index}" ] = "[{$custom_value}]";
				$form_fields[ "CustomKey{$custom_index}" ]   = (int) $custom_key_value;
			}
		}

		foreach ( $default_fields as $default_index => $default_value ) {
			$default_value = preg_replace( $brackets_regexp, '', $default_value );

			if ( ! empty( $default_value ) ) {
				$default_value = "[{$default_value}]";
			}

			$form_fields[ $default_index ] = $default_value;
		}

		return $form_fields;
	}

	public static function formatFieldValue( $type = '', $id = '', $value = '', $form_settings = array() ) {
		$formatted_value = $value;

		switch ( $type ) {
			case 'bool':
				$formatted_value = strtolower( $value ) === 'yes';
				break;

			case 'choice':
			case 'multichoice':
				if ( is_array( $value ) ) {
					$custom_fields   = self::getCustomFieldsAndOptions( $form_settings );
					$custom_fields   = PluginHelpers::arrayToAssoc( $custom_fields, 'key' );
					$custom_field    = PluginHelpers::getVal( $custom_fields, $id );
					$custom_options  = PluginHelpers::arrayToAssoc( $custom_field['options'], 'value' );
					$formatted_value = array();

					foreach ( $value as $option_value ) {
						if ( PluginHelpers::ifExistsAndNotEmpty( $custom_options, "[{$option_value}]" ) ) {
							$formatted_value[] = $custom_options[ "[{$option_value}]" ]['key'];
						}
					}
				}
				break;

			case 'text':
			case 'textarea':
				if ( is_array( $value ) ) {
					$formatted_value = implode( ', ', $value );
				}
				break;
		}

		return $formatted_value;
	}

	public function formatFormSettings() {
		$formatted_form_settings = array(
			'chosen_system'       => PluginHelpers::getVal( $this->form_settings, 'chosen_system', 'responder' ),
			'list'                => PluginHelpers::getVal( $this->form_settings, 'list', 0 ),
			'onexisting_joindate' => PluginHelpers::getVal( $this->form_settings, 'onexisting_joindate' ),
			'onexisting_rejoin'   => PluginHelpers::getVal( $this->form_settings, 'onexisting_rejoin' ),
			'onexisting'          => PluginHelpers::getVal( $this->form_settings, 'action_on_existing' ),
			'pass_params'         => PluginHelpers::getVal( $this->form_settings, 'thank_you_params' ),
			'tags'                => PluginHelpers::getVal( $this->form_settings, 'tags', array() ),
			'url_open_new_tab'    => PluginHelpers::getVal( $this->form_settings, 'thank_you_newtab' ),
			'url_redirect'        => PluginHelpers::getVal( $this->form_settings, 'thank_you_url' ),
		);

		$custom_field_index = 1;

		foreach ( $this->formatted_form_fields as $field ) {
			if ( is_numeric( $field['id'] ) ) {
				$formatted_form_settings[ "CustomValue{$custom_field_index}" ] = "[{$field['field_id']}]";
				$formatted_form_settings[ "CustomKey{$custom_field_index}" ]   = $field['id'];

				if ( PluginHelpers::ifExistsAndNotEmpty( $field, 'options' ) ) {
					$custom_option_index = 1;

					foreach ( $field['options'] as $option ) {
						$formatted_form_settings[ "CustomValue{$custom_field_index}_{$custom_option_index}" ] = "[{$option['id']}]";
						$formatted_form_settings[ "CustomKey{$custom_field_index}_{$custom_option_index}" ]   = $option['id'];
						$custom_option_index++;
					}
				}

				$custom_field_index++;
			} else {
				$formatted_form_settings[ $field['id'] ] = "[{$field['field_id']}]";
			}
		}

		return $formatted_form_settings;
	}

	public static function formatSubscriberFields( $form_fields = array(), $form_settings = array() ) {
		$personal_fields  = PluginHelpers::arrayToAssoc( $form_settings['personal_fields'], 'id' );
		$formatted_fields = array();
		$index_regexp     = '/(?<index>\d+?)$/';

		foreach ( $form_fields as $field_name => $field_value ) {
			$field_id = array_search( "[{$field_name}]", $form_settings, true );

			if ( strpos( $field_id, 'CustomValue' ) !== false && preg_match( $index_regexp, $field_id, $matches ) ) {
				$field_id = $form_settings[ "CustomKey{$matches['index']}" ];
			}

			if ( PluginHelpers::ifExistsAndNotEmpty( $personal_fields, $field_id ) ) {
				$formatted_field_value = self::formatFieldValue(
					$personal_fields[ $field_id ]['type'],
					$field_id,
					$field_value,
					$form_settings
				);

				$formatted_fields[ $field_id ] = array_merge(
					$personal_fields[ $field_id ],
					array( 'value' => $formatted_field_value )
				);
			}
		}

		return $formatted_fields;
	}

	public static function getCustomFieldsAndOptions( $form_settings ) {
		$custom_values = array_filter( $form_settings, array( __CLASS__, 'filterCustomValues' ), ARRAY_FILTER_USE_KEY );
		$custom_keys   = array_filter( $form_settings, array( __CLASS__, 'filterCustomKeys' ), ARRAY_FILTER_USE_KEY );
		$custom_fields = array();

		ksort( $custom_keys );

		foreach ( $custom_keys as $custom_key => $custom_key_value ) {
			$indexes = self::getCustomFieldIndexes( $custom_key );

			if ( ! empty( $indexes['field'] ) && ! empty( $indexes['option'] ) ) {
				$custom_fields[ $indexes['field'] ]['options'][] = array(
					'key'   => (int) $custom_key_value,
					'value' => $custom_values[ "CustomValue{$indexes['field']}_{$indexes['option']}" ],
				);
			} elseif ( ! empty( $indexes['field'] ) ) {
				$custom_fields[ $indexes['field'] ] = array(
					'key'   => (int) $custom_key_value,
					'value' => $custom_values[ "CustomValue{$indexes['field']}" ],
				);
			}
		}

		return PluginHelpers::assocToArray( $custom_fields );
	}

	public function getFormTemplate() {
		$button_text = PluginHelpers::getVal( $this->form_settings, 'submit_text' );
		$template    = '';

		foreach ( $this->formatted_form_fields as $form_field ) {
			$template .= self::createFieldTag( $form_field ) . PHP_EOL . PHP_EOL;
		}

		$template .= "[submit \"{$button_text}\"]";

		return $template;
	}

	public function getMail() {
		$body_template = array();

		foreach ( $this->formatted_form_fields as $form_field ) {
			array_push( $body_template, "{$form_field['name']}: [{$form_field['field_id']}]" );
		}

		if ( ! empty( $body_template ) ) {
			$body_template = implode( PHP_EOL . PHP_EOL, $body_template );
		}

		return array_merge(
			WPCF7_ContactFormTemplate::mail(),
			array(
				'subject' => esc_html__( 'פנייה חדשה מטופס האתר', 'responder' ),
				'body'    => $body_template,
			)
		);
	}

	public static function getMessages() {
		return array(
			'mail_sent_ok'             => esc_html__( 'הטופס נשלח בהצלחה', 'responder' ),
			'mail_sent_ng'             => esc_html__( 'הטופס לא נשלח, בדקו שכל שדות החובה מלאים ונסו שוב', 'responder' ),
			'validation_error'         => esc_html__( 'מלאו את שדות החובה ושלחו שוב את הטופס', 'responder' ),
			'spam'                     => esc_html__( 'התקבלה שגיאה בזמן השליחה של הטופס, נסו שוב עוד כמה דקות', 'responder' ),
			'accept_terms'             => esc_html__( 'אשרו את התנאים לשליחת הטופס', 'responder' ),
			'invalid_required'         => esc_html__( 'זהו שדה חובה', 'responder' ),
			'invalid_too_long'         => esc_html__( 'מספר התווים ארוך מידי, בדקו שוב את השדה', 'responder' ),
			'invalid_too_short'        => esc_html__( 'מספר התווים קצר מידי, בדקו שוב', 'responder' ),
			'invalid_date'             => esc_html__( 'התאריך שנכתב לא תקין', 'responder' ),
			'date_too_early'           => esc_html__( 'התאריך שהוזן לא תואם את התאריכים האפשריים', 'responder' ),
			'date_too_late'            => esc_html__( 'התאריך שהוזן לא תואם את התאריכים האפשריים', 'responder' ),
			'upload_failed'            => esc_html__( 'התקבלה שגיאה בזמן העלאת הקובץ. אפשר לנסות שוב עוד כמה דקות', 'responder' ),
			'upload_file_type_invalid' => esc_html__( 'לא ניתן לצרף קובץ מסוג זה', 'responder' ),
			'upload_file_too_large'    => esc_html__( 'הקובץ גדול מידי', 'responder' ),
			'upload_failed_php_error'  => esc_html__( 'התקבלה שגיאה בזמן העלאת הקובץ. נסו שוב', 'responder' ),
			'invalid_number'           => esc_html__( 'בדקו שהמספר שהוזן נכון', 'responder' ),
			'number_too_small'         => esc_html__( 'מספר התווים נמוך מהמינימום המותר', 'responder' ),
			'number_too_large'         => esc_html__( 'מספר התווים גבוהה מהמקסימום המותר', 'responder' ),
			'quiz_answer_not_correct'  => esc_html__( 'התשובה לא נכונה, נסו שוב', 'responder' ),
			'invalid_email'            => esc_html__( 'בדקו שכתובת המייל תקינה', 'responder' ),
			'invalid_url'              => esc_html__( 'בדקו שכתובת הקישור תקינה', 'responder' ),
			'invalid_tel'              => esc_html__( 'מספר הטלפון לא תקין', 'responder' ),
		);
	}

	public static function normalizeCF7SubmittedData( $tags = array(), $data = array() ) {
		foreach ( $data as $name => &$value ) {
			$tag = PluginHelpers::findObjectByKeyValue( $tags, 'name', $name );

			if ( $tag['basetype'] === 'acceptance' ) {
				$value = empty( $value ) ? 'NO' : 'YES';
			}
		}

		return $data;
	}

	private static function convertFieldId( $field_id = '' ) {
		if ( in_array( $field_id, self::PREFIXED_FIELD_IDS ) ) {
			return "your-{$field_id}";
		}

		return "field-{$field_id}";
	}

	private static function convertFieldType( $type = '', $field = array() ) {
		if ( PluginHelpers::ifExistsAndEqual( $field, 'is_hidden', true ) ) {
			return 'hidden';
		}

		switch ( $type ) {
			case 'email':
			case 'date':
			case 'number':
			case 'textarea':
				break;
			case 'bool':
				$type = 'acceptance';
				break;
			case 'phone':
				$type = 'tel';
				break;
			case 'choice':
				$type = 'select';
				break;
			case 'multichoice':
				$type = 'checkbox';
				break;
			default:
				$type = 'text';
				break;
		}

		return $type;
	}

	private static function createFieldTag( $field ) {
		$name          = $field['name'];
		$uri_param     = $field['uri_param'];
		$type          = $field['type'];
		$field_id      = $field['field_id'];
		$options       = PluginHelpers::getVal( $field, 'options' );
		$is_required   = PluginHelpers::ifExistsAndEqual( $field, 'is_required', true );
		$hidden_value  = PluginHelpers::getVal( $field, 'hidden_value' );
		$hidden_action = PluginHelpers::getVal( $field, 'hidden_action' );

		if ( ! empty( $options ) ) {
			$choices = '';

			foreach ( $options as $option ) {
				$choices .= "\"{$option['name']}|{$option['id']}\" ";
			}

			$options = $choices;
		}

		if ( $is_required ) {
			$name .= ' ' . esc_html__( '(חובה)', 'responder' );

			if ( $type !== 'acceptance' ) {
				$type .= '*';
			}
		} elseif ( ! $is_required && $type == 'acceptance' ) {
			$options = 'optional';
		}

		switch ( $field['type'] ) {
			case 'hidden':
				if ( $hidden_action === 'param' ) {
					$field = "[res_hidden {$field_id} getvalue=\"{$uri_param}\"]";
				} else {
					$field = "[hidden {$field_id} value=\"{$hidden_value}\"]";
				}
				break;
			case 'checkbox':
				$field = "<span>{$name}" . PHP_EOL . "    [{$type} {$field_id} {$options}]</span>";
				break;
			case 'acceptance':
				$field = "[{$type} {$field_id} {$options}] {$name} [/{$type}]";
				break;
			default:
				$field = "<label>{$name}" . PHP_EOL . "    [{$type} {$field_id} {$options}]</label>";
				break;
		}

		$field = preg_replace( '/\s+]/', ']', $field );

		return $field;
	}

	private static function filterCustomKeys( $key ) {
		return strpos( $key, 'CustomKey' ) === 0;
	}

	private static function filterCustomValues( $key ) {
		return strpos( $key, 'CustomValue' ) === 0;
	}

	private static function filterDefaultFields( $key ) {
		return in_array( $key, self::PREFIXED_FIELD_IDS );
	}

	private static function filterFormFields( $form_settings = array() ) {
		$fields = array();

		foreach ( $form_settings as $form_field => $field_ids ) {
			switch ( $form_field ) {
				case 'show-field':
					foreach ( $field_ids as $field_id ) {
						$fields[ $field_id ]['is_show'] = true;
					}
					break;
				case 'required-field':
					foreach ( $field_ids as $field_id ) {
						$fields[ $field_id ]['is_required'] = true;
					}
					break;
				case 'hidden-field':
					foreach ( $field_ids as $field_id ) {
						$fields[ $field_id ]['is_hidden']     = true;
						$fields[ $field_id ]['hidden_action'] = PluginHelpers::getVal( $form_settings, 'hidden-field-value-action-' . $field_id );
						$fields[ $field_id ]['hidden_value']  = PluginHelpers::getVal( $form_settings, 'hidden-field-value-' . $field_id );
					}
					break;
			}
		}

		return $fields;
	}

	private static function filterOutCustomFields( $key ) {
		return ! in_array( $key, self::PREFIXED_FIELD_IDS ) && strpos( $key, 'Custom' ) === false;
	}

	private static function formatFormFields( $form_fields = array(), $personal_fields = array() ) {
		$fields          = array();
		$personal_fields = PluginHelpers::arrayToAssoc( $personal_fields, 'id' );

		foreach ( $form_fields as $form_field_id => $form_field ) {
			$personal_field = PluginHelpers::getVal( $personal_fields, $form_field_id, array() );

			if ( ! empty( $personal_field ) ) {
				$type     = self::convertFieldType( $personal_field['type'], $form_field );
				$field_id = self::convertFieldId( $personal_field['id'] );

				$fields[] = array_merge(
					$personal_field,
					$form_field,
					array(
						'field_id' => $field_id,
						'type'     => $type,
					)
				);
			}
		}

		return $fields;
	}

	private static function getCustomFieldIndexes( $key_string ) {
		$indexes_regexp = '/(?<field_index>\d+?)(_(?<option_index>\d+?))?$/';

		preg_match( $indexes_regexp, $key_string, $matched_indexes );

		return array(
			'field'  => PluginHelpers::getVal( $matched_indexes, 'field_index' ),
			'option' => PluginHelpers::getVal( $matched_indexes, 'option_index' ),
		);
	}
}
