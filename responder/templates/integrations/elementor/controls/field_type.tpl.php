<div class="elementor-control-field">

	<# if ( data.label ) { #>
		<label for="<?php echo esc_attr( $control_uid ); ?>" class="elementor-control-title">{{{ data.label }}}</label>
	<# } #>

	<div class="elementor-control-input-wrapper elementor-control-unit-5">
	<#
	var attrDisabled = 'disabled',
	options = data.options;

	if (data.chosen_system === 'responder' && _.contains(['text', 'textarea'], data.controlValue)) {
		attrDisabled = '';
		options      = data.text_options;
	}
	#>

	<select id="<?php echo esc_attr( $control_uid ); ?>" data-setting="{{ data.name }}" {{ attrDisabled }}>
		<# _.each(options, function(optionTitle, optionValue) { #>
		<# var selected = data.controlValue === optionValue ? 'selected' : ''; #>

		<option {{ selected }} value="{{ optionValue }}">
			{{{ optionTitle }}}
		</option>
		<# }); #>
	</select>
	</div>

</div>
