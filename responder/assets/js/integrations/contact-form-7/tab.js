(function ($) {
  $(function () {

    var chosenSystemSelector = '#chosen-system',
        subscribersListSelector = '#cf7-subscribers-lists',
        tagsSelector = '#tags',
        createFormButtonSelector = '#cf7-create-form',
        fieldsTableCheckboxSelector = '.fields-table [type="checkbox"]',
        hiddenFieldValueActionSelector = '.hidden-field-value-action',
        fetchedTags = [],
        requiredFieldsIds = ['email', 'phone'],

        $cf7Form = $('#cf7-responder-form'),
        $fieldsTable = $cf7Form.find('.fields-table'),
        $fieldsTableWarning = $cf7Form.find('.fields-table-warning'),
        $submitButton = $cf7Form.find(createFormButtonSelector);

    $cf7Form.each(function () {
      $cf7Form
        .on('change', chosenSystemSelector, onChoosingSystem)
        .on('change', subscribersListSelector, onChoosingList)
        .on('change', fieldsTableCheckboxSelector, onTableCheckboxChange)
        .on('change', hiddenFieldValueActionSelector, onHiddenFieldValueActionChange)
        .on('change', tagsSelector, onTagsChange)
        .on('submit', onCreateCF7Form);

      initForm();
    });

    function initForm() {
      $cf7Form
        .find(createFormButtonSelector)
          .attr('disabled', true)
        .end()
        .find(chosenSystemSelector)
          .trigger('change');
    }

    function onChoosingSystem(event) {
      var chosenSystem     = event.currentTarget.value;
          $emptyListRow    = $fieldsTable.find('tfoot .empty-list-text').clone(),
          $subscribersList = $cf7Form.find(subscribersListSelector).attr('disabled', true),
          $tagsList        = $cf7Form.find(tagsSelector).attr('disabled', true);

      switch(chosenSystem) {
        case 'responder':
          $cf7Form
            .find('[name="tags"]')
              .prop('value', '').parents('.responder-form-row').addClass('hidden').end().end()
            .find('[name="onexisting_rejoin"], [name="onexisting_joindate"]')
              .prop('checked', false).parents('.responder-form-row').addClass('hidden').end().end()
            .find('[name="action_on_existing"]')
              .parents('.responder-form-row').removeClass('hidden').end().end();
          break;

        case 'responder_live':
          $cf7Form
            .find('[name="action_on_existing"]')
              .prop('selectedIndex', 0).parents('.responder-form-row').addClass('hidden').end().end()
            .find('[name="tags"], [name="onexisting_rejoin"], [name="onexisting_joindate"]')
              .parents('.responder-form-row').removeClass('hidden').end().end();

          $.RMP_AJAX('getSubscribersTags')
            .done(function (tags) {
              fetchedTags = [].concat(tags);
              renderTags(fetchedTags, $tagsList);
            });
          break;
      }

      // Clean up the table
      $fieldsTable.find('tbody').empty().append($emptyListRow);

      // Disable submit button
      $submitButton.attr('disabled', true);

      $.RMP_AJAX('getListsBySystemName', { system_name: chosenSystem })
        .done(function (lists) {
          renderSubscribersLists(lists, $subscribersList);
        });
    }

    function onChoosingList(event) {
      var listId = event.currentTarget.value,
          chosenSystemName = $cf7Form.find(chosenSystemSelector).val(),
          $loadingRow = $fieldsTable.find('tfoot .loading-text').clone();

      $fieldsTable.find('tbody').empty().append($loadingRow);

      $.RMP_AJAX('getPersonalFieldsByListId', {
          list_id: listId,
          system_name: chosenSystemName
        })
        .done(renderTableFields)
    }

    function onTableCheckboxChange(event) {
      var $checkbox    = $(event.currentTarget),
          $fieldRow    = $checkbox.parents('.field-row'),
          checkboxType = event.currentTarget.name.replace('-field', '');

      switch(checkboxType) {
        case 'show':
          if ($checkbox.is(':checked')) {
            $fieldRow
              .find('.responder-hidden-field [type="checkbox"]')
                .prop('checked', false)
              .end()
              .find('.responder-hidden-field-value')
                .attr('hidden', '')
                .find('.hidden-field-value-action, .hidden-field-value')
                  .val('')
                .end()
                .find('.hidden-field-value, .hidden-field-param')
                  .attr('hidden', '');
          } else {
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
              .end()
              .find('.responder-hidden-field [type="checkbox"]')
                .prop('checked', false)
              .end()
              .find('.responder-hidden-field-value')
                .attr('hidden', '')
                .find('.hidden-field-value-action, .hidden-field-value')
                  .val('')
                .end()
                .find('.hidden-field-value, .hidden-field-param')
                  .attr('hidden', '');
          }
          break;

        case 'hidden':
          if ($checkbox.is(':checked')) {
            $fieldRow
              .find('.responder-show-field [type="checkbox"], .responder-required-field [type="checkbox"]')
                .prop('checked', false)
              .end()
              .find('.responder-hidden-field-value')
                .removeAttr('hidden');
          } else {
            $fieldRow
              .find('.responder-hidden-field-value')
                .attr('hidden', '')
                .find('.hidden-field-value-action, .hidden-field-value')
                  .val('')
                .end()
                .find('.hidden-field-value, .hidden-field-param')
                  .attr('hidden', '');
          }
          break;
      }

      checkRequiredFields();
    }

    function onHiddenFieldValueActionChange(event) {
      var $selectBox = $(event.currentTarget);

      switch ($selectBox.val()) {
        case 'value':
          $selectBox.parent()
            .find('.hidden-field-value')
              .removeAttr('hidden')
              .val('')
            .end()
            .find('.hidden-field-param')
              .attr('hidden', '')
          break;
        case 'param':
          $selectBox.parent()
            .find('.hidden-field-param')
              .removeAttr('hidden')
            .end()
            .find('.hidden-field-value')
              .attr('hidden', '')
              .val('');
          break;
      }
    }

    function onTagsChange(event) {
      var $selectBox = $(event.currentTarget),
          currentTagsIds  = [],
          fetchedTagsIds  = _.pluck(fetchedTags, 'id'),
          selectedTagsIds = [],
          newTagsIds      = [],
          newTagName      = '';

      _.each($selectBox.find('option'), function (option) {
        var tagId = option.value,
            selectBoxTagIds = $selectBox.val() || []

        if (!isNaN(tagId) && _.contains(selectBoxTagIds, tagId)) {
          selectedTagsIds.push(tagId);
        }

        currentTagsIds.push(tagId);
      });

      newTagsIds = _.difference(currentTagsIds, fetchedTagsIds);

      if (newTagsIds.length) {
        newTagName = newTagsIds.pop();

        $selectBox.attr('disabled', true);
        $.RMP_AJAX('createSubscribersTag', { tag_name: newTagName })
          .done(function (newTag) {
            fetchedTags.push(newTag);
            selectedTagsIds.push(newTag.id);
          })
          .always(function() {
            renderTags(fetchedTags, $selectBox, selectedTagsIds);
            $selectBox.attr('disabled', false);
          });
      }
    }

    function onCreateCF7Form(event) {
      var formData     = $(event.currentTarget).serializeArray(),
          fieldArrays  = ['show-field', 'required-field', 'hidden-field', 'tags'],
          flatFormData = {};

      _.each(formData, function (data) {
        if (_.contains(fieldArrays, data.name)) {
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

      $.RMP_AJAX('createCF7Form', { form_data: flatFormData }, true)
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

    function renderSubscribersLists(lists, $subscribersList) {
      var optionTags = '';

      _.each(lists, function(option) {
        optionTags += '<option value="' + option.id + '">' + option.name + '</option>';
      });

      $subscribersList
        .find('option:not(:first-child)')
          .remove()
        .end()
        .append(optionTags)
        .removeAttr('disabled')
        .val('')
        .select2({
          dir: 'rtl',
          minimumResultsForSearch: 5,
          width: '280px'
        });
    }

    function renderTags(tags, $tagsList, selectedTagsIds) {
      var optionTags = '';

      selectedTagsIds = selectedTagsIds || [];

      _.each(tags, function (tag) {
        var selected = '';

        if (_.contains(selectedTagsIds, tag.id)) {
          selected = 'selected';
        }

        optionTags += '<option value="' + tag.id + '" ' + selected + '>' + tag.name + '</option>';
      });

      $tagsList
        .empty()
        .append(optionTags)
        .removeAttr('disabled')
        .select2({
          dir: 'rtl',
          minimumResultsForSearch: 5,
          tags: true,
          width: '280px'
        });
    }

    function renderTableFields(fields) {
      var $fieldsRows = [],
          $customFieldsListTitle = $fieldsTable.find('tfoot .custom-fields-title').clone(),
          fieldRowTemplate = $fieldsTable.find('tfoot .field-row').clone()[0].outerHTML,
          isCustomFieldsTitleAdded = false;

      _.each(fields, function(field, index) {
        var $fieldRow = $(
          fieldRowTemplate
            .replace(/{{ fieldId }}/g, field.id)
            .replace('{{ fieldName }}', field.name)
            .replace('{{ uriParam }}', field.uri_param)
        );

        if (isNaN(field.id)) {
          $fieldRow.find('.responder-hidden-field, .responder-hidden-field-value').remove();
        }

        if (_.contains(requiredFieldsIds, field.id)) {
          $fieldRow.find('[name="show-field"], [name="required-field"]').prop('checked', true);
        }

        if (!isCustomFieldsTitleAdded && !isNaN(field.id)) {
          $fieldsRows.push($customFieldsListTitle);
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
