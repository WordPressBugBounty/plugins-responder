<div class="elementor-control-field">

  <# if ( data.label ) { #>
	<label for="<?php echo esc_attr( $control_uid ); ?>" class="elementor-control-title">{{{ data.label }}}</label>
  <# } #>

  <div class="elementor-control-input-wrapper elementor-control-unit-5">
	<#
	  var inputType = 'text';

	  if ( data.input_type === 'date' ) {
		inputType = 'date';
	  }
	#>

	<input
	  id="<?php echo esc_attr( $control_uid ); ?>"
	  type="{{ inputType }}"
	  data-setting="{{ data.name }}" />
  </div>

</div>
