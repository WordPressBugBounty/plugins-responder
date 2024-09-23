<?php // phpcs:disable ?>
<div class="elementor-control-field">
  <# if (data.connectedSystemsCount === 1) { #>

    <input type="hidden" data-setting="{{ data.name }}" value="{{ data.controlValue }}">

  <# } else if (data.connectedSystemsCount >= 2) { #>

    <label for="<?php echo esc_attr($control_uid) ?>" class="elementor-control-title">{{{ data.label }}}</label>

    <div class="elementor-control-input-wrapper elementor-control-unit-5">
      <select id="<?php echo esc_attr($control_uid) ?>" data-setting="{{ data.name }}">
        <# _.each(data.options, function(optionTitle, optionValue) { #>
          <# var selected = data.controlValue === optionValue ? 'selected' : ''; #>

          <option {{ selected }} value="{{ optionValue }}">
            {{{ optionTitle }}}
          </option>
        <# }); #>
      </select>
    </div>

	<div class="elementor-control-field-description">
		<# if (data.tutorial_link) { #>
			<a href="{{{ data.tutorial_link }}}" class="elementor-control-field-tutorial-link" target="_blank">
				{{{ data.tutorial_link_text }}}
			</a>
		<# } #>
	</div>

  <# } #>
</div>
<?php // phpcs:enable ?>
