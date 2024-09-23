<?php // phpcs:disable ?>
<#
  var formMainClass     = 'responder-form-vert',
      formFields        = [],
      isFormHorizontal  = <?php echo $this->is_form_horizontal ? 'true' : 'false' ?>,
      formGeneratedId   = '<?php echo esc_attr($this->form_settings['generated_id']) ?>',
      personalFields    = <?php echo wp_json_encode($this->personal_fields) ?>,
      isShowLabel       = true,
      isShowPlaceholder = false,
      hoverAnimation    = settings.button_hover_animation ? 'elementor-animation-' + settings.button_hover_animation : '';

  if (isFormHorizontal) {
    formMainClass   = 'responder-form-hor';
  }

  if (settings.direction === 'rtl') {
    formMainClass += ' responder-rtl';
  }

  switch(settings.label_type) {
    case 'label_and_placeholder':
      isShowLabel = true;
      isShowPlaceholder = true;
      break;
    case 'placeholder_only':
      isShowLabel = false;
      isShowPlaceholder = true;
      break;
    case 'label_only':
    default:
      isShowLabel = true;
      isShowPlaceholder = false;
      break;
  }

  _.each(personalFields, function(personalField) {
    var formFieldId              = 'field_' + personalField.id
        formField                = {};
        formField['fieldClass']  = '';
        formField['id']          = 'responder_form_' + formGeneratedId + '_' + formFieldId;
        formField['isHidden']    = settings['is_hidden_field_' + formFieldId] === 'yes';
        formField['isRequired']  = settings[formFieldId + '_required'] === 'yes';
        formField['isShow']      = settings['is_show_' + formFieldId] === 'yes';
        formField['label']       = settings[formFieldId + '_field_label'];
        formField['order']       = settings[formFieldId + '_order'];
        formField['placeholder'] = '';
        formField['type']        = settings[formFieldId + '_field_type'];
        formField['typeHtml']    = 'text';

    switch(formField['type']) {
      case 'date':
        formField['typeHtml']  = 'date';
        break;
      case 'number':
        formField['typeHtml']   = 'number';
        break;
      case 'bool':
        formField['typeHtml']   = 'checkbox';
        break;
      case 'phone':
        formField['typeHtml']   = 'tel';
        break;
      case 'choice':
        formField['options']    = personalField.options;
        break;
      case 'multichoice':
        formField['options']    = personalField.options;
        break;
    }

    if (formField.isRequired) {
      formField['fieldClass'] += ' elementor-mark-required';
    }

    if (isShowPlaceholder) {
      formField['placeholder'] = settings[formFieldId + '_field_placeholder'];
    }

    formFields.push(formField);
  });

  formFields = _.sortBy(formFields, 'order');
#>

<div class="responder-form-main-wrapper {{ formMainClass }}">
  <form class="responder-form-wrapper" method="post">
    <div class="fields-wrapper">

      <# _.each(formFields, function(field) { #>
        <# if (!field.isShow || field.isHidden) { return false; } #>

        <div class="res-form-field res-form-field-input {{ field.fieldClass }}" data-field-type="{{ field.type }}">

          <# if (isShowLabel && !!field.label.length && field.type !== 'bool') { #>
            <label class="elementor-field-label" for="{{ field.id }}">{{ field.label }}</label>
          <# } #>

          <# if (field.type === 'textarea') { #>
            <textarea id="{{ field.id }}" placeholder="{{ field.placeholder }}"></textarea>

          <# } else if (field.type === 'choice') { #>
            <select id="{{ field.id }}">
              <# _.each(field.options, function(option) { #>
                <option value="{{ option.id }}">{{ option.name }}</option>
              <# }); #>
            </select>

          <# } else if (field.type === 'multichoice') { #>
            <# _.each(field.options, function(option) { #>
              <# var optionId = field.id + '_' + option.id; #>
              <div class="checkboxes">
                <input id="{{ optionId }}" type="checkbox" value="{{ option.id }}" />
                <label for="{{ optionId }}">{{ option.name }}</label>
              </div>
            <# }); #>

          <# } else if (field.type === 'bool') { #>
            <input id="{{ field.id }}" type="checkbox" />
            <label for="{{ field.id }}" class="elementor-field-label">
              {{ field.label }}
            </label>
          <# } else { #>
            <input id="{{ field.id }}" type="{{ field.typeHtml }}" placeholder="{{ field.placeholder }}" />
          <# } #>

        </div>

      <# }); #>

      <div class="res-form-field res-form-field-submit">
        <input type="submit" class="res-button-submit {{ hoverAnimation }}" value="{{ settings.submit_label }}">
      </div>

    </div>
  </form>

</div>
<?php // phpcs:enable ?>
