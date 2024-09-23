<?php namespace RavMesser\Integrations\Elementor\Controls;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Controls_Manager as ElementorControlsMananger;
use RavMesser\Plugin\Helpers as PluginHelpers;

trait ContentControls {

	public function addContentControls() {
		$this->contentSectionGeneral();
		$this->contentSectionsFields();
		$this->contentSectionSubmitButton();
	}

	public function contentSectionGeneral() {
		$this->start_controls_section(
			'general',
			array(
				'label' => esc_html__( 'כללי', 'responder' ),
			)
		);

		$this->add_control(
			'list_name',
			array(
				'label' => sprintf(
				  /* translators: %s is a list name */
					esc_html__( 'טופס זה מקושר לרשימה: %s', 'responder' ),
					stripcslashes( $this->form_settings['list_id_unite_selected_text'] )
				),
				'type'  => 'responder_custom_heading',
			)
		);

		$this->end_controls_section();
	}

	public function contentSectionsFields() {
		$personal_fields_count = count( $this->personal_fields );

		foreach ( $this->personal_fields as $personal_field_index => $personal_field ) {
			$section_field_id = 'field_' . PluginHelpers::getVal( $personal_field, 'id', '0' );

			$section_field = array(
				'input_type'                      => PluginHelpers::getVal( $personal_field, 'type', 'text' ),
				'id'                              => $section_field_id,
				'class'                           => "responder_form_{$this->form_settings['generated_id']}_{$section_field_id}",
				'is_custom'                       => false,
				'is_must'                         => false,
				'placeholder'                     => '',
				'show_and_required_field_default' => 'yes',
				'show_field_condition'            => array(),
				'uri_param'                       => PluginHelpers::getVal( $personal_field, 'uri_param' ),
				'title'                           => stripcslashes( PluginHelpers::getVal( $personal_field, 'name' ) ),
				'order'                           => array(
					'current' => $personal_field_index + 1,
					'options' => array(),
					'total'   => $personal_fields_count,
				),
			);

			switch ( $section_field_id ) {
				case 'field_name':
					$section_field['placeholder'] = esc_html__( 'ישראל ישראלי', 'responder' );
					break;
				case 'field_first':
					$section_field['placeholder']                     = esc_html__( 'ישראל', 'responder' );
					$section_field['show_and_required_field_default'] = 'no';
					break;
				case 'field_last':
					$section_field['placeholder']                     = esc_html__( 'ישראלי', 'responder' );
					$section_field['show_and_required_field_default'] = 'no';
					break;
				case 'field_email':
					$section_field['placeholder'] = esc_html__( 'example@email.co.il', 'responder' );
					$section_field['is_must']     = true;
					break;
				case 'field_phone':
					$section_field['placeholder'] = esc_html__( '054-1234567', 'responder' );
					$section_field['is_must']     = true;
					break;
				default:
					$section_field['is_custom']                       = true;
					$section_field['show_and_required_field_default'] = 'no';
					$section_field['show_field_condition']            = array(
						'is_hidden_field_' . $section_field['id'] . '!' => 'yes',
					);
			}

			for ( $index = 0; $index < $section_field['order']['total']; $index++ ) {
				$position                                       = $index + 1;
				$section_field['order']['options'][ $position ] = $position;
			}

			$this->contentSectionField( $section_field );
		}
	}

