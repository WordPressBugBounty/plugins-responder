<div class="elementor-control-field">

  <# if ( data.label ) { #>
	<label for="<?php echo esc_attr( $control_uid ); ?>" class="elementor-control-title">{{{ data.label }}}</label>
  <# } #>

  <div id="rmp-elementor-pro-tags-wrapper" class="elementor-control-input-wrapper elementor-control-unit-5">
	<input type="tagify" id="<?php echo esc_attr( $control_uid ); ?>" data-settings="{{ data.name }}" />
	<div id="rmp-elementor-pro-tags-dropdown-placement"></div>
  </div>

</div>
