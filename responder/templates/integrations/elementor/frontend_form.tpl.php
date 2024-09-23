<?php

use RavMesser\Plugin\Helpers as PluginHelpers;

$settings        = $this->get_settings_for_display();
$hover_aimation  = $settings['button_hover_animation'] ? 'elementor-animation-' . $settings['button_hover_animation'] : '';
$form_main_class = 'responder-form-vert';
$form_data_attrs = array(
	'action_after_submit'   => PluginHelpers::getVal( $settings, 'action_after_submit', '' ),
	'form_id'               => $this->form_settings['generated_id'],
	'urlthankyou'           => '',
	'urlthankyou-opennew'   => '',
	'urlthankyou-addparams' => '',
	'form_errors'           => wp_json_encode(
		array(
			'server_error'   => esc_html__( 'התקבלה שגיאה בזמן השליחה של הטופס, נסו שוב עוד כמה דקות', 'responder' ),
			'invalid_email'  => esc_html__( 'הזינו כתובת מייל תקינה', 'responder' ),
			'invalid_number' => esc_html__( 'הזינו מספר תקין', 'responder' ),
			'invalid_phone'  => esc_html__( 'יש להזין מספר טלפון ישראלי תקין', 'responder' ),
			'invalid_date'   => esc_html__( 'הזינו תאריך תקין', 'responder' ),
			'required'       => esc_html__( 'לשליחת הטופס בהצלחה מלאו את השדה', 'responder' ),
		)
	),
);
$form_fields     = array();

if ( $form_data_attrs['action_after_submit'] === 'redirect_to_thankyou_page' ) {
	$url_thank_you_page        = trim( PluginHelpers::getVal( $settings, 'url_thankyou_page_str', '' ) );
	$url_thank_you_open_in_new = PluginHelpers::getVal( $settings, 'url_thankyou_open_new_page', '' );
	$is_add_params             = PluginHelpers::getVal( $settings, 'url_thankyou_add_params', '' );

	if ( ! empty( $url_thank_you_page ) ) {
		if ( stripos( $url_thank_you_page, 'http://' ) === 0 && stripos( $url_thank_you_page, 'https://' ) === 0 ) {
			$url_thank_you_page = 'http://' . $url_thank_you_page;
		}

		$form_data_attrs['urlthankyou']           = $url_thank_you_page;
		$form_data_attrs['urlthankyou-opennew']   = $url_thank_you_open_in_new;
		$form_data_attrs['urlthankyou-addparams'] = $is_add_params;
	}
}

if ( $this->is_form_horizontal ) {
	$form_main_class = 'responder-form-hor';
}

if ( $settings['direction'] === 'rtl' ) {
	$form_main_class .= ' responder-rtl';
}

switch ( $settings['label_type'] ) {
	case 'label_and_placeholder':
		$is_show_label       = true;
		$is_show_placeholder = true;
		break;
	case 'placeholder_only':
		$is_show_label       = false;
		$is_show_placeholder = true;
		break;
	case 'label_only':
	default:
		$is_show_label       = true;
		$is_show_placeholder = false;
		break;
}

foreach ( $this->personal_fields as $personal_field ) {
	$form_field_id = "field_{$personal_field['id']}";

	$form_field['id']                    = "responder_form_{$form_data_attrs['form_id']}_{$form_field_id}";
	$form_field['name']                  = $form_field_id;
	$form_field['is_hidden']             = PluginHelpers::getVal( $settings, "is_hidden_field_{$form_field_id}", 'no' ) === 'yes';
	$form_field['is_required']           = PluginHelpers::getVal( $settings, "{$form_field_id}_required", 'no' ) === 'yes';
	$form_field['is_show']               = PluginHelpers::getVal( $settings, "is_show_{$form_field_id}", 'no' ) === 'yes';
	$form_field['field_class']           = '';
	$form_field['label']                 = PluginHelpers::getVal( $settings, "{$form_field_id}_field_label" );
	$form_field['order']                 = PluginHelpers::getVal( $settings, "{$form_field_id}_order" );
	$form_field['placeholder']           = '';
	$form_field['type_html']             = 'text';
	$form_field['type']                  = PluginHelpers::getVal( $settings, "{$form_field_id}_field_type" );
	$form_field['uri_param']             = $personal_field['uri_param'];
	$form_field['enable_value_from_uri'] = PluginHelpers::getVal( $settings, "enable_value_from_url_{$form_field_id}" ) === 'yes';

	// Weird Elementor bugfix, getting the default value and the selected one.
	if ( is_array( ( $form_field['type'] ) ) ) {
		$form_field['type'] = array_pop( $form_field['type'] );
	}

	switch ( $personal_field['type'] ) {
		case 'email':
			$form_field['type_html'] = 'email';
			break;
		case 'date':
			$form_field['type_html'] = 'date';
			break;
		case 'number':
			$form_field['type_html'] = 'number';
			break;
		case 'bool':
			$form_field['type_html'] = 'checkbox';
			break;
		case 'phone':
			$form_field['type_html'] = 'tel';
			break;
		case 'choice':
		case 'multichoice':
			$form_field['options'] = $personal_field['options'];
			break;
	}

	if ( $is_show_placeholder ) {
		$form_field['placeholder'] = PluginHelpers::getVal( $settings, "{$form_field_id}_field_placeholder" );
	}

	if ( $form_field['is_hidden'] ) {
		$form_field['hidden_field_value'] = PluginHelpers::getVal( $settings, "hidden_field_value_{$form_field_id}", '' );

		if ( $form_field['enable_value_from_uri'] ) {
			wp_parse_str( $_SERVER['QUERY_STRING'], $query_string );
			$form_field['hidden_field_value'] = PluginHelpers::getVal( $query_string, $form_field['uri_param'], '' );
		}
	}

	if ( $form_field['is_required'] ) {
		$form_field['field_class'] .= ' elementor-mark-required';
	}

	array_push( $form_fields, $form_field );
}

