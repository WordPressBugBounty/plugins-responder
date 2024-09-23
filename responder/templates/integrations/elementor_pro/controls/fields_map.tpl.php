<?php $direction = is_rtl() ? 'right' : 'left'; ?>

<div class="elementor-control-field subscribers-list-box">
  <input type="hidden" data-setting="{{ data.name }}" value="{{ data.controlValue }}">

  <label for="<?php echo esc_attr( $control_uid ); ?>" class="elementor-control-title">
	{{{ data.label }}}
  </label>

  <div class="elementor-control-input-wrapper elementor-control-unit-5">
	<select id="<?php echo esc_attr( $control_uid ); ?>" class="elementor-select2" type="select2">
	  <option disabled selected value="">
		<?php esc_html_e( 'בחירת רשימה', 'responder' ); ?>
	  </option>
	</select>
  </div>

</div>
<?php
// phpcs:disable
?>
<div class="elementor-control-content fields-map-box" style="display:none">
  <label style="display: flex; padding: 15px 0">
    <span class="elementor-control-title">
      <?php esc_html_e('התאמת שדות', 'responder') ?>
    </span>
  </label>

  <div class="elementor-label-inline">
    <div class="elementor-control-content">
    </div>
  </div>

  <# if (data.description) { #>
    <div class="elementor-control-field-description">
		{{{ data.description }}}
	</div>
  <# } #>
</div>

<script type="text/template" id="field-map-template">
  <div class="elementor-control-field" style="margin-bottom: <%= data.fieldOptions.length ? '5' : '10' %>px">
    <label for="rmp-<%= data.personalField.id %>" class="elementor-control-title">
      <%= data.personalField.name %>
    </label>

    <div class="elementor-control-input-wrapper elementor-control-unit-5">
      <select id="rmp-<%= data.personalField.id %>" <%= !data.formFields.length ? 'disabled' : '' %>>
        <option value="">---</option>
        <% _.each(data.formFields, function(formField, index) { %>
          <option
            <%= formField.selected ? 'selected' : '' %>
            <%= formField.disabled ? 'disabled' : '' %>
            value="rmp-<%= formField.id %>"
          >
            <%= formField.name %>
          </option>
        <% }); %>
      </select>
    </div>
  </div>

  <% if (data.fieldOptions.length) { %>
    <div class="elementor-label-inline">
      <div class="elementor-control-content" style="padding-<?php echo esc_attr($direction) ?>: 7px; border-<?php echo esc_attr($direction) ?>: 2px solid #34383c; margin-bottom: 10px;">
        <span class="elementor-control-title" style="margin-bottom: 10px; padding-top: 5px">
          <?php esc_html_e('התאמת אפשרויות הבחירה של', 'responder') ?> <span style="text-decoration: underline"><%= data.personalField.name %></span>
        </span>

        <% _.each(data.fieldOptions, function(fieldOption) { %>
          <div class="elementor-control-field" style="margin-bottom: 5px">
            <label for="rmp-<%= fieldOption.personalFieldOption.id %>" class="elementor-control-title">
              <%= fieldOption.personalFieldOption.name %>
            </label>

            <div class="elementor-control-input-wrapper elementor-control-unit-5">
              <select id="rmp-<%= fieldOption.personalFieldOption.id %>">
                <option value="">---</option>
                <% _.each(fieldOption.formFieldOptions, function(formFieldOption) { %>
                  <option
                    <%= formFieldOption.selected ? 'selected' : '' %>
                    <%= formFieldOption.disabled ? 'disabled' : '' %>
                    value="rmp-<%= formFieldOption.value %>"
                  >
                    <%= formFieldOption.name %>
                  </option>
                <% }); %>
              </select>
            </div>
          </div>
        <% }); %>
      </div>
    </div>
  <% } %>
</script>

<?php
// phpcs:enable
?>