	public function contentSectionSubmitButton() {
		$this->start_controls_section(
			'section_submit',
			array(
				'label' => esc_html__( 'כפתור', 'responder' ),
			)
		);

		$this->add_control(
			'submit_label',
			array(
				'type'    => ElementorControlsMananger::TEXT,
				'label'   => esc_html__( 'טקסט בכפתור', 'responder' ),
				'default' => esc_html__( 'הרשמה', 'responder' ),
			)
		);

		$this->add_control(
			'submit_label_divider',
			array(
				'type' => ElementorControlsMananger::DIVIDER,
			)
		);

		$this->add_control(
			'action_after_submit',
			array(
				'label'   => esc_html__( 'פעולה לאחר השליחה', 'responder' ),
				'type'    => ElementorControlsMananger::SELECT,
				'default' => 'show_confirm_message',
				'options' => array(
					'show_confirm_message'      => esc_html__( 'להציג הודעה בעמוד הנוכחי', 'responder' ),
					'redirect_to_thankyou_page' => esc_html__( 'מעבר לעמוד תודה', 'responder' ),
				),
			)
		);

		$this->add_control(
			'url_thankyou_page_str',
			array(
				'type'        => ElementorControlsMananger::TEXT,
				'label'       => esc_html__( 'כתובת עמוד תודה', 'responder' ),
				'url'         => 'https://yourlink.co.il',
				'placeholder' => 'https://yourlink.co.il',
				'condition'   => array(
					'action_after_submit' => 'redirect_to_thankyou_page',
				),
			)
		);

		$this->add_control(
			'url_thankyou_add_params',
			array(
				'type'      => ElementorControlsMananger::SWITCHER,
				'label'     => esc_html__( 'העברת פרמטרים לכתובת עמוד התודה', 'responder' ),
				'label_on'  => esc_html__( 'כן', 'responder' ),
				'label_off' => esc_html__( 'לא', 'responder' ),
				'default'   => 'no',
				'condition' => array(
					'action_after_submit' => 'redirect_to_thankyou_page',
				),
			)
		);

		$this->add_control(
			'url_thankyou_open_new_page',
			array(
				'type'      => ElementorControlsMananger::SWITCHER,
				'label'     => esc_html__( 'פתיחה בעמוד חדש', 'responder' ),
				'label_on'  => esc_html__( 'כן', 'responder' ),
				'label_off' => esc_html__( 'לא', 'responder' ),
				'default'   => 'no',
				'condition' => array(
					'action_after_submit' => 'redirect_to_thankyou_page',
				),
			)
		);

		$this->add_control(
			'submitted_text_devider',
			array(
				'type' => ElementorControlsMananger::DIVIDER,
			)
		);

		$this->add_control(
			'submitted_text',
			array(
				'type'    => ElementorControlsMananger::TEXTAREA,
				'label'   => esc_html__( 'טקסט הודעת הצלחה', 'responder' ),
				'default' => esc_html__( 'תודה על הרשמתך', 'responder' ),
			)
		);

		$this->add_control(
			'submitting_text',
			array(
				'type'    => ElementorControlsMananger::TEXT,
				'label'   => esc_html__( 'טקסט במצב שליחה', 'responder' ),
				'default' => esc_html__( 'הטופס נשלח', 'responder' ),
			)
		);

		$this->end_controls_section();
	}

