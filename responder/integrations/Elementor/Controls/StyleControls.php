<?php namespace RavMesser\Integrations\Elementor\Controls;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Controls_Manager as ElementorControlsMananger;
use Elementor\Group_Control_Typography  as ElementorGroupControlTypography;
use Elementor\Core\Schemes\Typography as ElementorTypographySchemes;

trait StyleControls {

	public function addStyleControls() {
		$this->styleSectionForm();
		$this->styleSectionFields();
		$this->styleSectionButton();
		$this->styleSectionThankYouMessage();
	}

	public function styleSectionForm() {
		$this->start_controls_section(
			'style_section_form',
			array(
				'label' => esc_html__( 'טופס', 'responder' ),
				'tab'   => ElementorControlsMananger::TAB_STYLE,
			)
		);

		$this->add_control(
			'style_heading',
			array(
				'label' => $this->is_form_horizontal
				  ? esc_html__( 'זהו טופס שוכב', 'responder' )
				  : esc_html__( 'טופס עומד', 'responder' ),
				'type'  => ElementorControlsMananger::HEADING,
			)
		);

		$this->add_control(
			'direction',
			array(
				'label'   => esc_html__( 'כיוון הטופס', 'responder' ),
				'type'    => ElementorControlsMananger::SELECT,
				'default' => is_rtl() ? 'rtl' : 'ltr',
				'options' => array(
					'rtl' => esc_html__( 'ימין לשמאל - כמו בעברית', 'responder' ),
					'ltr' => esc_html__( 'שמאל לימין', 'responder' ),
				),
			)
		);

		$this->add_control(
			'label_type',
			array(
				'label'   => esc_html__( 'אופן תצוגת השדה', 'responder' ),
				'type'    => ElementorControlsMananger::SELECT,
				'default' => 'label_only',
				'options' => array(
					'label_only'            => esc_html__( 'שם השדה בלבד', 'responder' ),
					'placeholder_only'      => esc_html__( 'טקסט פנימי בלבד', 'responder' ),
					'label_and_placeholder' => esc_html__( 'הצגת שם השדה וטקסט פנימי', 'responder' ),
				),
			)
		);

		if ( $this->is_form_horizontal === false ) {
			$this->add_control(
				'row_gap',
				array(
					'label'     => esc_html__( 'רווח שורות', 'responder' ),
					'type'      => ElementorControlsMananger::SLIDER,
					'default'   => array(
						'size' => 10,
					),
					'range'     => array(
						'px' => array(
							'min' => 0,
							'max' => 60,
						),
					),
					'selectors' => array(
						'{{WRAPPER}} .res-form-field-input' => 'margin-bottom: {{SIZE}}{{UNIT}};',
					),
				)
			);
		} else {
			$this->add_control(
				'column_gap',
				array(
					'label'     => esc_html__( 'ריווח עמודות', 'responder' ),
					'type'      => ElementorControlsMananger::SLIDER,
					'default'   => array(
						'size' => 10,
					),
					'range'     => array(
						'px' => array(
							'min' => 0,
							'max' => 60,
						),
					),
					'selectors' => array(
						'{{WRAPPER}} .res-form-field' => 'padding-right: calc( {{SIZE}}{{UNIT}} / 2 ); padding-left: calc( {{SIZE}}{{UNIT}} / 2 );',
						'{{WRAPPER}} .fields-wrapper' => 'margin-left: calc( -{{SIZE}}{{UNIT}} / 2 ); margin-right: calc( -{{SIZE}}{{UNIT}} / 2 );',
					),
				)
			);
		}

		$this->add_control(
			'text_gap',
			array(
				'label'     => esc_html__( 'רווח בין השדה לטקסט', 'responder' ),
				'type'      => ElementorControlsMananger::SLIDER,
				'default'   => array(
					'size' => 0,
				),
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 20,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .res-form-field label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		if ( $this->is_form_horizontal === false ) {
			$this->add_control(
				'button_gap',
				array(
					'label'     => esc_html__( 'ריווח בין הכפתור לשדות', 'responder' ),
					'type'      => ElementorControlsMananger::SLIDER,
					'default'   => array(
						'size' => 5,
					),
					'range'     => array(
						'px' => array(
							'min' => 0,
							'max' => 50,
						),
					),
					'selectors' => array(
						'{{WRAPPER}} .res-form-field-submit' => 'margin-top: {{SIZE}}{{UNIT}};',
					),
				)
			);
		}

		$this->end_controls_section();
	}

	private function styleSectionButton() {
		$this->start_controls_section(
			'section_button_style',
			array(
				'label' => esc_html__( 'כפתור שליחה', 'responder' ),
				'tab'   => ElementorControlsMananger::TAB_STYLE,
			)
		);
		$this->start_controls_tabs( 'tabs_button_style' );

		$this->styleSectionButtonRegularTab();
		$this->styleSectionButtonHoverTab();

		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	private function styleSectionButtonHoverTab() {
		$this->start_controls_tab(
			'tab_button_hover',
			array(
				'label' => esc_html__( 'הצבעה עם העכבר', 'responder' ),
			)
		);

		$this->add_control(
			'button_background_hover_color',
			array(
				'label'     => esc_html__( 'צבע רקע', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'default'   => '#3ABC4C',
				'selectors' => array(
					'{{WRAPPER}} .res-button-submit:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_color',
			array(
				'label'     => esc_html__( 'צבע טקסט', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .res-button-submit:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_border_color',
			array(
				'label'     => esc_html__( 'צבע הגבול', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .res-button-submit:hover' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'button_border_border!' => '',
				),
			)
		);

		$this->add_control(
			'button_hover_animation',
			array(
				'label' => esc_html__( 'אנימציה', 'responder' ),
				'type'  => ElementorControlsMananger::HOVER_ANIMATION,
			)
		);

		$this->end_controls_tab();
	}

	private function styleSectionButtonRegularTab() {
		$this->start_controls_tab(
			'tab_button_normal',
			array(
				'label' => esc_html__( 'רגיל', 'responder' ),
			)
		);

		$this->add_control(
			'button_background_color',
			array(
				'label'     => esc_html__( 'צבע רקע', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'default'   => '#61ce70',
				'selectors' => array(
					'{{WRAPPER}} .res-button-submit' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => esc_html__( 'צבע טקסט', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .res-button-submit' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			ElementorGroupControlTypography::get_type(),
			array(
				'name'     => 'button_typography',
				'scheme'   => ElementorTypographySchemes::TYPOGRAPHY_4,
				'selector' => '{{WRAPPER}} .res-button-submit',
			)
		);

		$this->add_control(
			'button_border_border',
			array(
				'label'     => esc_html__( 'סוג הגבול', 'responder' ),
				'type'      => ElementorControlsMananger::SELECT,
				'options'   => array(
					''       => esc_html__( 'בלי', 'responder' ),
					'solid'  => esc_html__( 'אחיד', 'responder' ),
					'double' => esc_html__( 'כפול', 'responder' ),
					'dotted' => esc_html__( 'מנוקד', 'responder' ),
					'dashed' => esc_html__( 'מקווקו', 'responder' ),
					'groove' => esc_html__( 'גרוב', 'responder' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .res-button-submit' => 'border-style: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_border_border_width',
			array(
				'label'     => esc_html__( 'עובי הגבול', 'responder' ),
				'type'      => ElementorControlsMananger::DIMENSIONS,
				'selectors' => array(
					'{{WRAPPER}} .res-button-submit' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'default'   => array(
					'top'      => 1,
					'bottom'   => 1,
					'left'     => 1,
					'right'    => 1,
					'isLinked' => 1,
					'unit'     => 'px',
				),
				'condition' => array(
					'button_border_border!' => '',
				),
			)
		);

		$this->add_control(
			'button_border_border_color',
			array(

				'label'     => esc_html__( 'צבע הגבול', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'default'   => '#34A844',
				'selectors' => array(
					'{{WRAPPER}} .res-button-submit' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'button_border_border!' => '',
				),
			)
		);

		$this->add_control(
			'button_border_radius',
			array(
				'label'      => esc_html__( 'רדיוס הגבול', 'responder' ),
				'type'       => ElementorControlsMananger::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .res-button-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_text_padding',
			array(
				'label'      => esc_html__( 'ריווח טקסט (padding)', 'responder' ),
				'type'       => ElementorControlsMananger::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .res-button-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_width',
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
				'default'   => $this->is_form_horizontal ? '100' : '25',
				'selectors' => array(
					'{{WRAPPER}} .res-form-field-submit .res-button-submit' => 'width: {{SIZE}}%',
				),
			)
		);

		$this->add_control(
			'button_alignment',
			array(
				'label'       => esc_html__( 'יישור', 'responder' ),
				'type'        => ElementorControlsMananger::CHOOSE,
				'label_block' => false,
				'options'     => array(
					'left'   => array(
						'title' => esc_html__( 'שמאל', 'responder' ),
						'icon'  => 'fa fa-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'מרכז', 'responder' ),
						'icon'  => 'fa fa-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'ימין', 'responder' ),
						'icon'  => 'fa fa-align-right',
					),
				),
				'default'     => 'center',
				'selectors'   => array(
					'{{WRAPPER}} .res-form-field-submit' => 'text-align: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();
	}

	private function styleSectionFields() {
		$this->start_controls_section(
			'style_section_fields',
			array(
				'label' => esc_html__( 'השדות', 'responder' ),
				'tab'   => ElementorControlsMananger::TAB_STYLE,
			)
		);

		$this->add_control(
			'field_text_color',
			array(
				'label'     => esc_html__( 'צבע טקסט', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .res-form-field label' => 'color: {{VALUE}};',
				),
				'default'   => '#383838',
			)
		);

		$this->add_control(
			'field_text_inside_color',
			array(
				'label'     => esc_html__( 'צבע טקסט בפנים השדה', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .res-form-field-input input, {{WRAPPER}} .res-form-field-input select, {{WRAPPER}} .res-form-field-input textarea' => 'color: {{VALUE}};',
				),
				'default'   => '#383838',
			)
		);

		$this->add_control(
			'field_placeholder_color',
			array(
				'label'     => esc_html__( 'צבע ממלא מקום', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .res-form-field input::-webkit-input-placeholder, {{WRAPPER}} .res-form-field input::-moz-placeholder, {{WRAPPER}} .res-form-field input:-ms-input-placeholder, {{WRAPPER}} .res-form-field input::-ms-input-placeholder, {{WRAPPER}} .res-form-field input::placeholder, {{WRAPPER}} .res-form-field textarea::-webkit-input-placeholder, {{WRAPPER}} .res-form-field textarea::-moz-placeholder, {{WRAPPER}} .res-form-field textarea:-ms-input-placeholder, {{WRAPPER}} .res-form-field textarea::-ms-input-placeholder, {{WRAPPER}} .res-form-field textarea::placeholder' => 'color: {{VALUE}};',
				),
				'default'   => '#E7E7E7',
			)
		);

		$this->add_control(
			'field_padding_vertical',
			array(
				'label'     => esc_html__( 'ריווח שדה מלמעלה ולמטה', 'responder' ),
				'type'      => ElementorControlsMananger::SLIDER,
				'default'   => array(
					'size' => 5,
				),
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 15,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .res-form-field input:not([type="checkbox"]), {{WRAPPER}} .res-form-field select, {{WRAPPER}} .res-form-field textarea' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'field_padding_horizontal',
			array(
				'label'     => esc_html__( 'ריווח שדה מהצדדים', 'responder' ),
				'type'      => ElementorControlsMananger::SLIDER,
				'default'   => array(
					'size' => 5,
				),
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 15,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .res-form-field input:not([type="checkbox"]), {{WRAPPER}} .res-form-field select, {{WRAPPER}} .res-form-field textarea' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .res-form-field input[type="checkbox"]' => 'line-height: 0;',
				),
			)
		);

		$this->add_group_control(
			ElementorGroupControlTypography::get_type(),
			array(
				'name'     => 'field_typography',
				'selector' => '{{WRAPPER}} .res-form-field label, {{WRAPPER}} .res-form-field input, {{WRAPPER}} .res-form-field select, {{WRAPPER}} .res-form-field textarea',
				'scheme'   => ElementorTypographySchemes::TYPOGRAPHY_3,
			)
		);

		$this->add_control(
			'field_background_color',
			array(
				'label'     => esc_html__( 'צבע רקע', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .res-form-field input:not([type="submit"])' => 'background-color: {{VALUE}};',
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'field_border_color',
			array(
				'label'     => esc_html__( 'צבע הגבול', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .res-form-field-input input, {{WRAPPER}} .res-form-field-input select, {{WRAPPER}} .res-form-field-input textarea' => 'border-color: {{VALUE}};',
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'field_border_width',
			array(
				'label'       => esc_html__( 'עובי הגבול', 'responder' ),
				'type'        => ElementorControlsMananger::DIMENSIONS,
				'placeholder' => '1',
				'size_units'  => array( 'px' ),
				'selectors'   => array(
					'{{WRAPPER}} .res-form-field-input input, {{WRAPPER}} .res-form-field-input select, {{WRAPPER}} .res-form-field-input textarea' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'field_border_radius',
			array(
				'label'      => esc_html__( 'רדיוס הגבול', 'responder' ),
				'type'       => ElementorControlsMananger::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .res-form-field-input input, {{WRAPPER}} .res-form-field-input select, {{WRAPPER}} .res-form-field-input textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function styleSectionThankYouMessage() {
		$this->start_controls_section(
			'section_thankyou_style',
			array(
				'label' => esc_html__( 'הודעת תודה', 'responder' ),
				'tab'   => ElementorControlsMananger::TAB_STYLE,
			)
		);

		$this->add_control(
			'thankyou_message_text_color',
			array(
				'label'     => esc_html__( 'צבע טקסט', 'responder' ),
				'type'      => ElementorControlsMananger::COLOR,
				'default'   => '#006400',
				'selectors' => array(
					'{{WRAPPER}} .responder-message-sent' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'thankyou_message_align',
			array(
				'label'     => esc_html__( 'מיקום טקסט', 'responder' ),
				'type'      => ElementorControlsMananger::SELECT,
				'options'   => array(
					'center' => esc_html__( 'מרכז', 'responder' ),
					'left'   => esc_html__( 'שמאל', 'responder' ),
					'right'  => esc_html__( 'ימין', 'responder' ),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .responder-message-sent' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			ElementorGroupControlTypography::get_type(),
			array(
				'name'     => 'thankyou_typography',
				'scheme'   => ElementorTypographySchemes::TYPOGRAPHY_2,
				'selector' => '{{WRAPPER}} .responder-message-sent',
			)
		);

		$this->end_controls_section();
	}
}