if ( ! function_exists( 'uSortByOrder' ) ) {
	function uSortByOrder( $a, $b ) {
		if ( $a['order'] === $b['order'] ) {
			return 0;
		}

		return $a['order'] < $b['order'] ? -1 : 1;
	}
}
usort( $form_fields, 'uSortByOrder' );

?>

<div class="responder-form-main-wrapper <?php echo esc_attr( $form_main_class ); ?>">
  <form class="responder-form-wrapper" method="post"
	<?php foreach ( $form_data_attrs as $data_attr => $data_value ) : ?>
		data-<?php echo esc_html( $data_attr ); ?>="<?php echo wp_http_validate_url( $data_value ) ? esc_url( $data_value ) : esc_attr( $data_value ); ?>"
	<?php endforeach ?>
  >

	<div class="fields-wrapper">
	  <?php foreach ( $form_fields as $field ) : ?>

			<?php if ( $field['is_hidden'] ) : ?>
		  <input type="hidden"
			data-uri_param="<?php echo esc_attr( $field['uri_param'] ); ?>"
			name="<?php echo esc_attr( $field['name'] ); ?>"
			value="<?php echo esc_attr( $field['hidden_field_value'] ); ?>"
		  />
		<?php endif ?>

			<?php if ( $field['is_show'] ) : ?>
		  <div class="<?php echo esc_attr( 'res-form-field res-form-field-input ' . $field['field_class'] ); ?>" data-field-type="<?php echo esc_attr( $field['type'] ); ?>">

				<?php if ( $is_show_label && ! empty( $field['label'] ) && $field['type'] !== 'bool' ) : ?>
			  <label class="elementor-field-label" for="<?php echo esc_attr( $field['id'] ); ?>">
					<?php echo esc_html( $field['label'] ); ?>
			  </label>
			<?php endif ?>

				<?php if ( $field['type'] === 'textarea' ) : ?>
			  <textarea
				data-uri_param="<?php echo esc_attr( $field['uri_param'] ); ?>"
				id="<?php echo esc_attr( $field['id'] ); ?>"
				name="<?php echo esc_attr( $field['name'] ); ?>"
				placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
			  ></textarea>

			<?php elseif ( $field['type'] === 'choice' ) : ?>
			  <select
				data-uri_param="<?php echo esc_attr( $field['uri_param'] ); ?>"
				id="<?php echo esc_attr( $field['id'] ); ?>"
				name="<?php echo esc_attr( $field['name'] ); ?>"
			  >

				<?php foreach ( $field['options'] as $option ) : ?>
				  <option value="<?php echo esc_attr( $option['id'] ); ?>">
					<?php echo esc_html( $option['name'] ); ?>
				  </option>
				<?php endforeach ?>

			  </select>

			<?php elseif ( $field['type'] === 'multichoice' ) : ?>
				<?php foreach ( $field['options'] as $option ) : ?>
				<div class="checkboxes">
				  <input type="checkbox"
					data-uri_param="<?php echo esc_attr( $field['uri_param'] . '[]' ); ?>"
					id="<?php echo esc_attr( $field['id'] . '_' . $option['id'] ); ?>"
					name="<?php echo esc_attr( $field['name'] ); ?>"
					value="<?php echo esc_attr( $option['id'] ); ?>"
				  />
				  <label for="<?php echo esc_attr( $field['id'] . '_' . $option['id'] ); ?>">
					<?php echo esc_html( $option['name'] ); ?>
				  </label>
				</div>
			  <?php endforeach ?>

			<?php elseif ( $field['type'] === 'bool' ) : ?>
			  <input type="<?php echo esc_attr( $field['type_html'] ); ?>"
				data-uri_param="<?php echo esc_attr( $field['uri_param'] ); ?>"
				id="<?php echo esc_attr( $field['id'] ); ?>"
				name="<?php echo esc_attr( $field['name'] ); ?>"
			  />
			  <label for="<?php echo esc_attr( $field['id'] ); ?>" class="elementor-field-label">
				<?php echo esc_html( $field['label'] ); ?>
			  </label>

			<?php else : ?>
			  <input type="<?php echo esc_attr( $field['type_html'] ); ?>"
				data-uri_param="<?php echo esc_attr( $field['uri_param'] ); ?>"
				id="<?php echo esc_attr( $field['id'] ); ?>"
				name="<?php echo esc_attr( $field['name'] ); ?>"
				placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
			  />
			<?php endif ?>

		  </div>
		<?php endif ?>
	  <?php endforeach ?>

	  <div class="res-form-field res-form-field-submit">
		<input type="submit"
		  class="<?php echo esc_attr( 'res-button-submit ' . $hover_aimation ); ?>"
		  data-original_text="<?php echo esc_attr( $settings['submit_label'] ); ?>"
		  data-submitting_text="<?php echo esc_attr( $settings['submitting_text'] ); ?>"
		  value="<?php echo esc_attr( $settings['submit_label'] ); ?>">
	  </div>

	</div>

	<div class="responder-message-form-error"></div>
	<div class="responder-message-sent">
	  <?php echo esc_html( PluginHelpers::getVal( $settings, 'submitted_text', '' ) ); ?>
	</div>

  </form>
</div>
