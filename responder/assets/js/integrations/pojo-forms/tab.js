(function ($) {
  $(function () {

    var subscribersListSelector = '#pojo-subscribers-lists',
      createFormButtonSelector = '#pojo-create-form',
      fieldsTableCheckboxSelector = '.fields-table [type="checkbox"]',
      requiredFieldsIds = ['email', 'phone'],

      $pojoForm = $('#pojo-responder-form'),
      $fieldsTable = $pojoForm.find('.fields-table'),
      $fieldsTableWarning = $pojoForm.find('.fields-table-warning'),
      $submitButton = $pojoForm.find(createFormButtonSelector);

    $pojoForm.each(function () {
      $pojoForm
        .on('change', subscribersListSelector, onChoosingList)
        .on('change', fieldsTableCheckboxSelector, onTableCheckboxChange)
        .on('submit', onCreatePojoForm);

      initForm();
    });

    function initForm() {
      $submitButton
        .attr('disabled', true);

      $pojoForm.find(subscribersListSelector)
        .select2({
          dir: 'rtl',
          minimumResultsForSearch: 5,
          width: '280px'
        });
    }

    function onChoosingList(event) {
      var listId = event.currentTarget.value,
        $loadingRow = $fieldsTable.find('tfoot .loading-text').clone();

      $fieldsTable.find('tbody').empty().append($loadingRow);

      $.RMP_AJAX('getPersonalFieldsByListId', {
        list_id: listId,
        system_name: 'responder'
      })
      .done(renderTableFields);
    }

    function onTableCheckboxChange(event) {
      var $checkbox = $(event.currentTarget),
        $fieldRow = $checkbox.parents('.field-row'),
        checkboxType = event.currentTarget.name.replace('-field', '');

      switch(checkboxType) {
        case 'show':
          if (!$checkbox.is(':checked')) {
            $fieldRow
              .find('.responder-required-field [type="checkbox"]')
              .prop('checked', false);
          }
          break;

        case 'required':
          if ($checkbox.is(':checked')) {
            $fieldRow
              .find('.responder-show-field [type="checkbox"]')
              .prop('checked', true)
          }
          break;
      }

      checkRequiredFields();
    }

    function onCreatePojoForm(event) {
      var formData   = $(event.currentTarget).serializeArray(),
        fieldArrays  = ['show-field', 'required-field'],
        flatFormData = {};

      _.each(formData, function (data) {
        if (fieldArrays.indexOf(data.name) !== -1) {
          if (typeof flatFormData[data.name] === 'undefined') {
            flatFormData[data.name] = [data.value];
          } else {
            flatFormData[data.name].push(data.value);
          }
        } else {
          flatFormData[data.name] = data.value;
        }
      });

      $submitButton
        .html($submitButton.data('save_text'))
        .prop('disabled', true);

      $.RMP_AJAX('createPojoForm', { form_data: flatFormData }, true)
        .done(function (url) {
          $submitButton.html($submitButton.data('success_text'));
          if (url.length) {
            window.location.href = url;
          }
        })
        .fail(function() {
          $submitButton
            .html($submitButton.data('text'))
            .prop('disabled', false);
        });

      event.preventDefault();
    }

    function renderTableFields(fields) {
      var $fieldsRows = [],
        $customFieldsTitle = $fieldsTable.find('tfoot .custom-fields-title').clone(),
        fieldRowTemplate = $fieldsTable.find('tfoot .field-row').clone()[0].outerHTML,
        isCustomFieldsTitleAdded = false;

      _.each(fields, function(field, index) {
        var $fieldRow = $(
          fieldRowTemplate
            .replace(/{{ fieldId }}/g, field.id)
            .replace('{{ fieldName }}', field.name)
            .replace('{{ uriParam }}', field.uri_param)
        );


        if (requiredFieldsIds.indexOf(field.id) !== -1) {
          $fieldRow.find('[name="show-field"], [name="required-field"]').prop('checked', true);
        }

        if (field.type === 'date') {
          $fieldRow.find('[name="show-field"], [name="required-field"]').prop('disabled', true);
        }

        if (!isCustomFieldsTitleAdded && !isNaN(field.id)) {
          $fieldsRows.push($customFieldsTitle);
          isCustomFieldsTitleAdded = true;
        }

        $fieldsRows.push($fieldRow);
      });

      // Enable submit button
      $submitButton.attr('disabled', false);

      $fieldsTable.find('tbody').empty().append($fieldsRows);
    }

    function checkRequiredFields() {
      var requiredFieldsIdsCounts = 0;

      _.each(requiredFieldsIds, function(requiredFieldId) {
        var isRequiredField = $fieldsTable
          .find('[name="required-field"][value="' + requiredFieldId + '"]')
          .is(':checked');

        if (isRequiredField) {
          requiredFieldsIdsCounts++;
        }
      });

      if (requiredFieldsIdsCounts > 0) {
        $fieldsTableWarning.hide();
        $submitButton.prop('disabled', false);
      } else {
        $fieldsTableWarning.show();
        $submitButton.prop('disabled', true);
      }
    }
  });
}(jQuery));
