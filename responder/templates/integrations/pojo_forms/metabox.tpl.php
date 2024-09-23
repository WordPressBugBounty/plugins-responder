<?php

use RavMesser\Plugin\API as PluginAPI;
use RavMesser\Plugin\Helpers as PluginHelpers;

$list_id    = get_post_meta( $post->ID, 'form_id', true );
$fields_map = get_post_meta( $post->ID, 'responder_pojo_responder_values', true );
$lists      = PluginAPI::run( 'responder' )->getLists();

if ( empty( $list_id ) ) {
	$list_id = $lists[0]['id'];
}

$personal_fields = PluginAPI::run( 'responder' )->getPersonalFieldsByListId( (int) $list_id );
$on_existing     = PluginHelpers::getVal( $fields_map, 'responder_onexisting', 'update' );
?>

<div class="responder-settings-wrapper">
  <p>
	<strong>
	  <?php esc_html_e( 'התאמת שדות הטופס לשדות הקיימים ברשימה ברב מסר', 'responder' ); ?>
	</strong>
  </p>

  <p>
	<label for="subscribers-lists" class="label">
	  <?php esc_html_e( 'הרשימה ברב מסר אליה יוכנסו פרטי הנמענים', 'responder' ); ?>
	</label>

	<select class="subscribers-lists" name="responder_form_id">
	  <?php foreach ( $lists as $list ) : ?>
		<option value="<?php echo esc_attr( $list['id'] ); ?>" <?php echo esc_attr( $list['id'] === $list_id ? 'selected' : '' ); ?>>
			<?php echo esc_html( $list['name'] ); ?>
		</option>
	  <?php endforeach ?>
	</select>
  </p>

  <div class="fields-map">
	<?php foreach ( $personal_fields as $field ) : ?>
	  <p>
		<label for="<?php echo esc_attr( 'responder_field_' . $field['id'] ); ?>">
		  <?php esc_html_e( 'שם השדה ברב מסר:', 'responder' ); ?> <strong><?php echo esc_html( $field['name'] ); ?></strong>
		</label>

		<input
		  id="<?php echo esc_attr( 'responder_field_' . $field['id'] ); ?>"
		  class="responder-connect-field"
		  placeholder="<?php esc_html_e( 'שם השדה בטופס', 'responder' ); ?>"
		  name="<?php echo esc_attr( 'responder_field_' . $field['id'] ); ?>"
		  value="<?php echo esc_attr( PluginHelpers::getVal( $fields_map, "responder_field_{$field['id']}" ) ); ?>"
		/>
	  </p>
	<?php endforeach ?>
  </div>

  <div class="field-map hidden">
	<p>
	  <label for="responder_field_{{ fieldId }}">
		<?php esc_html_e( 'שם השדה ברב מסר:', 'responder' ); ?> <strong>{{ fieldName }}</strong>
	  </label>

	  <input
		id="responder_field_{{ fieldId }}"
		class="responder-connect-field"
		placeholder="<?php esc_html_e( 'שם השדה בטופס', 'responder' ); ?>"
		name="responder_field_{{ fieldId }}"
	  />
	</p>
  </div>

  <hr>

  <p>
	<label for="responder_onexisting">
	  <?php esc_html_e( 'אם הנמען קיים ברשימה', 'responder' ); ?>
	</label>

	<select name="responder_onexisting" id="responder_onexisting">
	  <option value="update" <?php echo esc_attr( $on_existing === 'update' ? 'selected' : '' ); ?>>
		<?php esc_html_e( 'שמור ותק ועדכן פרטים', 'responder' ); ?>
	  </option>
	  <option value="resubscribe" <?php echo esc_attr( $on_existing === 'resubscribe' ? 'selected' : '' ); ?>>
		<?php esc_html_e( 'איפוס ותק והרשמה מחדש', 'responder' ); ?>
	  </option>
	</select>
  </p>

</div>
