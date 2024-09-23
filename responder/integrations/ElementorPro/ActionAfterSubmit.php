<?php namespace RavMesser\Integrations\ElementorPro;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use Elementor\Controls_Manager as ElementorControlsMananger;
use ElementorPro\Modules\Forms\Classes\Action_Base as ElementorProActionBase;
use RavMesser\Plugin\API as PluginAPI;
use RavMesser\Integrations\ElementorPro\FieldsHelper;
use RavMesser\Plugin\Helpers as PluginHelpers;

class ActionAfterSubmit extends ElementorProActionBase {

	public function get_label() {
		return esc_html__( 'רב מסר', 'responder' );
	}

	public function get_name() {
		return 'responder';
	}

	public function on_export( $element ) {
		unset(
			$element['responder_system_choice'],
			$element['responder_onexisting'],
			$element['responder_live_tags'],
			$element['responder_live_onexisting_rejoin'],
			$element['responder_live_onexisting_joindate'],
			$element['responder_fields_map']
		);
	}

	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_responder',
			array(
				'label'     => esc_html__( 'רב מסר', 'responder' ),
				'condition' => array(
					'submit_actions' => $this->get_name(),
				),
			)
		);

		$this->add_system_choice_control( $widget );

		$this->add_fields_map_control( $widget );

		$this->add_tags_control( $widget );

		$this->add_onexisting_control( $widget );

		$widget->end_controls_section();
	}

	public function run( $record, $ajax_handler ) {
		$form_settings = $record->get( 'form_settings' );
		$form_fields   = $record->get( 'fields' );
		$chosen_system = PluginHelpers::getVal( $form_settings, 'responder_system_choice' );
		$fields_map    = PluginHelpers::jsonDecode(
			PluginHelpers::getVal( $form_settings, 'responder_fields_map' )
		);

		if ( ! empty( $chosen_system ) && FieldsHelper::validateFieldsMap( $fields_map ) ) {
			$subscriber_details = array(
				'list_id' => $fields_map['list_id'],
				'fields'  => FieldsHelper::formatFormFieldsByFieldsMap(
					$chosen_system,
					$form_fields,
					$fields_map
				),
			);

			switch ( $chosen_system ) {
				case 'responder':
					$subscriber_details['onexisting'] = PluginHelpers::getVal( $form_settings, 'responder_onexisting' );
					break;
				case 'responder_live':
					$subscriber_details['tags']                = FieldsHelper::formatTags(
						PluginHelpers::getVal( $form_settings, 'responder_live_tags', array() )
					);
					$subscriber_details['onexisting_rejoin']   = PluginHelpers::getVal( $form_settings, 'responder_live_onexisting_rejoin' );
					$subscriber_details['onexisting_joindate'] = PluginHelpers::getVal( $form_settings, 'responder_live_onexisting_joindate' );
					break;
			}

			PluginAPI::run( $chosen_system )->createSubscriber( $subscriber_details );
		}
	}

	private function add_fields_map_control( $widget ) {
		$widget->add_control(
			'responder_fields_map',
			array(
				'type'        => 'responder_fields_map',
				'label'       => esc_html__( 'הרשימה ברב מסר אליה יוכנסו פרטי הנמענים', 'responder' ),
				'description' => esc_html__( '*חובה להעביר לרב מסר שדה מייל או טלפון כדי שהחיבור יעבוד', 'responder' ),
				'label_block' => true,
				'condition'   => array(
					'responder_system_choice!' => '',
				),
			)
		);
	}

	private function add_onexisting_control( $widget ) {
		$widget->add_control(
			'responder_onexisting',
			array(
				'label'       => esc_html__( 'אם הנמען קיים ברשימה', 'responder' ),
				'label_block' => true,
				'type'        => ElementorControlsMananger::SELECT,
				'separator'   => 'before',
				'default'     => 'update',
				'options'     => array(
					'update'      => esc_html__( 'שמור ותק ועדכן פרטים', 'responder' ),
					'resubscribe' => esc_html__( 'איפוס ותק והרשמה מחדש', 'responder' ),
				),
				'condition'   => array(
					'responder_system_choice' => 'responder',
				),
			)
		);

		$widget->add_control(
			'responder_live_onexisting_heading',
			array(
				'label'     => esc_html__( 'אם הנמען קיים', 'responder' ),
				'type'      => ElementorControlsMananger::HEADING,
				'separator' => 'before',
				'condition' => array(
					'responder_system_choice' => 'responder_live',
				),
			)
		);

		$widget->add_control(
			'responder_live_onexisting_rejoin',
			array(
				'label'        => esc_html__( 'להחליף את הפרטים הקיימים בפרטים חדשים', 'responder' ),
				'type'         => ElementorControlsMananger::SWITCHER,
				'label_on'     => esc_html__( 'כן', 'responder' ),
				'label_off'    => esc_html__( 'לא', 'responder' ),
				'return_value' => 'rejoin',
				'condition'    => array(
					'responder_system_choice' => 'responder_live',
				),
			)
		);

		$widget->add_control(
			'responder_live_onexisting_joindate',
			array(
				'label'        => esc_html__( 'לאפס את הותק ברשימה', 'responder' ),
				'type'         => ElementorControlsMananger::SWITCHER,
				'label_on'     => esc_html__( 'כן', 'responder' ),
				'label_off'    => esc_html__( 'לא', 'responder' ),
				'separator'    => 'after',
				'return_value' => 'joindate',
				'condition'    => array(
					'responder_system_choice' => 'responder_live',
				),
			)
		);
	}

	private function add_system_choice_control( $widget ) {
		$widget->add_control(
			'responder_system_choice',
			array(
				'type'        => 'responder_system_choice',
				'label'       => esc_html__( 'בחירת מערכת', 'responder' ),
				'label_block' => true,
				'separator'   => 'after',
				'tutorial_link'  => esc_url('https://www.youtube.com/watch?v=sqIMNKLB9Cs'),
				'tutorial_link_text'  => esc_html__('צפייה בסרטון הדרכה', 'responder'),
			)
		);
	}

	private function add_tags_control( $widget ) {
		$widget->add_control(
			'responder_live_tags',
			array(
				'type'        => 'responder_live_tags',
				'label'       => esc_html__( 'תגיות נמען', 'responder' ),
				'label_block' => true,
				'separator'   => 'before',
				'condition'   => array(
					'responder_system_choice' => 'responder_live',
				),
			)
		);
	}
}