	protected function contentSectionField( $section_field ) {
		$this->start_controls_section(
			'section_field_' . $section_field['id'],
			array(
				'label' => $section_field['title'],
			)
		);

		if ( $section_field['is_must'] ) {
			$this->add_control(
				'is_show_text_required' . $section_field['id'],
				array(
					'type'         => 'responder_custom_heading',
					'label'        => esc_html__( 'כתובת מייל או טלפון חייבים להופיע כשדות חובה בטופס', 'responder' ),
					'message_type' => 'alert',
					'conditions'   => array(
						'relation' => 'or',
						'terms'    => array(
							array(
								'name'     => 'is_show_' . $section_field['id'],
								'operator' => '!==',
								'value'    => 'yes',
							),
							array(
								'name'     => $section_field['id'] . '_required',
								'operator' => '!==',
								'value'    => 'yes',
							),
						),
					),
				)
			);
		}

		$this->add_control(
			'is_show_' . $section_field['id'],
			array(
				'type'      => ElementorControlsMananger::SWITCHER,
				'label'     => esc_html__( 'הוספת שדה לטופס', 'responder' ),
				'label_on'  => esc_html__( 'כן', 'responder' ),
				'label_off' => esc_html__( 'לא', 'responder' ),
				'default'   => $section_field['show_and_required_field_default'],
				'condition' => $section_field['show_field_condition'],
			)
		);

		$this->add_control(
			$section_field['id'] . '_required',
			array(
				'type'      => ElementorControlsMananger::SWITCHER,
				'label'     => esc_html__( 'שדה חובה', 'responder' ),
				'label_on'  => esc_html__( 'כן', 'responder' ),
				'label_off' => esc_html__( 'לא', 'responder' ),
				'default'   => $section_field['show_and_required_field_default'],
				'condition' => array(
					'is_show_' . $section_field['id'] => 'yes',
				),
			)
		);

		if ( $section_field['is_custom'] ) {
			$this->add_control(
				'is_hidden_field_' . $section_field['id'],
				array(
					'type'      => ElementorControlsMananger::SWITCHER,
					'label'     => esc_html__( 'שדה נסתר', 'responder' ),
					'label_on'  => esc_html__( 'כן', 'responder' ),
					'label_off' => esc_html__( 'לא', 'responder' ),
					'default'   => 'no',
					'condition' => array(
						'is_show_' . $section_field['id'] . '!' => 'yes',
					),
				)
			);

			$this->add_control(
				'hidden_field_value_' . $section_field['id'],
				array(
					'type'       => 'responder_hidden_field',
					'label'      => esc_html__( 'ערך קבוע בשדה נסתר', 'responder' ),
					'default'    => '',
					'condition'  => array(
						'is_hidden_field_' . $section_field['id'] => 'yes',
						'enable_value_from_url_' . $section_field['id'] . '!' => 'yes',
					),
					'input_type' => $section_field['input_type'],
				)
			);

			$this->add_control(
				'enable_value_from_url_' . $section_field['id'],
				array(
					'type'        => ElementorControlsMananger::SWITCHER,
					'label'       => esc_html__( 'שאיבת ערך השדה מכתובת הקישור', 'responder' ),
					'label_on'    => esc_html__( 'כן', 'responder' ),
					'label_off'   => esc_html__( 'לא', 'responder' ),
					'default'     => 'no',
					'description' => sprintf(
					/* translators: %s is a uri param */
						esc_html__( 'לדוגמה: http://yourpage.com/pagename?%s=value', 'responder' ),
						$section_field['uri_param']
					),
					'condition'   => array(
						'is_hidden_field_' . $section_field['id'] => 'yes',
					),
				)
			);
		}

		$this->add_control(
			$section_field['id'] . '_field_type',
			array(
				'type'          => 'responder_field_type',
				'label'         => esc_html__( 'סוג שדה', 'responder' ),
				'chosen_system' => $this->form_settings['chosen_system'],
				'default'       => $section_field['input_type'],
				'condition'     => array(
					'is_show_' . $section_field['id'] => 'yes',
				),
			)
		);

		$this->add_control(
			$section_field['id'] . '_field_label',
			array(
				'type'      => ElementorControlsMananger::TEXT,
				'label'     => esc_html__( 'שם השדה', 'responder' ),
				'default'   => $section_field['title'],
				'condition' => array(
					'is_show_' . $section_field['id'] => 'yes',
				),
			)
		);

		$this->add_control(
			$section_field['id'] . '_field_placeholder',
			array(
				'type'        => ElementorControlsMananger::TEXT,
				'label'       => esc_html__( 'טקסט לדוגמה בפנים השדה', 'responder' ),
				'default'     => $section_field['placeholder'],
				'description' => esc_html__( 'כדי שהטקסט בתוך השדה יוצג בטופס עברו ללשונית העיצוב ובחרו זאת בהגדרת אופן תצוגת השדה', 'responder' ),
				'condition'   => array(
					'is_show_' . $section_field['id'] => 'yes',
					$section_field['id'] . '_field_type' . '!' => array( 'choice', 'multichoice', 'bool' ),
				),
			)
		);

		if ( ! $this->is_form_horizontal ) {
			$this->add_control(
				$section_field['id'] . '_field_width',
				array(
					'label'     => esc_html__( 'רוחב עמודה', 'responder' ),
					'type'      => ElementorControlsMananger::SELECT,
					'options'   => array(
						'100'   => '100%',
						'80'    => '80%',
						'75'    => '75%',
						'66.66' => '66%',
						'60'    => '60%',
						'50'    => '50%',
						'40'    => '40%',
						'33.33' => '33%',
						'25'    => '25%',
						'20'    => '20%',
					),
					'default'   => '100',
					'selectors' => array(
						"{{WRAPPER}} .res-form-field-input.{$section_field['class']}" => 'width: {{SIZE}}%',
					),
					'condition' => array(
						'is_show_' . $section_field['id'] => 'yes',
					),
				)
			);
		}

		$this->add_control(
			$section_field['id'] . '_order',
			array(
				'label'     => esc_html__( 'מיקום השדה', 'responder' ),
				'type'      => ElementorControlsMananger::SELECT,
				'default'   => $section_field['order']['current'],
				'options'   => $section_field['order']['options'],
				'condition' => array(
					'is_show_' . $section_field['id'] => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}
}
