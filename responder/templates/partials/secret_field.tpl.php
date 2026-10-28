<?php
/**
 * One credential row (Token / key / secret): masked by default, with show/hide and copy buttons.
 * Expects $rmp_field = array( 'key' => option name, 'value' => saved value, 'label' => label ).
 */
?>
<tr valign="top">
  <th scope="row">
	<p>
	  <label for="<?php echo esc_attr( $rmp_field['key'] ); ?>">
		<?php echo esc_html( $rmp_field['label'] ); ?>
	  </label>
	</p>
  </th>
  <td>
	<p class="rmp-secret-field">
	  <input type="password" size="50" dir="ltr" class="rmp-secret-input"
		autocomplete="new-password" spellcheck="false" autocapitalize="off"
		id="<?php echo esc_attr( $rmp_field['key'] ); ?>"
		name="<?php echo esc_attr( 'responder[' . $rmp_field['key'] . ']' ); ?>"
		value="<?php echo esc_attr( $rmp_field['value'] ); ?>"
	  />
	  <button type="button" class="button rmp-secret-toggle" data-target="<?php echo esc_attr( $rmp_field['key'] ); ?>"
		title="<?php esc_attr_e( 'הצגה / הסתרה', 'responder' ); ?>" aria-label="<?php esc_attr_e( 'הצגה / הסתרה', 'responder' ); ?>" aria-pressed="false">
		<i class="fa fa-eye" aria-hidden="true"></i>
	  </button>
	  <button type="button" class="button rmp-secret-copy" data-target="<?php echo esc_attr( $rmp_field['key'] ); ?>"
		title="<?php esc_attr_e( 'העתקה', 'responder' ); ?>" aria-label="<?php esc_attr_e( 'העתקה', 'responder' ); ?>">
		<i class="fa fa-clone" aria-hidden="true"></i>
	  </button>
	  <span class="rmp-secret-copied" aria-live="polite"></span>
	</p>
  </td>
</tr>
